<?php

declare(strict_types=1);

namespace Barbier\Tests\Integration;

use Barbier\Http\Contract\Spec;
use Barbier\Http\Contract\StrictJson;
use Barbier\Http\Request;
use Barbier\Http\Response;

/** Envoi d'images (FR-007) : type par contenu, ré-encodage, aucun script exécutable ne survit. */
final class MediaUploadTest extends IntegrationCase
{
    /** @var array<string,string> */
    private array $csrf;
    private string $dir;

    protected function setUp(): void
    {
        self::pdo();
        self::truncate('session', 'recovery_code', 'editor');
        self::resetContent();
        $this->jar = [];
        $this->csrf = $this->asEditor();
        $this->dir = dirname(__DIR__, 2) . '/app/var/test-uploads';
        @mkdir($this->dir, 0755, true);
    }

    private function upload(string $bytes): Response
    {
        $tmp = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($tmp, $bytes);
        $req = new Request('POST', '/api/v1/admin/media', '', [], $this->csrf + ['Content-Type' => 'multipart/form-data; boundary=x'], $this->jar, '127.0.0.1',
            ['file' => ['name' => 'photo.jpg', 'tmp_name' => $tmp, 'size' => strlen($bytes), 'error' => UPLOAD_ERR_OK]]);
        $r = $this->kernel()->handle($req);
        @unlink($tmp);
        self::assertSame([], (new StrictJson(Spec::default()))->responseErrors('POST', '/api/v1/admin/media', $r->status, $r->body, $r->headers['Content-Type'] ?? ''), $r->body);

        return $r;
    }

    private static function jpeg(int $w, int $h): string
    {
        $img = imagecreatetruecolor($w, $h);
        imagefilledrectangle($img, 0, 0, $w, $h, (int) imagecolorallocate($img, 120, 90, 60));
        ob_start();
        imagejpeg($img, null, 90);

        return (string) ob_get_clean();
    }

    /** @happy photo 2400×1600 → WebP 1600 px, enregistrée et utilisable comme photo de barbier */
    public function testPhotoRedimensionneeEtReencodee(): void
    {
        $r = $this->upload(self::jpeg(2400, 1600));
        self::assertSame(201, $r->status, $r->body);
        $m = self::json($r);
        self::assertSame(1600, $m['width']);
        self::assertSame(1067, $m['height']);
        self::assertNull($m['warning']);
        $file = $this->dir . '/' . basename($m['src']);
        self::assertFileExists($file);
        self::assertSame("RIFF", substr((string) file_get_contents($file), 0, 4));
    }

    /** @security fichier polyglotte (JPEG + PHP) : le ré-encodage ne laisse aucun code ; SVG et faux JPEG refusés */
    public function testPolyglotteSvgEtFauxJpeg(): void
    {
        $r = $this->upload(self::jpeg(400, 300) . "<?php system('id'); ?>");
        self::assertSame(201, $r->status);
        $stored = (string) file_get_contents($this->dir . '/' . basename(self::json($r)['src']));
        self::assertStringNotContainsString('<?php', $stored);
        self::assertSame(422, $this->upload('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')->status);
        self::assertSame(422, $this->upload("<?php echo 'pas une image';")->status);
    }

    /** @edge très petite image → acceptée avec avertissement ; image utilisée → suppression refusée ; texte alternatif traduit */
    public function testPetiteImageReferencesEtAlt(): void
    {
        $m = self::json($this->upload(self::jpeg(120, 80)));
        self::assertNotNull($m['warning']);
        $alt = $this->call('PUT', '/api/v1/admin/media/{id}', '/api/v1/admin/media/' . $m['id'], ['alt' => ['fr' => 'Le fauteuil', 'nl' => 'De stoel']], [], $this->csrf);
        self::assertSame('De stoel', self::json($alt)['alt']['nl']);
        $hero = array_values(array_filter(self::json($this->call('GET', '/api/v1/admin/sections', '/api/v1/admin/sections'))['sections'], static fn ($s) => $s['key'] === 'hero'))[0];
        $fields = array_map(static fn ($f) => $f['values'], $hero['fields']);
        $this->call('PUT', '/api/v1/admin/sections/{key}', '/api/v1/admin/sections/hero', ['version' => $hero['version'], 'fields' => $fields, 'mediaId' => $m['id']], [], $this->csrf);
        self::assertStringContainsString(basename($m['src']), $this->page('GET', '/fr/')->body);
        self::assertSame(409, $this->call('DELETE', '/api/v1/admin/media/{id}', '/api/v1/admin/media/' . $m['id'], null, [], $this->csrf)->status);
        $free = self::json($this->upload(self::jpeg(600, 400)));
        self::assertSame(204, $this->call('DELETE', '/api/v1/admin/media/{id}', '/api/v1/admin/media/' . $free['id'], null, [], $this->csrf)->status);
        self::assertFileDoesNotExist($this->dir . '/' . basename($free['src']));
    }

    /** @negative image de plus de 8 Mo refusée */
    public function testImageTropLourde(): void
    {
        self::assertSame(422, $this->upload(self::jpeg(50, 50) . str_repeat("\0", 8 * 1024 * 1024))->status);
    }
}
