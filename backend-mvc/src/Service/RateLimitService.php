<?php

namespace App\Service;

class RateLimitService
{
    private string $logPath;
    
    public function __construct()
    {
        EnvService::load();
        $this->logPath = EnvService::get('LOG_PATH', '/Applications/MAMP/htdocs/exemple/backend-mvc/logs/app.log');
    }
    
    /**
     * Vérifie si l'IP a dépassé la limite de tentatives
     */
    public function isRateLimited(string $identifier, int $maxAttempts = 5, int $timeWindow = 300): bool
    {
        $cacheFile = $this->getCacheFile($identifier);
        
        if (!file_exists($cacheFile)) {
            return false;
        }
        
        $data = json_decode(file_get_contents($cacheFile), true);
        if (!$data) {
            return false;
        }
        
        $now = time();
        
        // Nettoyer les tentatives expirées
        $data['attempts'] = array_filter($data['attempts'], function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        // Sauvegarder les données nettoyées
        file_put_contents($cacheFile, json_encode($data));
        
        return count($data['attempts']) >= $maxAttempts;
    }
    
    /**
     * Enregistre une tentative
     */
    public function recordAttempt(string $identifier): void
    {
        $cacheFile = $this->getCacheFile($identifier);
        $now = time();
        
        $data = ['attempts' => []];
        if (file_exists($cacheFile)) {
            $existing = json_decode(file_get_contents($cacheFile), true);
            if ($existing) {
                $data = $existing;
            }
        }
        
        $data['attempts'][] = $now;
        
        // Créer le dossier cache s'il n'existe pas
        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        file_put_contents($cacheFile, json_encode($data));
        
        // Log de sécurité
        $this->logSecurityEvent($identifier, 'attempt_recorded');
    }
    
    /**
     * Réinitialise les tentatives pour un identifiant
     */
    public function resetAttempts(string $identifier): void
    {
        $cacheFile = $this->getCacheFile($identifier);
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
        
        $this->logSecurityEvent($identifier, 'attempts_reset');
    }
    
    /**
     * Bloque temporairement un identifiant
     */
    public function blockTemporarily(string $identifier, int $duration = 900): void
    {
        $cacheFile = $this->getCacheFile($identifier);
        $data = [
            'blocked_until' => time() + $duration,
            'attempts' => []
        ];
        
        // Créer le dossier cache s'il n'existe pas
        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        file_put_contents($cacheFile, json_encode($data));
        
        $this->logSecurityEvent($identifier, 'temporarily_blocked', ['duration' => $duration]);
    }
    
    /**
     * Vérifie si un identifiant est bloqué
     */
    public function isBlocked(string $identifier): bool
    {
        $cacheFile = $this->getCacheFile($identifier);
        
        if (!file_exists($cacheFile)) {
            return false;
        }
        
        $data = json_decode(file_get_contents($cacheFile), true);
        if (!$data || !isset($data['blocked_until'])) {
            return false;
        }
        
        $now = time();
        if ($now > $data['blocked_until']) {
            // Le blocage a expiré
            unlink($cacheFile);
            return false;
        }
        
        return true;
    }
    
    /**
     * Obtient le temps restant avant déblocage
     */
    public function getBlockTimeRemaining(string $identifier): int
    {
        $cacheFile = $this->getCacheFile($identifier);
        
        if (!file_exists($cacheFile)) {
            return 0;
        }
        
        $data = json_decode(file_get_contents($cacheFile), true);
        if (!$data || !isset($data['blocked_until'])) {
            return 0;
        }
        
        $remaining = $data['blocked_until'] - time();
        return max(0, $remaining);
    }
    
    /**
     * Rate limiting pour les connexions
     */
    public function checkLoginAttempts(string $ip, string $email = ''): array
    {
        $ipIdentifier = 'login_ip_' . $ip;
        $emailIdentifier = 'login_email_' . hash('sha256', $email);
        
        // Vérifier les blocages
        if ($this->isBlocked($ipIdentifier)) {
            return [
                'allowed' => false,
                'reason' => 'IP temporairement bloquée',
                'retry_after' => $this->getBlockTimeRemaining($ipIdentifier)
            ];
        }
        
        if ($email && $this->isBlocked($emailIdentifier)) {
            return [
                'allowed' => false,
                'reason' => 'Compte temporairement bloqué',
                'retry_after' => $this->getBlockTimeRemaining($emailIdentifier)
            ];
        }
        
        // Vérifier les rate limits
        if ($this->isRateLimited($ipIdentifier, 10, 300)) { // 10 tentatives par 5 min
            $this->blockTemporarily($ipIdentifier, 900); // Bloquer 15 min
            return [
                'allowed' => false,
                'reason' => 'Trop de tentatives depuis cette IP',
                'retry_after' => 900
            ];
        }
        
        if ($email && $this->isRateLimited($emailIdentifier, 5, 300)) { // 5 tentatives par 5 min
            $this->blockTemporarily($emailIdentifier, 1800); // Bloquer 30 min
            return [
                'allowed' => false,
                'reason' => 'Trop de tentatives pour ce compte',
                'retry_after' => 1800
            ];
        }
        
        return ['allowed' => true];
    }
    
    /**
     * Enregistre une tentative de connexion échouée
     */
    public function recordFailedLogin(string $ip, string $email = ''): void
    {
        $this->recordAttempt('login_ip_' . $ip);
        if ($email) {
            $this->recordAttempt('login_email_' . hash('sha256', $email));
        }
        
        $this->logSecurityEvent($ip, 'failed_login', ['email' => $email]);
    }
    
    /**
     * Réinitialise les tentatives après connexion réussie
     */
    public function recordSuccessfulLogin(string $ip, string $email = ''): void
    {
        $this->resetAttempts('login_ip_' . $ip);
        if ($email) {
            $this->resetAttempts('login_email_' . hash('sha256', $email));
        }
        
        $this->logSecurityEvent($ip, 'successful_login', ['email' => $email]);
    }
    
    /**
     * Rate limiting général pour les API
     */
    public function checkApiRateLimit(string $identifier, int $maxRequests = 100, int $timeWindow = 3600): bool
    {
        return !$this->isRateLimited('api_' . $identifier, $maxRequests, $timeWindow);
    }
    
    /**
     * Enregistre une requête API
     */
    public function recordApiRequest(string $identifier): void
    {
        $this->recordAttempt('api_' . $identifier);
    }
    
    /**
     * Obtient le chemin du fichier cache
     */
    private function getCacheFile(string $identifier): string
    {
        $logDir = dirname($this->logPath);
        return $logDir . '/rate_limit_' . hash('sha256', $identifier) . '.json';
    }
    
    /**
     * Log des événements de sécurité
     */
    private function logSecurityEvent(string $identifier, string $event, array $data = []): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'identifier' => $identifier,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'data' => $data
        ];
        
        $logDir = dirname($this->logPath);
        $securityLogPath = $logDir . '/security.log';
        
        $logLine = json_encode($logEntry) . "\n";
        file_put_contents($securityLogPath, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Nettoie les anciens fichiers de cache
     */
    public function cleanupOldCache(int $maxAge = 86400): void
    {
        $logDir = dirname($this->logPath);
        $files = glob($logDir . '/rate_limit_*.json');
        
        $now = time();
        foreach ($files as $file) {
            if ($now - filemtime($file) > $maxAge) {
                unlink($file);
            }
        }
    }
}


