<?php

namespace App\Repository;

use PDO;

class SystemSettingsRepository
{
    private PDO $pdo;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    /**
     * Récupère tous les paramètres système d'un utilisateur
     */
    public function getUserSettings(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT setting_key, setting_value 
            FROM system_settings 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    }
    
    /**
     * Met à jour un paramètre système
     */
    public function updateSetting(int $userId, string $key, string $value): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO system_settings (user_id, setting_key, setting_value) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                updated_at = CURRENT_TIMESTAMP
            ");
            return $stmt->execute([$userId, $key, $value]);
        } catch (\Exception $e) {
            error_log("Erreur mise à jour paramètre système: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Met à jour plusieurs paramètres système
     */
    public function updateSettings(int $userId, array $settings): bool
    {
        try {
            $this->pdo->beginTransaction();
            
            foreach ($settings as $key => $value) {
                $this->updateSetting($userId, $key, $value);
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            error_log("Erreur mise à jour paramètres système: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère un paramètre spécifique
     */
    public function getSetting(int $userId, string $key, string $default = null): ?string
    {
        $stmt = $this->pdo->prepare("
            SELECT setting_value 
            FROM system_settings 
            WHERE user_id = ? AND setting_key = ?
        ");
        $stmt->execute([$userId, $key]);
        $result = $stmt->fetch();
        
        return $result ? $result['setting_value'] : $default;
    }
    
    /**
     * Supprime un paramètre
     */
    public function deleteSetting(int $userId, string $key): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM system_settings 
                WHERE user_id = ? AND setting_key = ?
            ");
            return $stmt->execute([$userId, $key]);
        } catch (\Exception $e) {
            error_log("Erreur suppression paramètre: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Initialise les paramètres par défaut pour un utilisateur
     */
    public function initializeDefaultSettings(int $userId): bool
    {
        $defaultSettings = [
            'offline_mode' => 'false',
            'auto_save' => 'true',
            'cache_enabled' => 'true'
        ];
        
        return $this->updateSettings($userId, $defaultSettings);
    }
}
