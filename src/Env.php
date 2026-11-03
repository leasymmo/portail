<?php
/**
 * ==============================================================================
 * GESTIONNAIRE D'ENVIRONNEMENT AUTONOME (ZERO DÉPENDANCE COMPOSER)
 * Conciergerie Carré d'Hôtes - Portail Voyageurs
 * ==============================================================================
 */

namespace Cdh\Portal;

// Polyfills de compatibilité pour PHP < 8.0
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        return $needle === '' || $needle === substr($haystack, -strlen($needle));
    }
}

class Env
{
    private static bool $loaded = false;

    /**
     * Charge le fichier .env depuis les emplacements candidats
     */
    public static function load(?string $filePath = null): bool
    {
        if (self::$loaded && $filePath === null) {
            return true;
        }

        if ($filePath === null) {
            $appRoot = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__);
            $candidates = [
                $appRoot . '/.env',
                dirname($appRoot) . '/.env'
            ];
            foreach ($candidates as $cand) {
                if (file_exists($cand)) {
                    $filePath = $cand;
                    break;
                }
            }
        }

        if (!$filePath || !file_exists($filePath)) {
            return false;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return false;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }

                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv("{$name}={$value}");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }

        self::$loaded = true;
        return true;
    }

    /**
     * Récupère la valeur d'une variable d'environnement avec conversion de type
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();

        $value = getenv($key);
        if ($value === false) {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }

        if (is_string($value)) {
            $lower = strtolower($value);
            if ($lower === 'true') return true;
            if ($lower === 'false') return false;
            if ($lower === 'null') return null;
        }

        return $value;
    }
}
