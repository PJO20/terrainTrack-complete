<?php

namespace App\Service;

use App\Service\SessionManager;

class LocalizationService
{
    private static ?array $userSettings = null;
    
    /**
     * Récupère les paramètres de localisation de l'utilisateur connecté
     */
    public static function getUserSettings(): array
    {
        if (self::$userSettings === null) {
            $user = SessionManager::getCurrentUser();
            
            if (!$user) {
                // Valeurs par défaut si pas connecté
                self::$userSettings = [
                    'language' => 'fr',
                    'timezone' => 'Europe/Paris',
                    'date_format' => 'DD/MM/YYYY',
                    'time_format' => '24',
                    'auto_save' => true
                ];
            } else {
                // Récupérer depuis la base de données
                try {
                    $pdo = \App\Service\Database::connect();
                    $stmt = $pdo->prepare("SELECT language, timezone, date_format, time_format, auto_save FROM users WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    $settings = $stmt->fetch(\PDO::FETCH_ASSOC);
                    
                    self::$userSettings = [
                        'language' => $settings['language'] ?? 'fr',
                        'timezone' => $settings['timezone'] ?? 'Europe/Paris',
                        'date_format' => $settings['date_format'] ?? 'DD/MM/YYYY',
                        'time_format' => $settings['time_format'] ?? '24',
                        'auto_save' => (bool)($settings['auto_save'] ?? true)
                    ];
                } catch (\Exception $e) {
                    error_log("Erreur récupération paramètres localisation: " . $e->getMessage());
                    // Valeurs par défaut en cas d'erreur
                    self::$userSettings = [
                        'language' => 'fr',
                        'timezone' => 'Europe/Paris',
                        'date_format' => 'DD/MM/YYYY',
                        'time_format' => '24',
                        'auto_save' => true
                    ];
                }
            }
        }
        
        return self::$userSettings;
    }
    
    /**
     * Formate une date selon les préférences de l'utilisateur
     */
    public static function formatDate(\DateTime $date, ?string $customFormat = null): string
    {
        $settings = self::getUserSettings();
        $format = $customFormat ?? $settings['date_format'];
        
        // Convertir le format utilisateur en format PHP
        $phpFormat = self::convertDateFormat($format);
        
        // Appliquer le fuseau horaire si nécessaire
        if ($settings['timezone'] !== 'Europe/Paris') {
            try {
                $date->setTimezone(new \DateTimeZone($settings['timezone']));
            } catch (\Exception $e) {
                error_log("Erreur fuseau horaire: " . $e->getMessage());
            }
        }
        
        return $date->format($phpFormat);
    }
    
    /**
     * Formate une heure selon les préférences de l'utilisateur
     */
    public static function formatTime(\DateTime $date, ?string $customFormat = null): string
    {
        $settings = self::getUserSettings();
        
        // Appliquer le fuseau horaire si nécessaire
        if ($settings['timezone'] !== 'Europe/Paris') {
            try {
                $date->setTimezone(new \DateTimeZone($settings['timezone']));
            } catch (\Exception $e) {
                error_log("Erreur fuseau horaire: " . $e->getMessage());
            }
        }
        
        if ($customFormat) {
            return $date->format($customFormat);
        }
        
        // Format selon les préférences utilisateur
        if ($settings['time_format'] === '12') {
            return $date->format('g:i A'); // 2:30 PM
        } else {
            return $date->format('H:i'); // 14:30
        }
    }
    
    /**
     * Formate une date et heure complète
     */
    public static function formatDateTime(\DateTime $date, ?string $customFormat = null): string
    {
        $settings = self::getUserSettings();
        
        if ($customFormat) {
            // Appliquer le fuseau horaire si nécessaire
            if ($settings['timezone'] !== 'Europe/Paris') {
                try {
                    $date->setTimezone(new \DateTimeZone($settings['timezone']));
                } catch (\Exception $e) {
                    error_log("Erreur fuseau horaire: " . $e->getMessage());
                }
            }
            return $date->format($customFormat);
        }
        
        $dateStr = self::formatDate($date);
        $timeStr = self::formatTime($date);
        
        return $dateStr . ' ' . $timeStr;
    }
    
    /**
     * Convertit le format utilisateur en format PHP
     */
    private static function convertDateFormat(string $userFormat): string
    {
        $conversions = [
            'DD/MM/YYYY' => 'd/m/Y',
            'MM/DD/YYYY' => 'm/d/Y',
            'YYYY-MM-DD' => 'Y-m-d',
            'DD-MM-YYYY' => 'd-m-Y'
        ];
        
        return $conversions[$userFormat] ?? 'd/m/Y';
    }
    
    /**
     * Force le rechargement des paramètres (utile après modification)
     */
    public static function reloadSettings(): void
    {
        self::$userSettings = null;
    }
    
    /**
     * Récupère la langue de l'utilisateur
     */
    public static function getLanguage(): string
    {
        $settings = self::getUserSettings();
        return $settings['language'];
    }
    
    /**
     * Récupère le fuseau horaire de l'utilisateur
     */
    public static function getTimezone(): string
    {
        $settings = self::getUserSettings();
        return $settings['timezone'];
    }
    
    /**
     * Récupère le format de date de l'utilisateur
     */
    public static function getDateFormat(): string
    {
        $settings = self::getUserSettings();
        return $settings['date_format'];
    }
    
    /**
     * Récupère le format d'heure de l'utilisateur
     */
    public static function getTimeFormat(): string
    {
        $settings = self::getUserSettings();
        return $settings['time_format'];
    }
}

