<?php

namespace App\Repository;

use PDO;

class AppearanceSettingsRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Récupère les paramètres d'apparence d'un utilisateur
     */
    public function findByUserId(int $userId): ?array
    {
        $query = "SELECT * FROM appearance_settings WHERE user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Met à jour les paramètres d'apparence d'un utilisateur
     */
    public function updateAppearance(int $userId, array $data): bool
    {
        try {
            $query = "UPDATE appearance_settings SET 
                        theme = :theme,
                        primary_color = :primary_color,
                        font_size = :font_size,
                        animations_enabled = :animations_enabled,
                        high_contrast = :high_contrast,
                        reduced_motion = :reduced_motion,
                        updated_at = CURRENT_TIMESTAMP
                      WHERE user_id = :user_id";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                'user_id' => $userId,
                'theme' => $data['theme'] ?? 'light',
                'primary_color' => $data['primary_color'] ?? 'blue',
                'font_size' => $data['font_size'] ?? 'medium',
                'animations_enabled' => isset($data['animations_enabled']) ? 1 : 0,
                'high_contrast' => isset($data['high_contrast']) ? 1 : 0,
                'reduced_motion' => isset($data['reduced_motion']) ? 1 : 0
            ]);
            
            if ($result && $stmt->rowCount() > 0) {
                error_log("Paramètres apparence utilisateur ID $userId mis à jour avec succès");
                return true;
            }
            
            // Si aucune ligne n'a été affectée, créer un nouvel enregistrement
            if ($stmt->rowCount() === 0) {
                return $this->createAppearanceSettings($userId, $data);
            }
            
            return false;
        } catch (\PDOException $e) {
            error_log("Erreur dans AppearanceSettingsRepository::updateAppearance : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crée de nouveaux paramètres d'apparence
     */
    private function createAppearanceSettings(int $userId, array $data): bool
    {
        try {
            $query = "INSERT INTO appearance_settings (
                        user_id, theme, primary_color, font_size,
                        animations_enabled, high_contrast, reduced_motion
                      ) VALUES (
                        :user_id, :theme, :primary_color, :font_size,
                        :animations_enabled, :high_contrast, :reduced_motion
                      )";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                'user_id' => $userId,
                'theme' => $data['theme'] ?? 'light',
                'primary_color' => $data['primary_color'] ?? 'blue',
                'font_size' => $data['font_size'] ?? 'medium',
                'animations_enabled' => isset($data['animations_enabled']) ? 1 : 0,
                'high_contrast' => isset($data['high_contrast']) ? 1 : 0,
                'reduced_motion' => isset($data['reduced_motion']) ? 1 : 0
            ]);
            
            if ($result) {
                error_log("Nouveaux paramètres apparence utilisateur ID $userId créés avec succès");
                return true;
            }
            
            return false;
        } catch (\PDOException $e) {
            error_log("Erreur dans AppearanceSettingsRepository::createAppearanceSettings : " . $e->getMessage());
            return false;
        }
    }
} 