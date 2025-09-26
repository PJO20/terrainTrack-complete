<?php

namespace App\Service;

class EnvService
{
    private static array $env = [];
    private static bool $loaded = false;

    /**
     * Charge les variables d'environnement depuis le fichier .env
     */
    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $envFile = __DIR__ . '/../../.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                // Ignorer les commentaires
                if (strpos($line, '#') === 0) {
                    continue;
                }
                
                // Parser les variables d'environnement
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Supprimer les guillemets si présents
                    if (($value[0] ?? '') === '"' && ($value[-1] ?? '') === '"') {
                        $value = substr($value, 1, -1);
                    }
                    
                    self::$env[$key] = $value;
                }
            }
        }
        
        self::$loaded = true;
    }

    /**
     * Récupère une variable d'environnement
     */
    public static function get(string $key, $default = null)
    {
        self::load();
        return self::$env[$key] ?? $default;
    }

    /**
     * Récupère une variable d'environnement comme entier
     */
    public static function getInt(string $key, int $default = 0): int
    {
        $value = self::get($key, $default);
        return is_numeric($value) ? (int)$value : $default;
    }

    /**
     * Récupère une variable d'environnement comme booléen
     */
    public static function getBool(string $key, bool $default = false): bool
    {
        $value = self::get($key, $default);
        
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'yes', 'on']);
        }
        
        return (bool)$value;
    }

    /**
     * Récupère une variable d'environnement comme tableau
     */
    public static function getArray(string $key, array $default = []): array
    {
        $value = self::get($key, $default);
        
        if (is_array($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            return explode(',', $value);
        }
        
        return $default;
    }

    /**
     * Définit une variable d'environnement
     */
    public static function set(string $key, $value): void
    {
        self::load();
        self::$env[$key] = $value;
    }

    /**
     * Vérifie si une variable d'environnement existe
     */
    public static function has(string $key): bool
    {
        self::load();
        return isset(self::$env[$key]);
    }

    /**
     * Récupère toutes les variables d'environnement
     */
    public static function all(): array
    {
        self::load();
        return self::$env;
    }
}