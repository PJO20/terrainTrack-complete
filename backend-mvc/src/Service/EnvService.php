<?php

namespace App\Service;

class EnvService
{
    private static $env = [];
    private static $loaded = false;

    public static function load(string $envPath = null): void
    {
        if (self::$loaded) {
            return; // Évite le double chargement
        }

        $envPath = $envPath ?? __DIR__ . '/../../.env';
        
        if (!file_exists($envPath)) {
            // En production, on peut avoir un .env.local ou pas de .env du tout
            if (self::get('APP_ENV', 'development') === 'production') {
                return; // Les variables sont dans l'environnement serveur
            }
            throw new \Exception(".env file not found at $envPath");
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Ignorer les commentaires
            if (strpos($line, '#') === 0 || empty($line)) {
                continue;
            }

            // Gérer les valeurs avec quotes
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'"); // Enlever quotes

                self::$env[$key] = $value;
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
        
        self::$loaded = true;
    }

    public static function get(string $key, $default = null)
    {
        return self::$env[$key] ?? getenv($key) ?? $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $value = self::get($key, $default);
        if (is_bool($value)) {
            return $value;
        }
        return in_array(strtolower($value), ['true', '1', 'yes', 'on']);
    }

    public static function getInt(string $key, int $default = 0): int
    {
        return (int) self::get($key, $default);
    }
}