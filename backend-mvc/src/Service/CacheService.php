<?php

namespace App\Service;

class CacheService
{
    private string $cacheDir;
    private int $defaultTtl;
    
    public function __construct()
    {
        EnvService::load();
        $this->cacheDir = EnvService::get('CACHE_DIR', '/Applications/MAMP/htdocs/exemple/backend-mvc/var/cache');
        $this->defaultTtl = EnvService::getInt('CACHE_DEFAULT_TTL', 3600); // 1 heure
        
        // Créer le dossier cache s'il n'existe pas
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Récupère une valeur depuis le cache
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $cacheFile = $this->getCacheFile($key);
        
        if (!file_exists($cacheFile)) {
            return $default;
        }
        
        $data = file_get_contents($cacheFile);
        if ($data === false) {
            return $default;
        }
        
        $cacheData = unserialize($data);
        if (!$cacheData || !isset($cacheData['expires']) || !isset($cacheData['value'])) {
            return $default;
        }
        
        // Vérifier l'expiration
        if (time() > $cacheData['expires']) {
            $this->delete($key);
            return $default;
        }
        
        return $cacheData['value'];
    }
    
    /**
     * Stocke une valeur dans le cache
     */
    public function set(string $key, mixed $value, int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        $expires = time() + $ttl;
        
        $cacheData = [
            'value' => $value,
            'expires' => $expires,
            'created' => time()
        ];
        
        $cacheFile = $this->getCacheFile($key);
        $cacheDir = dirname($cacheFile);
        
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        return file_put_contents($cacheFile, serialize($cacheData), LOCK_EX) !== false;
    }
    
    /**
     * Supprime une entrée du cache
     */
    public function delete(string $key): bool
    {
        $cacheFile = $this->getCacheFile($key);
        
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        
        return true;
    }
    
    /**
     * Vérifie si une clé existe dans le cache
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }
    
    /**
     * Cache avec callback (pattern remember)
     */
    public function remember(string $key, callable $callback, int $ttl = null): mixed
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Cache des requêtes SQL
     */
    public function cacheQuery(string $sql, array $params = [], int $ttl = 300): mixed
    {
        $key = 'query_' . md5($sql . serialize($params));
        
        return $this->remember($key, function() use ($sql, $params) {
            $pdo = \App\Service\Database::connect();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }, $ttl);
    }
    
    /**
     * Cache spécifique pour les utilisateurs
     */
    public function cacheUser(int $userId, int $ttl = 1800): ?array
    {
        $key = "user_{$userId}";
        
        return $this->remember($key, function() use ($userId) {
            $pdo = \App\Service\Database::connect();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        }, $ttl);
    }
    
    /**
     * Cache des permissions utilisateur
     */
    public function cacheUserPermissions(int $userId, int $ttl = 3600): array
    {
        $key = "permissions_{$userId}";
        
        return $this->remember($key, function() use ($userId) {
            $pdo = \App\Service\Database::connect();
            $stmt = $pdo->prepare("
                SELECT p.name 
                FROM permissions p
                JOIN role_permissions rp ON p.id = rp.permission_id
                JOIN user_roles ur ON rp.role_id = ur.role_id
                WHERE ur.user_id = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(\PDO::FETCH_COLUMN);
        }, $ttl);
    }
    
    /**
     * Cache des statistiques dashboard
     */
    public function cacheDashboardStats(int $userId, int $ttl = 600): array
    {
        $key = "dashboard_stats_{$userId}";
        
        return $this->remember($key, function() use ($userId) {
            $pdo = \App\Service\Database::connect();
            
            // Compter toutes les interventions (pas spécifique à l'utilisateur pour l'instant)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM interventions");
            $stmt->execute();
            $interventions = $stmt->fetchColumn();
            
            // Compter les véhicules
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM vehicles");
            $stmt->execute();
            $vehicles = $stmt->fetchColumn();
            
            // Compter les notifications non lues
            $notifications = 0;
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE recipient_id = ? AND is_read = 0");
                $stmt->execute([$userId]);
                $notifications = $stmt->fetchColumn();
            } catch (\PDOException $e) {
                error_log("Erreur comptage notifications: " . $e->getMessage());
            }
            
            // Compter les interventions pour l'utilisateur actuel si possible
            $userInterventions = 0;
            try {
                // Essayer avec la colonne technicien (peut contenir l'ID ou le nom)
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM interventions WHERE technicien LIKE ?");
                $stmt->execute(["%$userId%"]);
                $userInterventions = $stmt->fetchColumn();
            } catch (\PDOException $e) {
                error_log("Erreur comptage interventions utilisateur: " . $e->getMessage());
            }
            
            return [
                'interventions' => $interventions,
                'user_interventions' => $userInterventions,
                'vehicles' => $vehicles,
                'notifications' => $notifications,
                'cached_at' => time()
            ];
        }, $ttl);
    }
    
