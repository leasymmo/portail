<?php
declare(strict_types=1);

namespace Cdh\Portal\Admin;

use PDO;
use Throwable;

/**
 * Service de traduction automatique intelligente via IA
 * Supporte : Google Gemini, OpenAI ChatGPT, Anthropic Claude
 * Carré d'Hôtes - Conciergerie de luxe
 */
class AiTranslator
{
    public const PROVIDER_GEMINI = 'gemini';
    public const PROVIDER_OPENAI = 'openai';
    public const PROVIDER_CLAUDE = 'claude';

    public const DEFAULT_MODELS = [
        self::PROVIDER_GEMINI => 'gemini-2.5-flash',
        self::PROVIDER_OPENAI => 'gpt-4o-mini',
        self::PROVIDER_CLAUDE => 'claude-3-5-haiku-latest',
    ];

    /**
     * Teste la connexion à l'API IA spécifiée
     */
    public static function testConnection(string $provider, string $apiKey, ?string $model = null): array
    {
        $startTime = microtime(true);
        $testDict = [
            'test_phrase' => 'Stationnement gratuit dans la rue au pied de l\'immeuble.'
        ];

        try {
            $translated = self::callProvider($provider, $apiKey, $model, $testDict, 'Test de connexion');
            $durationMs = (int)round((microtime(true) - $startTime) * 1000);

            if (empty($translated['test_phrase'])) {
                return [
                    'ok' => false,
                    'error' => 'Réponse reçue invalide ou vide.',
                    'duration_ms' => $durationMs
                ];
            }

            return [
                'ok' => true,
                'provider' => $provider,
                'model' => $model ?: (self::DEFAULT_MODELS[$provider] ?? 'standard'),
                'sample_fr' => $testDict['test_phrase'],
                'sample_en' => $translated['test_phrase'],
                'duration_ms' => $durationMs
            ];
        } catch (Throwable $e) {
            $durationMs = (int)round((microtime(true) - $startTime) * 1000);
            return [
                'ok' => false,
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs
            ];
        }
    }

    /**
     * Traduit les champs de règles générales de stationnement
     * Reçoit le tableau complet (avec champs *_fr) et remplit les versions *_en
     */
    public static function translateParkingGeneral(PDO $db, array $data): array
    {
        $fieldsToTranslate = [
            'intro'                  => $data['intro_fr'] ?? null,
            'important_rule'         => $data['important_rule_fr'] ?? null,
            'free_parking_notes'     => $data['free_parking_notes_fr'] ?? null,
            'street_parking_info'    => $data['street_parking_info_fr'] ?? null,
            'street_parking_schedule'=> $data['street_parking_schedule_fr'] ?? null,
            'street_parking_rates'   => $data['street_parking_rates_fr'] ?? null,
            'tcl_conditions'         => $data['tcl_conditions_fr'] ?? null,
            'tcl_notes'              => $data['tcl_notes_fr'] ?? null,
        ];

        $dict = [];
        foreach ($fieldsToTranslate as $key => $val) {
            if ($val !== null && trim((string)$val) !== '') {
                $dict[$key] = trim((string)$val);
            }
        }

        if (empty($dict)) {
            return $data;
        }

        $translated = self::translateDictWithDbConfig($db, $dict, 'Règles générales de stationnement');
        if (!empty($translated)) {
            foreach ($translated as $key => $enVal) {
                $enKey = $key . '_en';
                $data[$enKey] = $enVal;
            }
        }

        return $data;
    }

    /**
     * Traduit les champs d'un lieu de stationnement
     * Reçoit le tableau (avec champs *_fr) et remplit les versions *_en
     */
    public static function translateParkingLocation(PDO $db, array $data): array
    {
        $fieldsToTranslate = [
            'name'             => $data['name_fr'] ?? null,
            'walking_time'     => $data['walking_time_fr'] ?? null,
            'transit_time'     => $data['transit_time_fr'] ?? null,
            'opening_hours'    => $data['opening_hours_fr'] ?? null,
            'rates'            => $data['rates_fr'] ?? null,
            'suitable_vehicles'=> $data['suitable_vehicles_fr'] ?? null,
            'how_to_reach'     => $data['how_to_reach_fr'] ?? null,
            'notes'            => $data['notes_fr'] ?? null,
        ];

        $dict = [];
        foreach ($fieldsToTranslate as $key => $val) {
            if ($val !== null && trim((string)$val) !== '') {
                $dict[$key] = trim((string)$val);
            }
        }

        if (empty($dict)) {
            return $data;
        }

        $translated = self::translateDictWithDbConfig($db, $dict, 'Fiche détaillée de lieu de stationnement');
        if (!empty($translated)) {
            foreach ($translated as $key => $enVal) {
                $enKey = $key . '_en';
                $data[$enKey] = $enVal;
            }
        }

        return $data;
    }

