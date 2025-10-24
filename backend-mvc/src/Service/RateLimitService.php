<?php

namespace App\Service;

class RateLimitService
{
    private string $cacheDir;
    private array $limits;
    
    public function __construct()
    {
        $this->cacheDir = __DIR__ . '/../../var/rate_limit/';
        
        // Créer le dossier de cache s'il n'existe pas
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        
        // Configuration des limites par défaut
        $this->limits = [
            'login' => ['max_attempts' => 5, 'window' => 900], // 5 tentatives par 15 min
            'password_reset' => ['max_attempts' => 3, 'window' => 3600], // 3 tentatives par heure
            'api' => ['max_attempts' => 100, 'window' => 3600], // 100 requêtes par heure
            'form_submission' => ['max_attempts' => 10, 'window' => 300], // 10 soumissions par 5 min
        ];
    }
    
    /**
     * Vérifie si une action est autorisée
     */
    public function attempt(string $key, string $action = 'default'): bool
    {
        $limit = $this->limits[$action] ?? $this->limits['api'];
        $cacheFile = $this->getCacheFile($key, $action);
        
        // Charger les tentatives existantes
        $attempts = $this->loadAttempts($cacheFile);
        
        // Nettoyer les anciennes tentatives
        $attempts = $this->cleanOldAttempts($attempts, $limit['window']);
        
        // Vérifier si la limite est atteinte
        if (count($attempts) >= $limit['max_attempts']) {
            $this->logRateLimitExceeded($key, $action);
            return false;
        }
        
        // Ajouter la nouvelle tentative
        $attempts[] = time();
        
        // Sauvegarder
        $this->saveAttempts($cacheFile, $attempts);
        
        return true;
    }
    
    /**
     * Vérifie le statut sans enregistrer de tentative
     */
    public function check(string $key, string $action = 'default'): array
    {
        $limit = $this->limits[$action] ?? $this->limits['api'];
        $cacheFile = $this->getCacheFile($key, $action);
        
        $attempts = $this->loadAttempts($cacheFile);
        $attempts = $this->cleanOldAttempts($attempts, $limit['window']);
        
        $remaining = max(0, $limit['max_attempts'] - count($attempts));
        $resetTime = count($attempts) > 0 ? max($attempts) + $limit['window'] : null;
        
        return [
            'allowed' => $remaining > 0,
            'remaining' => $remaining,
            'reset_at' => $resetTime,
            'retry_after' => $resetTime ? max(0, $resetTime - time()) : 0
        ];
    }
    
    /**
     * Réinitialise les tentatives pour une clé
     */
    public function reset(string $key, string $action = 'default'): void
    {
        $cacheFile = $this->getCacheFile($key, $action);
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }
    
    /**
     * Bloque temporairement une clé
     */
    public function block(string $key, string $action = 'default', int $duration = 3600): void
    {
        $limit = $this->limits[$action] ?? $this->limits['api'];
        $cacheFile = $this->getCacheFile($key, $action);
        
        // Créer un nombre de tentatives qui dépasse la limite
        $attempts = array_fill(0, $limit['max_attempts'] + 1, time());
        
        $this->saveAttempts($cacheFile, $attempts);
        $this->logRateLimitBlocked($key, $action, $duration);
    }
    
    /**
     * Obtient le chemin du fichier de cache
     */
    private function getCacheFile(string $key, string $action): string
    {
        $hash = md5($key . '_' . $action);
        return $this->cacheDir . "rate_limit_{$hash}.json";
    }
    
    /**
     * Charge les tentatives depuis le cache
     */
    private function loadAttempts(string $cacheFile): array
    {
        if (!file_exists($cacheFile)) {
            return [];
        }
        
        $content = file_get_contents($cacheFile);
        $data = json_decode($content, true);
        
        return is_array($data) ? $data : [];
    }
    
    /**
     * Sauvegarde les tentatives dans le cache
     */
    private function saveAttempts(string $cacheFile, array $attempts): void
    {
        file_put_contents($cacheFile, json_encode($attempts), LOCK_EX);
    }
    
    /**
     * Nettoie les anciennes tentatives
     */
    private function cleanOldAttempts(array $attempts, int $window): array
    {
        $cutoff = time() - $window;
        return array_filter($attempts, function($timestamp) use ($cutoff) {
            return $timestamp > $cutoff;
        });
    }
    
    /**
     * Log quand la limite est dépassée
     */
    private function logRateLimitExceeded(string $key, string $action): void
    {
        $message = "Rate limit exceeded for key: {$key}, action: {$action}, IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        error_log($message);
        
        // Log dans un fichier spécifique pour le monitoring
        $logFile = __DIR__ . '/../../logs/rate_limit.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log quand une clé est bloquée
     */
    private function logRateLimitBlocked(string $key, string $action, int $duration): void
    {
        $message = "Rate limit blocked for key: {$key}, action: {$action}, duration: {$duration}s, IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        error_log($message);
        
        $logFile = __DIR__ . '/../../logs/rate_limit.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Nettoie les anciens fichiers de cache
     */
    public function cleanup(): int
    {
        $cleaned = 0;
        $files = glob($this->cacheDir . 'rate_limit_*.json');
        
        foreach ($files as $file) {
            // Supprimer les fichiers plus anciens que 24h
            if (filemtime($file) < time() - 86400) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Obtient les statistiques de rate limiting
     */
    public function getStats(): array
    {
        $files = glob($this->cacheDir . 'rate_limit_*.json');
        $activeKeys = 0;
        $totalAttempts = 0;
        
        foreach ($files as $file) {
            $attempts = $this->loadAttempts($file);
            if (!empty($attempts)) {
                $activeKeys++;
                $totalAttempts += count($attempts);
            }
        }
        
        return [
            'active_keys' => $activeKeys,
            'total_attempts' => $totalAttempts,
            'cache_files' => count($files)
        ];
    }
    
    /**
     * Configure une limite personnalisée
     */
    public function setLimit(string $action, int $maxAttempts, int $window): void
    {
        $this->limits[$action] = [
            'max_attempts' => $maxAttempts,
            'window' => $window
        ];
    }
}