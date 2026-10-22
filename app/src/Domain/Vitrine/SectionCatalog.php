<?php

declare(strict_types=1);

namespace Barbier\Domain\Vitrine;

use Barbier\Domain\Shared\DomainException;

/**
 * Sections éditables et leurs champs (FR-006, FR-011). Seuls ces champs existent : l'admin ne peut ni
 * inventer de champ ni dépasser les longueurs. `rich` = mise en forme limitée (gras, italique, liens sûrs).
 */
final class SectionCatalog
{
    /** @var array<string, array{page:string, label:string, fields: array<string, array{label:string, max:int, rich:bool, multiline:bool}>}> */
    private const SECTIONS = [
        'hero' => ['page' => 'home', 'label' => 'Bandeau d’accueil', 'fields' => [
            'eyebrow' => ['label' => 'Surtitre', 'max' => 80, 'rich' => false, 'multiline' => false],
            'title' => ['label' => 'Nom affiché', 'max' => 40, 'rich' => false, 'multiline' => false],
            'lead' => ['label' => 'Accroche', 'max' => 160, 'rich' => false, 'multiline' => false],
            'lead_em' => ['label' => 'Accroche, suite en italique', 'max' => 120, 'rich' => false, 'multiline' => false],
            'cta_primary' => ['label' => 'Bouton principal', 'max' => 30, 'rich' => false, 'multiline' => false],
            'cta_secondary' => ['label' => 'Bouton secondaire', 'max' => 30, 'rich' => false, 'multiline' => false],
            'fact1_label' => ['label' => 'Fait 1 — intitulé', 'max' => 20, 'rich' => false, 'multiline' => false],
            'fact1_value' => ['label' => 'Fait 1 — valeur', 'max' => 50, 'rich' => false, 'multiline' => false],
            'fact2_label' => ['label' => 'Fait 2 — intitulé', 'max' => 20, 'rich' => false, 'multiline' => false],
            'fact2_value' => ['label' => 'Fait 2 — valeur', 'max' => 50, 'rich' => false, 'multiline' => false],
            'fact3_label' => ['label' => 'Fait 3 — intitulé', 'max' => 20, 'rich' => false, 'multiline' => false],
            'fact3_value' => ['label' => 'Fait 3 — valeur', 'max' => 50, 'rich' => false, 'multiline' => false],
        ]],
        'maison' => ['page' => 'home', 'label' => 'La maison', 'fields' => [
            'eyebrow' => ['label' => 'Surtitre', 'max' => 40, 'rich' => false, 'multiline' => false],
            'title' => ['label' => 'Titre', 'max' => 80, 'rich' => false, 'multiline' => false],
            'body' => ['label' => 'Texte (une ligne vide entre deux paragraphes)', 'max' => 2000, 'rich' => true, 'multiline' => true],
            'rules_title' => ['label' => 'Titre de l’encadré', 'max' => 60, 'rich' => false, 'multiline' => false],
            'rules' => ['label' => 'Règles (une par ligne)', 'max' => 600, 'rich' => false, 'multiline' => true],
        ]],
        'carte' => ['page' => 'home', 'label' => 'Tarifs (textes)', 'fields' => [
            'eyebrow' => ['label' => 'Surtitre', 'max' => 40, 'rich' => false, 'multiline' => false],
            'title' => ['label' => 'Titre', 'max' => 80, 'rich' => false, 'multiline' => false],
            'intro' => ['label' => 'Introduction', 'max' => 300, 'rich' => true, 'multiline' => true],
            'note' => ['label' => 'Note de bas de carte', 'max' => 300, 'rich' => true, 'multiline' => true],
        ]],
        'equipe' => ['page' => 'home', 'label' => 'Équipe (textes)', 'fields' => [
            'eyebrow' => ['label' => 'Surtitre', 'max' => 40, 'rich' => false, 'multiline' => false],
            'title' => ['label' => 'Titre', 'max' => 80, 'rich' => false, 'multiline' => false],
        ]],
        'reservation' => ['page' => 'home', 'label' => 'Réservation (textes)', 'fields' => [
            'eyebrow' => ['label' => 'Surtitre', 'max' => 40, 'rich' => false, 'multiline' => false],
            'title' => ['label' => 'Titre', 'max' => 80, 'rich' => false, 'multiline' => false],
            'intro' => ['label' => 'Introduction', 'max' => 300, 'rich' => true, 'multiline' => true],
        ]],
        'horaires' => ['page' => 'home', 'label' => 'Horaires (textes)', 'fields' => [
            'eyebrow' => ['label' => 'Surtitre', 'max' => 40, 'rich' => false, 'multiline' => false],
            'title' => ['label' => 'Titre', 'max' => 80, 'rich' => false, 'multiline' => false],
        ]],
        'footer' => ['page' => 'home', 'label' => 'Pied de page', 'fields' => [
            'tagline' => ['label' => 'Devise', 'max' => 120, 'rich' => false, 'multiline' => false],
        ]],
        'legal_notice' => ['page' => 'legal', 'label' => 'Mentions légales', 'fields' => [
            'title' => ['label' => 'Titre', 'max' => 80, 'rich' => false, 'multiline' => false],
            'body' => ['label' => 'Texte', 'max' => 8000, 'rich' => true, 'multiline' => true],
        ]],
        'privacy' => ['page' => 'legal', 'label' => 'Politique de confidentialité', 'fields' => [
            'title' => ['label' => 'Titre', 'max' => 80, 'rich' => false, 'multiline' => false],
            'body' => ['label' => 'Texte ({{retention_months}} = durée de conservation)', 'max' => 8000, 'rich' => true, 'multiline' => true],
        ]],
    ];

    /** @return array<string, array{page:string, label:string, fields: array<string, array{label:string, max:int, rich:bool, multiline:bool}>}> */
    public static function all(): array
    {
        return self::SECTIONS;
    }

    /** @return array{page:string, label:string, fields: array<string, array{label:string, max:int, rich:bool, multiline:bool}>} */
    public static function get(string $key): array
    {
        return self::SECTIONS[$key] ?? throw new DomainException('not_found', 'Section inconnue.', 'key');
    }

    /**
     * Valide un jeu de champs traduits pour une section : champs connus seulement, FR obligatoire, longueurs.
     *
     * @param array<string, array<string,string|null>> $fields
     * @return array<string, TranslatedText>
     */
    public static function validate(string $key, array $fields): array
    {
        $def = self::get($key)['fields'];
        $out = [];
        foreach ($fields as $name => $values) {
            if (!isset($def[$name])) {
                throw new DomainException('unknown_field', "Champ inconnu : $name", "fields.$name");
            }
            $out[$name] = new TranslatedText($values, "fields.$name", $def[$name]['max']);
        }
        foreach (array_keys($def) as $name) {
            if (!isset($out[$name])) {
                throw new DomainException('fr_required', 'Le texte en français est obligatoire.', "fields.$name.fr");
            }
        }

        return $out;
    }
}
