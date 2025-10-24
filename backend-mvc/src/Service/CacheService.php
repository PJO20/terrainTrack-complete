<?php

namespace App\Service;

use PDO;

class CacheService
{
    private PDO $pdo;
    private string $cacheDir;
    private int $defaultTtl;
    
    public function __construct(PDO $pdo, string $cacheDir = null, int $defaultTtl = 3600)
    {
        $this->pdo = $pdo;
        $this->cacheDir = $cacheDir ?: __DIR__ . '/../../var/cache';
        $this->defaultTtl = $defaultTtl;
        
        // Créer le répertoire de cache s'il n'existe pas
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Active le cache pour un utilisateur
     */
    public function enableCache(int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO system_settings (user_id, setting_key, setting_value) 
                VALUES (?, 'cache_enabled', 'true')
                ON DUPLICATE KEY UPDATE setting_value = 'true', updated_at = CURRENT_TIMESTAMP
            ");
            return $stmt->execute([$userId]);
        } catch (\Exception $e) {
            error_log("Erreur activation cache: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Désactive le cache pour un utilisateur
     */
    public function disableCache(int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE system_settings 
                SET setting_value = 'false', updated_at = CURRENT_TIMESTAMP
                WHERE user_id = ? AND setting_key = 'cache_enabled'
            ");
            $result = $stmt->execute([$userId]);
            
            // Nettoyer le cache local
            $this->clearUserCache($userId);
            
            return $result;
        } catch (\Exception $e) {
            error_log("Erreur désactivation cache: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifie si le cache est activé pour un utilisateur
     */
    public function isCacheEnabled(int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT setting_value 
                FROM system_settings 
                WHERE user_id = ? AND setting_key = 'cache_enabled'
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return $result && $result['setting_value'] === 'true';
        } catch (\Exception $e) {
            error_log("Erreur vérification cache: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Met en cache une donnée
     */
    public function set(string $key, $data, int $ttl = null): bool
    {
        try {
            $ttl = $ttl ?: $this->defaultTtl;
            $expires = time() + $ttl;
            
            $cacheData = [
                'data' => $data,
                'expires' => $expires,
                'created' => time()
            ];
            
            $cacheFile = $this->getCacheFile($key);
            return file_put_contents($cacheFile, json_encode($cacheData)) !== false;
            
        } catch (\Exception $e) {
            error_log("Erreur mise en cache: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère une donnée du cache
     */
    public function get(string $key)
    {
        try {
            $cacheFile = $this->getCacheFile($key);
            
            if (!file_exists($cacheFile)) {
                return null;
            }
            
            $content = file_get_contents($cacheFile);
            $cacheData = json_decode($content, true);
            
            if (!$cacheData) {
                return null;
            }
            
            // Vérifier l'expiration
            if (time() > $cacheData['expires']) {
                unlink($cacheFile);
                return null;
            }
            
            return $cacheData['data'];
            
        } catch (\Exception $e) {
            error_log("Erreur récupération cache: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Supprime une donnée du cache
     */
    public function delete(string $key): bool
    {
        try {
            $cacheFile = $this->getCacheFile($key);
            if (file_exists($cacheFile)) {
                return unlink($cacheFile);
            }
            return true;
        } catch (\Exception $e) {
            error_log("Erreur suppression cache: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Nettoie le cache d'un utilisateur
     */
    public function clearUserCache(int $userId): bool
    {
        try {
            $userCacheDir = $this->cacheDir . '/user_' . $userId;
            if (is_dir($userCacheDir)) {
                $this->deleteDirectory($userCacheDir);
            }
            return true;
        } catch (\Exception $e) {
            error_log("Erreur nettoyage cache utilisateur: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Nettoie tout le cache
     */
    public function clearAllCache(): bool
    {
        try {
            $this->deleteDirectory($this->cacheDir);
            mkdir($this->cacheDir, 0755, true);
            return true;
        } catch (\Exception $e) {
            error_log("Erreur nettoyage cache complet: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Met en cache les données utilisateur
     */
    public function cacheUserData(int $userId, array $userData): bool
    {
        if (!$this->isCacheEnabled($userId)) {
            return false;
        }
        
        return $this->set("user_data_$userId", $userData, 7200); // 2 heures
    }
    
    /**
     * Récupère les données utilisateur du cache
     */
    public function getCachedUserData(int $userId): ?array
    {
        if (!$this->isCacheEnabled($userId)) {
            return null;
        }
        
        return $this->get("user_data_$userId");
    }
    
    /**
     * Met en cache les interventions
     */
    public function cacheInterventions(int $userId, array $interventions): bool
    {
        if (!$this->isCacheEnabled($userId)) {
            return false;
        }
        
        return $this->set("interventions_$userId", $interventions, 1800); // 30 minutes
    }
    
    /**
     * Récupère les interventions du cache
     */
    public function getCachedInterventions(int $userId): ?array
    {
        if (!$this->isCacheEnabled($userId)) {
            return null;
        }
        
        return $this->get("interventions_$userId");
    }
    
    /**
     * Met en cache les véhicules
     */
    public function cacheVehicles(array $vehicles): bool
    {
        return $this->set("vehicles_all", $vehicles, 3600); // 1 heure
    }
    
    /**
     * Récupère les véhicules du cache
     */
    public function getCachedVehicles(): ?array
    {
        return $this->get("vehicles_all");
    }
    
    /**
     * Met en cache les techniciens
     */
    public function cacheTechnicians(array $technicians): bool
    {
        return $this->set("technicians_all", $technicians, 3600); // 1 heure
    }
    
    /**
     * Récupère les techniciens du cache
     */
    public function getCachedTechnicians(): ?array
    {
        return $this->get("technicians_all");
    }
    
    /**
     * Optimise les performances en pré-chargeant les données
     */
    public function preloadData(int $userId): bool
    {
        try {
            if (!$this->isCacheEnabled($userId)) {
                return false;
            }
            
            // Pré-charger les données utilisateur
            $userData = $this->getUserDataFromDatabase($userId);
            $this->cacheUserData($userId, $userData);
            
            // Pré-charger les interventions
            $interventions = $this->getInterventionsFromDatabase($userId);
            $this->cacheInterventions($userId, $interventions);
            
            // Pré-charger les véhicules
            $vehicles = $this->getVehiclesFromDatabase();
            $this->cacheVehicles($vehicles);
            
            // Pré-charger les techniciens
            $technicians = $this->getTechniciansFromDatabase();
            $this->cacheTechnicians($technicians);
            
            return true;
            
        } catch (\Exception $e) {
            error_log("Erreur pré-chargement: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère les statistiques du cache
     */
    public function getCacheStats(): array
    {
        try {
            $stats = [
                'total_files' => 0,
                'total_size' => 0,
                'oldest_file' => null,
                'newest_file' => null
            ];
            
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->cacheDir)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $stats['total_files']++;
                    $stats['total_size'] += $file->getSize();
                    
                    $mtime = $file->getMTime();
                    if (!$stats['oldest_file'] || $mtime < $stats['oldest_file']) {
                        $stats['oldest_file'] = $mtime;
                    }
                    if (!$stats['newest_file'] || $mtime > $stats['newest_file']) {
                        $stats['newest_file'] = $mtime;
                    }
                }
            }
            
            return $stats;
            
        } catch (\Exception $e) {
            error_log("Erreur statistiques cache: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Génère le chemin du fichier de cache
     */
    private function getCacheFile(string $key): string
    {
        $hash = md5($key);
        $subDir = substr($hash, 0, 2);
        $dir = $this->cacheDir . '/' . $subDir;
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        return $dir . '/' . $hash . '.json';
    }
    
    /**
     * Supprime récursivement un répertoire
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return true;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        return rmdir($dir);
    }
    
    /**
     * Récupère les données utilisateur depuis la base de données
     */
    private function getUserDataFromDatabase(int $userId): array
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
     * Récupère les interventions depuis la base de données
     */
    private function getInterventionsFromDatabase(int $userId): array
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
     * Récupère les véhicules depuis la base de données
     */
    private function getVehiclesFromDatabase(): array
    {
        $stmt = $this->pdo->query("
            SELECT id, name, plate_number, type, status, location
            FROM vehicles 
            ORDER BY name
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Récupère les techniciens depuis la base de données
     */
    private function getTechniciansFromDatabase(): array
    {
        $stmt = $this->pdo->query("
            SELECT id, name, email, phone, department, status
            FROM users 
            WHERE role IN ('technician', 'admin', 'super_admin')
            ORDER BY name
        ");
        return $stmt->fetchAll();
    }
}