    /**
     * Récupère la config IA depuis la table admin_settings et traduit le dictionnaire
     */
    public static function translateDictWithDbConfig(PDO $db, array $dict, string $context = ''): array
    {
        try {
            $stmt = $db->query('SELECT setting_key, setting_value FROM admin_settings WHERE setting_key LIKE "ai_%"');
            $settings = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }

            $provider = $settings['ai_provider'] ?? self::PROVIDER_GEMINI;
            $apiKeyKey = 'ai_api_key_' . $provider;
            $apiKey = $settings[$apiKeyKey] ?? '';
            $modelKey = 'ai_model_' . $provider;
            $model = $settings[$modelKey] ?? (self::DEFAULT_MODELS[$provider] ?? null);

            if (empty($apiKey)) {
                // Pas de clé configurée : on journalise un avis sans bloquer l'enregistrement
                return [];
            }

            return self::callProvider($provider, $apiKey, $model, $dict, $context);
        } catch (Throwable $e) {
            error_log('[AiTranslator] Erreur lors de la traduction : ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Appelle le fournisseur d'IA sélectionné
     */
    private static function callProvider(string $provider, string $apiKey, ?string $model, array $dict, string $context): array
    {
        $provider = strtolower(trim($provider));
        if ($provider === self::PROVIDER_GEMINI) {
            return self::callGemini($apiKey, $model ?: self::DEFAULT_MODELS[self::PROVIDER_GEMINI], $dict, $context);
        } elseif ($provider === self::PROVIDER_OPENAI) {
            return self::callOpenAI($apiKey, $model ?: self::DEFAULT_MODELS[self::PROVIDER_OPENAI], $dict, $context);
        } elseif ($provider === self::PROVIDER_CLAUDE) {
            return self::callClaude($apiKey, $model ?: self::DEFAULT_MODELS[self::PROVIDER_CLAUDE], $dict, $context);
        }

        throw new \InvalidArgumentException("Fournisseur IA inconnu : {$provider}");
    }

    /**
     * Construction du prompt système contextualisé pour Carré d'Hôtes
     */
    private static function buildSystemPrompt(string $context): string
    {
        return "Tu es le traducteur bilingue officiel de la conciergerie de luxe Carré d'Hôtes à Lyon (France).\n"
            . "Ta mission : traduire avec fidélité, élégance et précision professionnelle un dictionnaire de textes français vers l'anglais, destiné à des voyageurs internationaux.\n"
            . "Contexte spécifique : {$context}.\n\n"
            . "Règles strictes de traduction :\n"
            . "1. Conserve le ton haut de gamme, bienveillant, clair et sécurisant.\n"
            . "2. Préserve les noms propres de lieux, enseignes et voiries (ex: 'Cours Émile-Zola', 'Rue Bataille', 'Grange Blanche', 'Laurent Bonnevay', 'Zenpark', 'EasyPark', 'TCL', 'Indigo'). Tu peux rajouter des précisions explicites pour les étrangers (ex: 'Metro A', 'Flachet station', 'Line D').\n"
            . "3. Adapte les unités, devises et créneaux horaires (ex: 'Lun-Sam : 9h-19h' -> 'Mon-Sat: 9am-7pm', 'gratuit dimanche et fériés' -> 'free on Sundays & public holidays', '2 €/h' -> '€2/h').\n"
            . "4. Conserve la mise en forme exacte (sauts de ligne, puces '•', tirets).\n"
            . "5. Format de sortie OBLIGATOIRE : Retourne UNIQUEMENT un objet JSON valide contenant exactement les mêmes clés que l'objet fourni, avec pour valeurs leur traduction anglaise.\n"
            . "Ne produis AUCUN texte avant ou après, pas de Markdown, pas d'explication.";
    }

    /**
     * Appel à Google Gemini (REST API)
     */
    private static function callGemini(string $apiKey, string $model, array $dict, string $context): array
    {
        $systemPrompt = self::buildSystemPrompt($context);
        $userText = $systemPrompt . "\n\nOBJET JSON À TRADUIRE DU FRANÇAIS VERS L'ANGLAIS :\n" . json_encode($dict, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $userText]
                    ]
                ]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature' => 0.2
            ]
        ];

        // Tentative avec le modèle demandé
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . urlencode($model) . ':generateContent?key=' . urlencode($apiKey);
        $res = self::httpPostJson($url, $payload, ['Content-Type: application/json']);

        // Si erreur 404 sur 2.5-flash, repli automatique sur 1.5-flash
        if ($res['http_code'] === 404 && $model !== 'gemini-1.5-flash') {
            $fallbackUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . urlencode($apiKey);
            $res = self::httpPostJson($fallbackUrl, $payload, ['Content-Type: application/json']);
        }

        if ($res['http_code'] !== 200) {
            $errBody = json_decode($res['body'] ?? '', true);
            $errMsg = $errBody['error']['message'] ?? ('Erreur HTTP Gemini ' . $res['http_code']);
            throw new \RuntimeException("Gemini API : " . $errMsg);
        }

        $data = json_decode($res['body'], true);
        $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $decoded = self::cleanAndDecodeJson($rawText);

        if (!$decoded) {
            throw new \RuntimeException("Gemini : La réponse n'est pas un JSON valide.");
        }

        return $decoded;
    }

    /**
     * Appel à OpenAI ChatGPT (REST API)
     */
    private static function callOpenAI(string $apiKey, string $model, array $dict, string $context): array
    {
        $systemPrompt = self::buildSystemPrompt($context);
        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => json_encode($dict, JSON_UNESCAPED_UNICODE)]
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.2
        ];

        $url = 'https://api.openai.com/v1/chat/completions';
        $res = self::httpPostJson($url, $payload, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . trim($apiKey)
        ]);

        if ($res['http_code'] !== 200) {
            $errBody = json_decode($res['body'] ?? '', true);
            $errMsg = $errBody['error']['message'] ?? ('Erreur HTTP OpenAI ' . $res['http_code']);
            throw new \RuntimeException("OpenAI API : " . $errMsg);
        }

        $data = json_decode($res['body'], true);
        $rawText = $data['choices'][0]['message']['content'] ?? '';
        $decoded = self::cleanAndDecodeJson($rawText);

        if (!$decoded) {
            throw new \RuntimeException("OpenAI : La réponse n'est pas un JSON valide.");
        }

        return $decoded;
    }

    /**
     * Appel à Anthropic Claude (REST API)
     */
    private static function callClaude(string $apiKey, string $model, array $dict, string $context): array
    {
        $systemPrompt = self::buildSystemPrompt($context);
        $payload = [
            'model' => $model,
            'max_tokens' => 2048,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => "Traduis cet objet JSON :\n" . json_encode($dict, JSON_UNESCAPED_UNICODE)]
            ],
            'temperature' => 0.2
        ];

        $url = 'https://api.anthropic.com/v1/messages';
        $res = self::httpPostJson($url, $payload, [
            'Content-Type: application/json',
            'x-api-key: ' . trim($apiKey),
            'anthropic-version: 2023-06-01'
        ]);

        if ($res['http_code'] !== 200) {
            $errBody = json_decode($res['body'] ?? '', true);
            $errMsg = $errBody['error']['message'] ?? ('Erreur HTTP Claude ' . $res['http_code']);
            throw new \RuntimeException("Claude API : " . $errMsg);
        }

        $data = json_decode($res['body'], true);
        $rawText = $data['content'][0]['text'] ?? '';
        $decoded = self::cleanAndDecodeJson($rawText);

        if (!$decoded) {
            throw new \RuntimeException("Claude : La réponse n'est pas un JSON valide.");
        }

        return $decoded;
    }

    /**
     * Nettoyage des balises Markdown et décodage JSON robuste
     */
    private static function cleanAndDecodeJson(string $raw): ?array
    {
        $clean = trim($raw);
        if (str_starts_with($clean, '```')) {
            $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
            $clean = preg_replace('/\s*```$/', '', $clean);
            $clean = trim((string)$clean);
        }
        $decoded = json_decode($clean, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Requête HTTP POST JSON via cURL
     */
    private static function httpPostJson(string $url, array $payload, array $headers): array
    {
        $ch = curl_init($url);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $body = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            throw new \RuntimeException("Erreur réseau cURL : " . $curlError);
        }

        return [
            'http_code' => $httpCode,
            'body'      => (string)$body
        ];
    }
}