    /**
     * Invalide le cache utilisateur (lors de modifications)
     */
    public function invalidateUser(int $userId): void
    {
        $this->delete("user_{$userId}");
        $this->delete("permissions_{$userId}");
        $this->delete("dashboard_stats_{$userId}");
    }
    
    /**
     * Invalide les caches liés aux interventions
     */
    public function invalidateInterventions(int $userId = null): void
    {
        if ($userId) {
            $this->delete("dashboard_stats_{$userId}");
        }
        
        // Supprimer tous les caches de requêtes liées aux interventions
        $this->deleteByPattern('query_*intervention*');
    }
    
    /**
     * Cache des templates Twig compilés
     */
    public function cacheTemplate(string $template, array $data = [], int $ttl = 3600): string
    {
        $key = 'template_' . md5($template . serialize($data));
        
        return $this->remember($key, function() use ($template, $data) {
            $twig = new \App\Service\TwigService();
            return $twig->render($template, $data);
        }, $ttl);
    }
    
    /**
     * Cache des configurations système
     */
    public function cacheConfig(string $configKey, int $ttl = 7200): mixed
    {
        $key = "config_{$configKey}";
        
        return $this->remember($key, function() use ($configKey) {
            $pdo = \App\Service\Database::connect();
            $stmt = $pdo->prepare("SELECT value FROM system_config WHERE config_key = ?");
            $stmt->execute([$configKey]);
            $result = $stmt->fetch();
            return $result ? $result['value'] : null;
        }, $ttl);
    }
    
    /**
     * Nettoyage du cache expiré
     */
    public function cleanup(): int
    {
        $cleaned = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->cacheDir)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'cache') {
                $data = file_get_contents($file->getPathname());
                $cacheData = unserialize($data);
                
                if ($cacheData && isset($cacheData['expires']) && time() > $cacheData['expires']) {
                    unlink($file->getPathname());
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Vide tout le cache
     */
    public function flush(): bool
    {
        if (!is_dir($this->cacheDir)) {
            return true;
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->cacheDir),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                unlink($file->getPathname());
            } elseif ($file->isDir() && !in_array($file->getFilename(), ['.', '..'])) {
                rmdir($file->getPathname());
            }
        }
        
        return true;
    }
    
    /**
     * Supprime les caches correspondant à un pattern
     */
    public function deleteByPattern(string $pattern): int
    {
        $deleted = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->cacheDir)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'cache') {
                $filename = $file->getFilename();
                if (fnmatch($pattern, $filename)) {
                    unlink($file->getPathname());
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * Obtient les statistiques du cache
     */
    public function getStats(): array
    {
        $totalFiles = 0;
        $totalSize = 0;
        $expiredFiles = 0;
        
        if (is_dir($this->cacheDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->cacheDir)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'cache') {
                    $totalFiles++;
                    $totalSize += $file->getSize();
                    
                    $data = file_get_contents($file->getPathname());
                    $cacheData = unserialize($data);
                    
                    if ($cacheData && isset($cacheData['expires']) && time() > $cacheData['expires']) {
                        $expiredFiles++;
                    }
                }
            }
        }
        
        return [
            'total_files' => $totalFiles,
            'total_size' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'expired_files' => $expiredFiles,
            'cache_dir' => $this->cacheDir
        ];
    }
    
    /**
     * Génère le chemin du fichier cache
     */
    private function getCacheFile(string $key): string
    {
        $hash = hash('sha256', $key);
        $subDir = substr($hash, 0, 2);
        return $this->cacheDir . '/' . $subDir . '/' . $hash . '.cache';
    }
    
    /**
     * Cache avec verrouillage (évite les race conditions)
     */
    public function lockAndSet(string $key, callable $callback, int $ttl = null): mixed
    {
        $lockFile = $this->getCacheFile($key . '.lock');
        $lockDir = dirname($lockFile);
        
        if (!is_dir($lockDir)) {
            mkdir($lockDir, 0755, true);
        }
        
        $fp = fopen($lockFile, 'w');
        if (!$fp || !flock($fp, LOCK_EX)) {
            return $this->get($key);
        }
        
        try {
            // Vérifier à nouveau si la valeur existe (après le lock)
            $value = $this->get($key);
            if ($value !== null) {
                return $value;
            }
            
            // Générer la nouvelle valeur
            $value = $callback();
            $this->set($key, $value, $ttl);
            
            return $value;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
            unlink($lockFile);
        }
    }
}
