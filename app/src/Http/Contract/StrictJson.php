<?php

declare(strict_types=1);

namespace Barbier\Http\Contract;

use Barbier\Http\Request;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;

/**
 * Désérialisation stricte (élément 3 du contrat matérialisé) : chaque requête est validée contre
 * la spec elle-même. Paramètres de chemin et de requête, corps JSON ; champ inconnu → refus.
 * Les paramètres de requête sont des chaînes dans la spec (pas de coercition implicite).
 */
final class StrictJson
{
    private Validator $validator;

    public function __construct(private readonly Spec $spec)
    {
        $this->validator = new Validator();
        $this->validator->setMaxErrors(20);
        $this->validator->resolver()->registerRaw($spec->raw(), Spec::BASE_URI);
    }

    /**
     * @param array<string,string> $pathParams
     * @return array{path:array<string,string>, query:array<string,string>, body:mixed}
     */
    public function validateRequest(string $method, string $pattern, Request $request, array $pathParams): array
    {
        $op = $this->spec->operation($method, $pattern);
        if ($op === null) {
            throw new ContractViolation([['field' => '', 'message' => 'opération absente du contrat']]);
        }
        $errors = [];

        // Paramètres de chemin et de requête.
        $declared = ['path' => [], 'query' => []];
        foreach ($op['parameters'] ?? [] as $i => $param) {
            $in = $param['in'] ?? '';
            if (!isset($declared[$in])) {
                continue;
            }
            $declared[$in][$param['name']] = [$i, $param];
        }
        $query = $request->query();
        foreach (array_keys($query) as $name) {
            if (!isset($declared['query'][$name])) {
                $errors[] = ['field' => "query.$name", 'message' => 'paramètre inconnu'];
            }
        }
        foreach (['path' => $pathParams, 'query' => $query] as $in => $values) {
            foreach ($declared[$in] as $name => [$i, $param]) {
                if (!array_key_exists($name, $values)) {
                    if (($param['required'] ?? false) === true) {
                        $errors[] = ['field' => "$in.$name", 'message' => 'paramètre obligatoire'];
                    }
                    continue;
                }
                if (!is_string($values[$name])) {
                    $errors[] = ['field' => "$in.$name", 'message' => 'valeur multiple interdite'];
                    continue;
                }
                $ptr = Spec::pointer('paths', $pattern, strtolower($method), 'parameters', (string) $i, 'schema');
                $errors = [...$errors, ...$this->check($values[$name], $ptr, "$in.$name")];
            }
        }

        // Corps.
        $body = null;
        $bodySpec = $op['requestBody']['content']['application/json'] ?? null;
        $multipart = isset($op['requestBody']['content']['multipart/form-data']);
        $raw = trim($request->rawBody());
        if ($multipart) {
            // Envoi de fichier : le contrôleur valide le fichier lui-même (type par contenu, taille).
            if (!str_starts_with(strtolower($request->header('content-type') ?? ''), 'multipart/form-data')) {
                $errors[] = ['field' => 'body', 'message' => 'Content-Type multipart/form-data attendu'];
            } elseif ($request->files() === []) {
                $errors[] = ['field' => 'file', 'message' => 'fichier manquant'];
            }
        } elseif ($bodySpec === null) {
            if ($raw !== '') {
                $errors[] = ['field' => 'body', 'message' => 'aucun corps attendu'];
            }
        } elseif ($raw === '') {
            if (($op['requestBody']['required'] ?? false) === true) {
                $errors[] = ['field' => 'body', 'message' => 'corps JSON obligatoire'];
            }
        } else {
            if (!str_starts_with(strtolower($request->header('content-type') ?? ''), 'application/json')) {
                $errors[] = ['field' => 'body', 'message' => 'Content-Type application/json attendu'];
            } else {
                try {
                    $decoded = json_decode($raw, false, 64, JSON_THROW_ON_ERROR);
                    $ptr = Spec::pointer('paths', $pattern, strtolower($method), 'requestBody', 'content', 'application/json', 'schema');
                    $errors = [...$errors, ...$this->check($decoded, $ptr, 'body')];
                    $body = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    $errors[] = ['field' => 'body', 'message' => 'JSON invalide'];
                }
            }
        }

        if ($errors !== []) {
            throw new ContractViolation($errors);
        }

        return ['path' => $pathParams, 'query' => $query, 'body' => $body];
    }

    /** Valide une valeur (décodée en objets) contre un nœud de la spec. @return list<array{field:string, message:string}> */
    public function check(mixed $value, string $pointer, string $field): array
    {
        $result = $this->validator->validate($value, $pointer);
        if ($result->isValid()) {
            return [];
        }
        $out = [];
        foreach ((new ErrorFormatter())->format($result->error(), true) as $path => $messages) {
            foreach ($messages as $message) {
                $out[] = ['field' => $field . ($path === '/' ? '' : str_replace('/', '.', (string) $path)), 'message' => (string) $message];
            }
        }

        return $out;
    }

    /**
     * Valide une réponse (tests d'intégration) : le corps correspond-il au schéma déclaré pour ce statut ?
     *
     * @return list<array{field:string, message:string}>
     */
    public function responseErrors(string $method, string $pattern, int $status, string $body, string $contentType): array
    {
        $op = $this->spec->operation($method, $pattern);
        $resp = $op['responses'][(string) $status] ?? null;
        $responsePtr = ['paths', $pattern, strtolower($method), 'responses', (string) $status];
        if (is_array($resp) && isset($resp['$ref'])) {
            $name = substr((string) $resp['$ref'], strlen('#/components/responses/'));
            $resp = $this->spec->document()['components']['responses'][$name] ?? null;
            $responsePtr = ['components', 'responses', $name];
        }
        if ($resp === null) {
            return [['field' => 'status', 'message' => "statut $status non déclaré au contrat"]];
        }
        $media = str_starts_with($contentType, 'application/problem+json') ? 'application/problem+json' : 'application/json';
        if (!isset($resp['content'])) {
            return trim($body) === '' ? [] : [['field' => 'body', 'message' => 'aucun corps déclaré']];
        }
        if (!isset($resp['content'][$media]['schema'])) {
            return [['field' => 'content-type', 'message' => "média $media non déclaré pour $status"]];
        }
        try {
            $decoded = json_decode($body, false, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [['field' => 'body', 'message' => 'réponse JSON invalide']];
        }

        return $this->check($decoded, Spec::pointer(...$responsePtr, ...['content', $media, 'schema']), 'response');
    }
}
