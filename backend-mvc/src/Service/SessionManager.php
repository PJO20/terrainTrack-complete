<?php

namespace App\Service;

class SessionManager
{
    // Le timeout est maintenant configurable via .env
    
    /**
     * Configure et démarre la session avec les paramètres de sécurité
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Chargement des variables d'environnement
            EnvService::load();
            
            $sessionTimeout = EnvService::getInt('SESSION_TIMEOUT', 1800);
            $isSecure = EnvService::getBool('SESSION_SECURE', false);
            
            // Configuration sécurisée de la session (seulement si headers pas encore envoyés)
            if (!headers_sent()) {
                ini_set('session.gc_maxlifetime', $sessionTimeout);
                ini_set('session.cookie_lifetime', $sessionTimeout);
                ini_set('session.cookie_httponly', 1);
                ini_set('session.cookie_secure', $isSecure ? 1 : 0);
                ini_set('session.use_strict_mode', 1);
                ini_set('session.cookie_samesite', 'Strict'); // Protection CSRF
                
                // Régénération ID de session périodique
                ini_set('session.gc_probability', 1);
                ini_set('session.gc_divisor', 100);
            }
            
            session_start();
            
            // Régénérer l'ID de session périodiquement (sécurité)
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } elseif (time() - $_SESSION['created'] > 300) { // 5 minutes
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
            
            // Initialiser le timestamp de dernière activité
            if (!isset($_SESSION['last_activity'])) {
                $_SESSION['last_activity'] = time();
            }
        }
        // Si pas d'utilisateur en session mais cookie remember_me présent, tenter restauration
        if (!isset($_SESSION['user']) && isset($_COOKIE['remember_me'])) {
            $repo = new \App\Repository\RememberMeTokenRepository();
            $tokenData = $repo->findValidToken($_COOKIE['remember_me']);
            if ($tokenData) {
                // Restaurer la session utilisateur
                $userRepo = new \App\Repository\UserRepository();
                $user = $userRepo->findById($tokenData['user_id']);
                if ($user) {
                    $_SESSION['user'] = [
                        'id' => $user->getId(),
                        'email' => $user->getEmail()
                    ];
                    self::updateActivity();
                }
            }
        }
    }
    
    /**
     * Vérifie si la session a expiré
     */
    public static function isSessionExpired(): bool
    {
        EnvService::load();
        $sessionTimeout = EnvService::getInt('SESSION_TIMEOUT', 1800);
        
        if (!isset($_SESSION['last_activity'])) {
            return true;
        }
        
        return (time() - $_SESSION['last_activity']) > $sessionTimeout;
    }
    
    /**
     * Met à jour le timestamp de dernière activité
     */
    public static function updateActivity(): void
    {
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Détruit la session (déconnexion)
     */
    public static function destroySession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }
    }
    
    /**
     * Vérifie l'authentification et gère le timeout
     */
    public static function requireLogin(): void
    {
        self::startSession();
        
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['user'])) {
            // Détection automatique de l'environnement
            $loginUrl = '/login';
            if (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/exemple/backend-mvc/public/') !== false) {
                // Environnement Apache/serveur web traditionnel
                $loginUrl = '/exemple/backend-mvc/public/login';
            }
            header("Location: $loginUrl");
            exit;
        }
        
        // Vérifier l'expiration de la session
        if (self::isSessionExpired()) {
            self::destroySession();
            // Détection automatique de l'environnement
            $loginUrl = '/login?timeout=1';
            if (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/exemple/backend-mvc/public/') !== false) {
                // Environnement Apache/serveur web traditionnel
                $loginUrl = '/exemple/backend-mvc/public/login?timeout=1';
            }
            header("Location: $loginUrl");
            exit;
        }
        
        // Mettre à jour l'activité
        self::updateActivity();
    }
    
    /**
     * Obtient le temps restant avant expiration (en minutes)
     */
    public static function getTimeRemaining(): int
    {
        if (!isset($_SESSION['last_activity'])) {
            return 0;
        }
        
        EnvService::load();
        $sessionTimeout = EnvService::getInt('SESSION_TIMEOUT', 1800);
        
        $elapsed = time() - $_SESSION['last_activity'];
        $remaining = $sessionTimeout - $elapsed;
        
        return max(0, floor($remaining / 60));
    }

    /**
     * Récupère l'utilisateur actuel depuis la session
     */
    public static function getCurrentUser(): ?array
    {
        self::startSession();
        
        if (!isset($_SESSION['user'])) {
            return null;
        }
        
        // Vérifier l'expiration de la session
        if (self::isSessionExpired()) {
            self::destroySession();
            return null;
        }
        
        // Mettre à jour l'activité
        self::updateActivity();
        
        // Retourner les données utilisateur de la session
        return $_SESSION['user'];
    }
    
    /**
     * Vérifie si l'utilisateur est connecté
     */
    public static function isLoggedIn(): bool
    {
        self::startSession();
        
        if (self::isSessionExpired()) {
            return false;
        }
        
        return isset($_SESSION['user']) && !empty($_SESSION['user']);
    }
} 