<?php

namespace App\Service;

class CsrfService
{
    private const TOKEN_LENGTH = 32;
    private const SESSION_KEY = 'csrf_tokens';
    private const MAX_TOKENS = 10; // Limite le nombre de tokens en session
    
    /**
     * Génère un nouveau token CSRF
     */
    public function generateToken(string $formName = 'default'): string
    {
        SessionManager::start();
        
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        $timestamp = time();
        
        // Initialiser le tableau de tokens s'il n'existe pas
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }
        
        // Ajouter le nouveau token
        $_SESSION[self::SESSION_KEY][$formName] = [
            'token' => $token,
            'timestamp' => $timestamp
        ];
        
        // Nettoyer les anciens tokens si nécessaire
        $this->cleanupOldTokens();
        
        return $token;
    }
    
    /**
     * Valide un token CSRF
     */
    public function validateToken(string $token, string $formName = 'default', bool $consumeToken = true): bool
    {
        SessionManager::start();
        
        if (!isset($_SESSION[self::SESSION_KEY][$formName])) {
            $this->logSecurityEvent('csrf_token_missing', ['form' => $formName]);
            return false;
        }
        
        $storedData = $_SESSION[self::SESSION_KEY][$formName];
        $storedToken = $storedData['token'];
        $timestamp = $storedData['timestamp'];
        
        // Vérifier l'expiration (30 minutes par défaut)
        $maxAge = EnvService::getInt('CSRF_TOKEN_LIFETIME', 1800);
        if (time() - $timestamp > $maxAge) {
            if ($consumeToken) {
                unset($_SESSION[self::SESSION_KEY][$formName]);
            }
            $this->logSecurityEvent('csrf_token_expired', ['form' => $formName]);
            return false;
        }
        
        // Validation sécurisée du token
        if (!hash_equals($storedToken, $token)) {
            $this->logSecurityEvent('csrf_token_invalid', ['form' => $formName]);
            return false;
        }
        
        // Consommer le token après utilisation (protection contre replay)
        if ($consumeToken) {
            unset($_SESSION[self::SESSION_KEY][$formName]);
        }
        
        return true;
    }
    
    /**
     * Génère un champ input hidden pour les formulaires
     */
    public function getTokenField(string $formName = 'default'): string
    {
        $token = $this->generateToken($formName);
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Génère un meta tag pour AJAX
     */
    public function getMetaTag(string $formName = 'ajax'): string
    {
        $token = $this->generateToken($formName);
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Valide le token depuis la requête POST
     */
    public function validateFromRequest(string $formName = 'default'): bool
    {
        $token = $_POST['csrf_token'] ?? '';
        
        if (empty($token)) {
            $this->logSecurityEvent('csrf_token_missing_in_request', ['form' => $formName]);
            return false;
        }
        
        return $this->validateToken($token, $formName);
    }
    
    /**
     * Valide le token depuis les headers (pour AJAX)
     */
    public function validateFromHeader(string $formName = 'ajax'): bool
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (empty($token)) {
            $this->logSecurityEvent('csrf_token_missing_in_header', ['form' => $formName]);
            return false;
        }
        
        return $this->validateToken($token, $formName);
    }
    
    /**
     * Middleware pour valider automatiquement les requêtes POST
     */
    public function validateMiddleware(string $formName = 'default'): bool
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return true; // Skip pour les requêtes non-POST
        }
        
        // Vérifier d'abord les headers (AJAX)
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return $this->validateFromHeader($formName);
        }
        
        // Puis vérifier POST
        return $this->validateFromRequest($formName);
    }
    
    /**
     * Nettoie les anciens tokens
     */
    private function cleanupOldTokens(): void
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return;
        }
        
        $tokens = $_SESSION[self::SESSION_KEY];
        $maxAge = EnvService::getInt('CSRF_TOKEN_LIFETIME', 1800);
        $now = time();
        
        // Supprimer les tokens expirés
        foreach ($tokens as $formName => $data) {
            if ($now - $data['timestamp'] > $maxAge) {
                unset($_SESSION[self::SESSION_KEY][$formName]);
            }
        }
        
        // Limiter le nombre de tokens (garde les plus récents)
        if (count($_SESSION[self::SESSION_KEY]) > self::MAX_TOKENS) {
            $sorted = $_SESSION[self::SESSION_KEY];
            uasort($sorted, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });
            
            $_SESSION[self::SESSION_KEY] = array_slice($sorted, 0, self::MAX_TOKENS, true);
        }
    }
    
    /**
     * Supprime tous les tokens pour un formulaire
     */
    public function invalidateTokens(string $formName = null): void
    {
        SessionManager::start();
        
        if ($formName === null) {
            // Supprimer tous les tokens
            unset($_SESSION[self::SESSION_KEY]);
        } else {
            // Supprimer les tokens pour un formulaire spécifique
            unset($_SESSION[self::SESSION_KEY][$formName]);
        }
    }
    
    /**
     * Vérifie si un token existe pour un formulaire
     */
    public function hasToken(string $formName = 'default'): bool
    {
        SessionManager::start();
        return isset($_SESSION[self::SESSION_KEY][$formName]);
    }
    
    /**
     * Génère un token pour Twig
     */
    public function getTokenForTwig(string $formName = 'default'): string
    {
        return $this->generateToken($formName);
    }
    
    /**
     * Log des événements de sécurité CSRF
     */
    private function logSecurityEvent(string $event, array $data = []): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => 'csrf_' . $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown',
            'data' => $data
        ];
        
        // Configuration directe des logs
        $logDir = __DIR__ . '/../../logs';
        $securityLogPath = $logDir . '/security.log';
        
        // Créer le dossier de logs s'il n'existe pas
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logLine = json_encode($logEntry) . "\n";
        file_put_contents($securityLogPath, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Double submit cookie pattern pour une sécurité renforcée
     */
    public function generateDoubleSubmitToken(string $formName = 'default'): array
    {
        $token = $this->generateToken($formName);
        
        // Cookie sécurisé
        $isSecure = EnvService::getBool('SESSION_SECURE', false);
        setcookie(
            'csrf_token_' . $formName,
            $token,
            [
                'expires' => time() + 1800, // 30 minutes
                'path' => '/',
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );
        
        return [
            'token' => $token,
            'field' => $this->getTokenField($formName)
        ];
    }
    
    /**
     * Valide le double submit pattern
     */
    public function validateDoubleSubmit(string $token, string $formName = 'default'): bool
    {
        $cookieToken = $_COOKIE['csrf_token_' . $formName] ?? '';
        
        if (empty($cookieToken)) {
            $this->logSecurityEvent('csrf_double_submit_cookie_missing', ['form' => $formName]);
            return false;
        }
        
        if (!hash_equals($cookieToken, $token)) {
            $this->logSecurityEvent('csrf_double_submit_mismatch', ['form' => $formName]);
            return false;
        }
        
        return $this->validateToken($token, $formName);
    }
}


