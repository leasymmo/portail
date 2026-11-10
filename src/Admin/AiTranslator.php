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
        self::PROVIDER_GEMINI => 'gemini-3.5-flash',
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
     * Interroge l'API de l'IA pour lister dynamiquement les modèles disponibles
     */
    public static function listAvailableModels(string $provider, string $apiKey = ''): array
    {
        $provider = strtolower(trim($provider));
        if ($provider === self::PROVIDER_GEMINI) {
            return self::listGeminiModels($apiKey);
        } elseif ($provider === self::PROVIDER_OPENAI) {
            return self::listOpenAiModels($apiKey);
        } elseif ($provider === self::PROVIDER_CLAUDE) {
            return self::listClaudeModels($apiKey);
        }
        return [];
    }

    private static function listGeminiModels(string $apiKey): array
    {
        $defaultList = [
            ['id' => 'gemini-3.5-flash', 'name' => 'Gemini 3.5 Flash (Recommandé)', 'recommended' => true],
            ['id' => 'gemini-3.8-flash', 'name' => 'Gemini 3.8 Flash', 'recommended' => false],
            ['id' => 'gemini-3-flash-preview', 'name' => 'Gemini 3 Flash Preview', 'recommended' => false],
            ['id' => 'gemini-flash-latest', 'name' => 'Gemini Flash Latest', 'recommended' => false],
            ['id' => 'gemini-3.5-flash-lite', 'name' => 'Gemini 3.5 Flash Lite', 'recommended' => false],
            ['id' => 'gemini-2.5-pro', 'name' => 'Gemini 2.5 Pro', 'recommended' => false],
        ];

        if (empty($apiKey)) {
            return $defaultList;
        }

        try {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . urlencode($apiKey);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 6,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_SSL_VERIFYPEER => true
            ]);
            $body = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $body) {
                $data = json_decode((string)$body, true);
                if (!empty($data['models']) && is_array($data['models'])) {
                    $models = [];
                    foreach ($data['models'] as $m) {
                        $methods = $m['supportedGenerationMethods'] ?? [];
                        if (!in_array('generateContent', $methods, true)) {
                            continue;
                        }
                        $id = str_replace('models/', '', $m['name'] ?? '');
                        if (preg_match('/(tts|robotics|banana|argon|lyria|transcribe|computer-use|antigravity|embedding)/i', $id)) {
                            continue;
                        }
                        $displayName = $m['displayName'] ?? $id;
                        $isRecommended = ($id === 'gemini-3.5-flash');
                        $models[] = [
                            'id' => $id,
                            'name' => $displayName . ($isRecommended ? ' (Recommandé)' : ''),
                            'recommended' => $isRecommended,
                            'is_flash' => (strpos($id, 'flash') !== false)
                        ];
                    }

                    if (!empty($models)) {
                        usort($models, function ($a, $b) {
                            if ($a['recommended'] !== $b['recommended']) {
                                return $a['recommended'] ? -1 : 1;
                            }
                            if ($a['is_flash'] !== $b['is_flash']) {
                                return $a['is_flash'] ? -1 : 1;
                            }
                            return strnatcasecmp($b['id'], $a['id']);
                        });
                        return $models;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('[AiTranslator] listGeminiModels error: ' . $e->getMessage());
        }

        return $defaultList;
    }

    private static function listOpenAiModels(string $apiKey): array
    {
        $defaultList = [
            ['id' => 'gpt-4o-mini', 'name' => 'GPT-4o Mini (Recommandé - Rapide & économique)', 'recommended' => true],
            ['id' => 'gpt-4o', 'name' => 'GPT-4o (Haut de gamme)', 'recommended' => false],
            ['id' => 'o3-mini', 'name' => 'o3-mini (Raisonnement avancé)', 'recommended' => false],
            ['id' => 'gpt-3.5-turbo', 'name' => 'GPT-3.5 Turbo', 'recommended' => false],
        ];

        if (empty($apiKey)) {
            return $defaultList;
        }

        try {
            $url = 'https://api.openai.com/v1/models';
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . trim($apiKey)],
                CURLOPT_TIMEOUT        => 6,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_SSL_VERIFYPEER => true
            ]);
            $body = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $body) {
                $data = json_decode((string)$body, true);
                if (!empty($data['data']) && is_array($data['data'])) {
                    $models = [];
                    foreach ($data['data'] as $m) {
                        $id = $m['id'] ?? '';
                        if (!preg_match('/^(gpt-4|o1|o3|chatgpt|gpt-3\.5)/i', $id)) {
                            continue;
                        }
                        if (preg_match('/(audio|realtime|transcribe|moderation|embedding|tts|instruct|vision-preview)/i', $id)) {
                            continue;
                        }
                        $isRecommended = ($id === 'gpt-4o-mini');
                        $models[] = [
                            'id' => $id,
                            'name' => $id . ($isRecommended ? ' (Recommandé)' : ''),
                            'recommended' => $isRecommended
                        ];
                    }

                    if (!empty($models)) {
                        usort($models, function ($a, $b) {
                            if ($a['recommended'] !== $b['recommended']) {
                                return $a['recommended'] ? -1 : 1;
                            }
                            return strnatcasecmp($a['id'], $b['id']);
                        });
                        return $models;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('[AiTranslator] listOpenAiModels error: ' . $e->getMessage());
        }

        return $defaultList;
    }

    private static function listClaudeModels(string $apiKey): array
    {
        $defaultList = [
            ['id' => 'claude-3-5-haiku-latest', 'name' => 'Claude 3.5 Haiku (Recommandé - Rapide & précis)', 'recommended' => true],
            ['id' => 'claude-3-5-sonnet-latest', 'name' => 'Claude 3.5 Sonnet (Qualité maximale)', 'recommended' => false],
            ['id' => 'claude-3-opus-latest', 'name' => 'Claude 3 Opus', 'recommended' => false],
            ['id' => 'claude-3-haiku-20240307', 'name' => 'Claude 3 Haiku (20240307)', 'recommended' => false],
        ];

        if (empty($apiKey)) {
            return $defaultList;
        }

        try {
            $url = 'https://api.anthropic.com/v1/models';
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    'x-api-key: ' . trim($apiKey),
                    'anthropic-version: 2023-06-01'
                ],
                CURLOPT_TIMEOUT        => 6,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_SSL_VERIFYPEER => true
            ]);
            $body = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $body) {
                $data = json_decode((string)$body, true);
                if (!empty($data['data']) && is_array($data['data'])) {
                    $models = [];
                    foreach ($data['data'] as $m) {
                        $id = $m['id'] ?? '';
                        $displayName = $m['display_name'] ?? $id;
                        $isRecommended = (strpos($id, '3-5-haiku') !== false);
                        $models[] = [
                            'id' => $id,
                            'name' => $displayName . ($isRecommended ? ' (Recommandé)' : ''),
                            'recommended' => $isRecommended
                        ];
                    }
                    if (!empty($models)) {
                        usort($models, function ($a, $b) {
                            if ($a['recommended'] !== $b['recommended']) {
                                return $a['recommended'] ? -1 : 1;
                            }
                            return strnatcasecmp($a['id'], $b['id']);
                        });
                        return $models;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('[AiTranslator] listClaudeModels error: ' . $e->getMessage());
        }

        return $defaultList;
    }

    /**
     * Traduit les champs de règles générales de stationnement
     * Reçoit le tableau complet (avec champs *_fr) et remplit les versions *_en
     * Si $force = false, les champs *_en déjà renseignés (ex: vérifiés/modifiés manuellement) sont préservés.
     */
    public static function translateParkingGeneral(PDO $db, array $data, bool $force = false, bool $throwOnError = false): array
    {
        $fieldsToTranslate = [
            'intro'                  => $data['intro_fr'] ?? null,
            'important_rule'         => $data['important_rule_fr'] ?? null,
            'free_parking_notes'     => $data['free_parking_notes_fr'] ?? null,
            'street_parking_info'    => $data['street_parking_info_fr'] ?? null,
            'street_parking_schedule'=> $data['street_parking_schedule_fr'] ?? null,
            'street_parking_rates'   => $data['street_parking_rates_fr'] ?? null,
            'tcl_conditions'         => $data['tcl_conditions_fr'] ?? null,
        ];

        $dict = [];
        foreach ($fieldsToTranslate as $key => $val) {
            $enKey = $key . '_en';
            // Si non forcé et déjà renseigné, on conserve la valeur existante
            if (!$force && !empty($data[$enKey]) && trim((string)$data[$enKey]) !== '') {
                continue;
            }
            if ($val !== null && trim((string)$val) !== '') {
                $dict[$key] = trim((string)$val);
            }
        }

        if (empty($dict)) {
            return $data;
        }

        $translated = self::translateDictWithDbConfig($db, $dict, 'Règles générales de stationnement', $throwOnError);
        if (!empty($translated)) {
            foreach ($translated as $key => $enVal) {
                $enKey = $key . '_en';
                $data[$enKey] = $enVal;
            }
        }

        return $data;
    }

    /**
     * Traduit les champs d'un lieu de stationnement selon sa catégorie
     * (Rues gratuites, parking privé ou P+R TCL)
     * Reçoit le tableau (avec champs *_fr) et remplit les versions *_en
     * Si $force = false, les champs *_en déjà renseignés sont préservés.
     */
    public static function translateParkingLocation(PDO $db, array $data, bool $force = false, bool $throwOnError = false): array
    {
        $category = $data['category'] ?? 'free_street';

        // Sélection ciblée des champs physiques du parking dans le catalogue (indépendants des hébergements)
        if ($category === 'private_parking') {
            $fieldsToTranslate = [
                'name'              => $data['name_fr'] ?? null,
                'opening_hours'     => $data['opening_hours_fr'] ?? null,
                'rates'             => $data['rates_fr'] ?? null,
                'suitable_vehicles' => $data['suitable_vehicles_fr'] ?? null,
                'notes'             => $data['notes_fr'] ?? null,
            ];
            $context = 'Parking privé couvert (nom, horaires d\'accès, tarifs, gabarit/véhicules adaptés, remarques)';
        } elseif ($category === 'tcl_pr') {
            $fieldsToTranslate = [
                'name'              => $data['name_fr'] ?? null,
                'opening_hours'     => $data['opening_hours_fr'] ?? null,
                'notes'             => $data['notes_fr'] ?? null,
            ];
            $context = 'Parc Relais TCL connecté aux transports en commun (nom officiel, horaires d\'ouverture, remarques)';
        } else {
            // Rues gratuites & Dépose minute
            $fieldsToTranslate = [
                'name'              => $data['name_fr'] ?? null,
                'notes'             => $data['notes_fr'] ?? null,
            ];
            $context = 'Rue ou arrêt gratuit de stationnement (nom de la voie ou du lieu, conseils pratiques)';
        }

        $dict = [];
        foreach ($fieldsToTranslate as $key => $val) {
            $enKey = $key . '_en';
            if (!$force && !empty($data[$enKey]) && trim((string)$data[$enKey]) !== '') {
                continue;
            }
            if ($val !== null && trim((string)$val) !== '') {
                $dict[$key] = trim((string)$val);
            }
        }

        if (empty($dict)) {
            return $data;
        }

        $translated = self::translateDictWithDbConfig($db, $dict, $context, $throwOnError);
        if (!empty($translated)) {
            foreach ($translated as $key => $enVal) {
                $enKey = $key . '_en';
                $data[$enKey] = $enVal;
            }
        }

        return $data;
    }

    /**
     * Traduit les informations de liaison / accès spécifiques entre un logement et un parking
     */
    public static function translateParkingConnection(PDO $db, array $data, bool $force = false, bool $throwOnError = false): array
    {
        $fieldsToTranslate = [
            'walking_time' => $data['walking_time_fr'] ?? null,
            'transit_time' => $data['transit_time_fr'] ?? null,
            'how_to_reach' => $data['how_to_reach_fr'] ?? null,
            'notes'        => $data['notes_fr'] ?? null,
        ];
        $context = 'Informations de trajet et d\'accès entre un logement et un stationnement (temps à pied, temps TCL, itinéraire retour)';

        $dict = [];
        foreach ($fieldsToTranslate as $key => $val) {
            $enKey = $key . '_en';
            if (!$force && !empty($data[$enKey]) && trim((string)$data[$enKey]) !== '') {
                continue;
            }
            if ($val !== null && trim((string)$val) !== '') {
                $dict[$key] = trim((string)$val);
            }
        }

        if (empty($dict)) {
            return $data;
        }

        $translated = self::translateDictWithDbConfig($db, $dict, $context, $throwOnError);
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
    public static function translateDictWithDbConfig(PDO $db, array $dict, string $context = '', bool $throwOnError = false): array
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
                if ($throwOnError) {
                    throw new \RuntimeException("Aucune clé API configurée pour " . strtoupper($provider) . ". Rendez-vous dans la configuration IA pour saisir votre clé.");
                }
                return [];
            }

            return self::callProvider($provider, $apiKey, $model, $dict, $context);
        } catch (Throwable $e) {
            error_log('[AiTranslator] Erreur lors de la traduction : ' . $e->getMessage());
            if ($throwOnError) {
                throw $e;
            }
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

        // Modèles Gemini à tester par ordre de priorité (avec replis successifs)
        $modelsToTry = array_unique([
            $model,
            'gemini-3.5-flash',
            'gemini-3-flash-preview',
            'gemini-3.8-flash',
            'gemini-2.0-flash',
            'gemini-2.5-flash'
        ]);

        $lastError = '';
        foreach ($modelsToTry as $m) {
            if (empty($m)) continue;
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . urlencode($m) . ':generateContent?key=' . urlencode($apiKey);
            try {
                $res = self::httpPostJson($url, $payload, ['Content-Type: application/json']);
                if ($res['http_code'] === 200) {
                    $data = json_decode($res['body'], true);
                    $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    $decoded = self::cleanAndDecodeJson($rawText);
                    if ($decoded) {
                        return $decoded;
                    }
                } else {
                    $errBody = json_decode($res['body'] ?? '', true);
                    $lastError = $errBody['error']['message'] ?? ('Erreur HTTP Gemini ' . $res['http_code']);
                }
            } catch (Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        throw new \RuntimeException("Gemini API : " . ($lastError ?: 'Impossible de générer la traduction.'));
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
