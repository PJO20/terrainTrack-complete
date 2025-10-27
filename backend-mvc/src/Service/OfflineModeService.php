<?php

namespace App\Service;

use PDO;

class OfflineModeService
{
    private PDO $pdo;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    /**
     * Active le mode hors-ligne pour un utilisateur
     */
    public function enableOfflineMode(int $userId): bool
    {
        try {
            $this->pdo->beginTransaction();
            
            // Mettre à jour le paramètre offline_mode
            $stmt = $this->pdo->prepare("
                INSERT INTO system_settings (user_id, setting_key, setting_value) 
                VALUES (?, 'offline_mode', 'true')
                ON DUPLICATE KEY UPDATE setting_value = 'true', updated_at = CURRENT_TIMESTAMP
            ");
            $result = $stmt->execute([$userId]);
            
            // Créer un cache local des données essentielles
            $this->createOfflineCache($userId);
            
            $this->pdo->commit();
            return $result;
            
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            error_log("Erreur activation mode hors-ligne: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Désactive le mode hors-ligne pour un utilisateur
     */
    public function disableOfflineMode(int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE system_settings 
                SET setting_value = 'false', updated_at = CURRENT_TIMESTAMP
                WHERE user_id = ? AND setting_key = 'offline_mode'
            ");
            $result = $stmt->execute([$userId]);
            
            // Nettoyer le cache hors-ligne
            $this->clearOfflineCache($userId);
            
            return $result;
            
        } catch (\Exception $e) {
            error_log("Erreur désactivation mode hors-ligne: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifie si le mode hors-ligne est activé pour un utilisateur
     */
    public function isOfflineModeEnabled(int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT setting_value 
                FROM system_settings 
                WHERE user_id = ? AND setting_key = 'offline_mode'
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return $result && $result['setting_value'] === 'true';
            
        } catch (\Exception $e) {
            error_log("Erreur vérification mode hors-ligne: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crée un cache local des données essentielles pour le mode hors-ligne
     */
    private function createOfflineCache(int $userId): void
    {
        try {
            // Données utilisateur
            $userData = $this->getUserData($userId);
            
            // Données des interventions
            $interventions = $this->getInterventionsData($userId);
            
            // Données des véhicules
            $vehicles = $this->getVehiclesData();
            
            // Données des techniciens
            $technicians = $this->getTechniciansData();
            
            // Créer le cache JSON
            $cacheData = [
                'user' => $userData,
                'interventions' => $interventions,
                'vehicles' => $vehicles,
                'technicians' => $technicians,
                'created_at' => date('Y-m-d H:i:s'),
                'offline_mode' => true
            ];
            
            // Sauvegarder dans un fichier local
            $cacheDir = __DIR__ . '/../../var/cache/offline';
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
            
            $cacheFile = $cacheDir . '/user_' . $userId . '_offline.json';
            file_put_contents($cacheFile, json_encode($cacheData, JSON_PRETTY_PRINT));
            
            error_log("Cache hors-ligne créé pour l'utilisateur $userId");
            
        } catch (\Exception $e) {
            error_log("Erreur création cache hors-ligne: " . $e->getMessage());
        }
    }
    
    /**
     * Nettoie le cache hors-ligne
     */
    private function clearOfflineCache(int $userId): void
    {
        try {
            $cacheFile = __DIR__ . '/../../var/cache/offline/user_' . $userId . '_offline.json';
            if (file_exists($cacheFile)) {
                unlink($cacheFile);
                error_log("Cache hors-ligne supprimé pour l'utilisateur $userId");
            }
        } catch (\Exception $e) {
            error_log("Erreur suppression cache hors-ligne: " . $e->getMessage());
        }
    }
    
    /**
     * Récupère les données utilisateur
     */
    private function getUserData(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, email, name, phone, location, department, role, timezone, language
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: [];
    }
    
    /**
     * Récupère les données des interventions
     */
    private function getInterventionsData(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT i.*, v.name as vehicle_name, v.plate_number
            FROM interventions i
            LEFT JOIN vehicles v ON i.vehicle_id = v.id
            WHERE i.created_by = ? OR i.assigned_to = ?
            ORDER BY i.created_at DESC
            LIMIT 100
        ");
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Récupère les données des véhicules
     */
    private function getVehiclesData(): array
    {
        $stmt = $this->pdo->query("
            SELECT id, name, plate_number, type, status, location
            FROM vehicles 
            WHERE status = 'available'
            ORDER BY name
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Récupère les données des techniciens
     */
    private function getTechniciansData(): array
    {
        $stmt = $this->pdo->query("
            SELECT id, name, email, phone, department, status
            FROM users 
            WHERE role IN ('technician', 'admin', 'super_admin')
            AND status = 'active'
            ORDER BY name
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Récupère le cache hors-ligne pour un utilisateur
     */
    public function getOfflineCache(int $userId): ?array
    {
        try {
            $cacheFile = __DIR__ . '/../../var/cache/offline/user_' . $userId . '_offline.json';
            if (file_exists($cacheFile)) {
                $content = file_get_contents($cacheFile);
                return json_decode($content, true);
            }
            return null;
        } catch (\Exception $e) {
            error_log("Erreur lecture cache hors-ligne: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Synchronise les données hors-ligne avec le serveur
     */
    public function syncOfflineData(int $userId, array $offlineData): bool
    {
        try {
            $this->pdo->beginTransaction();
            
            // Traiter les interventions créées hors-ligne
            if (isset($offlineData['new_interventions'])) {
                foreach ($offlineData['new_interventions'] as $intervention) {
                    $this->syncIntervention($intervention);
                }
            }
            
            // Traiter les modifications hors-ligne
            if (isset($offlineData['modified_interventions'])) {
                foreach ($offlineData['modified_interventions'] as $intervention) {
                    $this->updateIntervention($intervention);
                }
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            error_log("Erreur synchronisation données hors-ligne: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Synchronise une intervention créée hors-ligne
     */
    private function syncIntervention(array $intervention): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO interventions (
                title, description, type, status, priority, 
                vehicle_id, created_by, assigned_to, 
                scheduled_date, location, notes, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $intervention['title'],
            $intervention['description'],
            $intervention['type'],
            $intervention['status'] ?? 'pending',
            $intervention['priority'] ?? 'medium',
            $intervention['vehicle_id'],
            $intervention['created_by'],
            $intervention['assigned_to'],
            $intervention['scheduled_date'],
            $intervention['location'],
            $intervention['notes'],
            $intervention['created_at']
        ]);
    }
    
    /**
     * Met à jour une intervention modifiée hors-ligne
     */
    private function updateIntervention(array $intervention): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE interventions 
            SET title = ?, description = ?, type = ?, status = ?, 
                priority = ?, vehicle_id = ?, assigned_to = ?, 
                scheduled_date = ?, location = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $stmt->execute([
            $intervention['title'],
            $intervention['description'],
            $intervention['type'],
            $intervention['status'],
            $intervention['priority'],
            $intervention['vehicle_id'],
            $intervention['assigned_to'],
            $intervention['scheduled_date'],
            $intervention['location'],
            $intervention['notes'],
            $intervention['id']
        ]);
    }
}

