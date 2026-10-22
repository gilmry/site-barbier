<?php

declare(strict_types=1);

namespace Barbier\Adapter\Media;

use Barbier\Domain\Shared\DomainException;
use Barbier\Domain\Vitrine\ImageProcessor;

/**
 * Traitement d'image par GD (FR-007 @security) : type vérifié par le CONTENU (signature + getimagesize),
 * jamais par l'extension ; décodage puis ré-encodage complet en WebP (JPEG si WebP indisponible), ce qui
 * retire les métadonnées EXIF et neutralise un fichier polyglotte ; largeur ramenée à 1600 px au plus.
 */
final class GdImageProcessor implements ImageProcessor
{
    public const MAX_BYTES = 8 * 1024 * 1024;
    public const MAX_WIDTH = 1600;
    public const MAX_PIXELS = 40_000_000;

    public function process(string $path): array
    {
        $size = @filesize($path);
        if ($size === false || $size === 0) {
            throw new DomainException('invalid_image', 'Fichier vide ou illisible.', 'file');
        }
        if ($size > self::MAX_BYTES) {
            throw new DomainException('image_too_large', 'Image trop lourde (8 Mo au plus).', 'file');
        }
        $head = (string) file_get_contents($path, false, null, 0, 16);
        $type = match (true) {
            str_starts_with($head, "\xFF\xD8\xFF") => IMAGETYPE_JPEG,
            str_starts_with($head, "\x89PNG\r\n\x1A\n") => IMAGETYPE_PNG,
            str_starts_with($head, 'RIFF') && substr($head, 8, 4) === 'WEBP' => IMAGETYPE_WEBP,
            default => null,
        };
        $info = @getimagesize($path);
        if ($type === null || $info === false || $info[2] !== $type) {
            throw new DomainException('invalid_image', 'Format non autorisé : JPEG, PNG ou WebP seulement.', 'file');
        }
        [$w, $h] = [$info[0], $info[1]];
        if ($w < 1 || $h < 1 || $w * $h > self::MAX_PIXELS) {
            throw new DomainException('invalid_image', 'Dimensions d’image non prises en charge.', 'file');
        }
        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
        };
        if ($src === false) {
            throw new DomainException('invalid_image', 'Image endommagée ou illisible.', 'file');
        }
        if ($type === IMAGETYPE_JPEG) {
            $src = $this->applyOrientation($src, $path);
            [$w, $h] = [imagesx($src), imagesy($src)];
        }
        $tw = min($w, self::MAX_WIDTH);
        $th = (int) round($h * $tw / $w);
        $dst = imagecreatetruecolor($tw, $th);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $h);
        ob_start();
        $webp = function_exists('imagewebp') && imagewebp($dst, null, 82);
        if (!$webp) {
            imagejpeg($dst, null, 85);
        }
        $bytes = (string) ob_get_clean();

        return ['bytes' => $bytes, 'mime' => $webp ? 'image/webp' : 'image/jpeg', 'extension' => $webp ? 'webp' : 'jpg', 'width' => $tw, 'height' => $th];
    }

    /** Applique l'orientation EXIF avant de la perdre au ré-encodage (photos de téléphone). */
    private function applyOrientation(\GdImage $img, string $path): \GdImage
    {
        $exif = function_exists('exif_read_data') ? @exif_read_data($path) : false;
        $o = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
        $rotated = match ($o) {
            3 => imagerotate($img, 180, 0),
            6 => imagerotate($img, -90, 0),
            8 => imagerotate($img, 90, 0),
            default => $img,
        };

        return $rotated === false ? $img : $rotated;
    }
}
