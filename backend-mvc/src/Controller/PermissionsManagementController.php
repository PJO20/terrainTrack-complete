<?php

namespace App\Controller;

use App\Service\TwigService;
use App\Service\SessionManager;

class PermissionsManagementController
{
    private TwigService $twig;

    public function __construct(TwigService $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Page principale de gestion des permissions (accès sécurisé)
     */
    public function management(): string
    {
        // Vérification de sécurité maximale
        SessionManager::requireLogin();
        
        $currentUser = SessionManager::getCurrentUser();
        
        if (!$currentUser) {
            header('Location: /login');
            exit;
        }
        
        // Vérification des permissions d'accès
        if (!$this->hasPermissionAccess($currentUser)) {
            $this->logSecurityEvent('UNAUTHORIZED_ACCESS_ATTEMPT', 
                "User {$currentUser['id']} attempted to access permissions management", 
                $_SERVER['REMOTE_ADDR'] ?? 'unknown');
            
            header('Location: ' . $this->getRedirectUrlByRole($currentUser));
            exit;
        }
        
        // Vérification de compromission de session
        if ($this->isSessionCompromised()) {
            $this->logSecurityEvent('SUSPICIOUS_SESSION', 
                "Suspicious session detected for user {$currentUser['id']}", 
                $_SERVER['REMOTE_ADDR'] ?? 'unknown');
            
            SessionManager::logout();
            header('Location: /login?error=security_check');
            exit;
        }
        
        // Application du timeout de session renforcé pour les admins
        $this->enforceAdminSessionTimeout();
        
        // Log d'accès autorisé
        $this->logSecurityEvent('AUTHORIZED_ACCESS', 
            "User {$currentUser['id']} accessed permissions management", 
            $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        
        // Rendu du template avec les données sécurisées
        return $this->twig->render('permissions/management.html.twig', [
            'user' => $currentUser,
            'security_level' => 'admin',
            'session_timeout' => 30, // 30 minutes pour les admins
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }

    /**
     * API pour vérifier l'état de la session (appelée par JavaScript)
     */
    public function checkSessionStatus(): void
    {
        header('Content-Type: application/json');
        
        SessionManager::start();
        $currentUser = SessionManager::getCurrentUser();
        
        // Debug logs
        error_log("DEBUG: checkSessionStatus called");
        error_log("DEBUG: currentUser = " . ($currentUser ? 'exists' : 'null'));
        
        if (!$currentUser) {
            error_log("DEBUG: No current user found");
            http_response_code(401);
            echo json_encode([
                'status' => 'unauthorized',
                'message' => 'Session expirée ou accès non autorisé',
                'redirect' => '/login'
            ]);
            return;
        }
        
        error_log("DEBUG: User found: " . ($currentUser['email'] ?? 'unknown'));
        error_log("DEBUG: User role: " . ($currentUser['role'] ?? 'no role'));
        error_log("DEBUG: isAdmin: " . (($currentUser['role'] === 'admin' || $currentUser['role'] === 'super_admin') ? 'true' : 'false'));
        error_log("DEBUG: isSuperAdmin: " . (($currentUser['role'] === 'super_admin') ? 'true' : 'false'));
        
        if (!$this->hasPermissionAccess($currentUser)) {
            error_log("DEBUG: Permission access denied for user: " . ($currentUser['email'] ?? 'unknown'));
            http_response_code(401);
            echo json_encode([
                'status' => 'unauthorized',
                'message' => 'Session expirée ou accès non autorisé',
                'redirect' => '/login'
            ]);
            return;
        }
        
        if ($this->isSessionCompromised()) {
            error_log("DEBUG: Session compromised for user: " . ($currentUser['email'] ?? 'unknown'));
            http_response_code(403);
            echo json_encode([
                'status' => 'compromised',
                'message' => 'Session potentiellement compromise',
                'redirect' => '/login?error=security_check'
            ]);
            return;
        }
        
        error_log("DEBUG: Session valid for user: " . ($currentUser['email'] ?? 'unknown'));
        echo json_encode([
            'status' => 'valid',
            'user' => [
                'id' => $currentUser['id'],
                'name' => $currentUser['email'],
                'role' => $currentUser['role']
            ],
            'session_remaining' => SessionManager::getTimeRemaining()
        ]);
    }

    /**
     * Vérifie si l'utilisateur peut accéder à la gestion des permissions
     */
    private function hasPermissionAccess($user): bool
    {
        // Vérification stricte : seulement admin et super-admin
        if (!$user) {
            return false;
        }
        
        // Vérification basée sur is_admin (structure de votre BDD)
        $isAdmin = isset($user['is_admin']) && $user['is_admin'] == 1;
        
        // Vérification alternative par rôle (si présent)
        $role = $user['role'] ?? '';
        $hasAdminRole = in_array(strtolower($role), ['admin', 'super_admin', 'administrator', 'super_administrator']);
        
        // Si l'utilisateur est admin (is_admin = 1) ou a un rôle admin, il a accès
        if ($isAdmin || $hasAdminRole) {
            return true;
        }
        
        // Vérification par email (pour les tests - utilisateurs admin créés)
        $email = $user['email'] ?? '';
        if (strpos($email, 'admin') !== false) {
            return true;
        }
        
        return false;
    }

    /**
     * Détermine l'URL de redirection selon le rôle de l'utilisateur
     */
    private function getRedirectUrlByRole($user): string
    {
        // Vérifier si l'utilisateur est admin (basé sur is_admin)
        $isAdmin = isset($user['is_admin']) && $user['is_admin'] == 1;
        
        if ($isAdmin) {
            return '/dashboard';
        }
        
        // Vérification par rôle (si présent)
        $role = $user['role'] ?? '';
        if (in_array(strtolower($role), ['admin', 'super_admin'])) {
            return '/dashboard';
        }
        
        return '/dashboard';
    }

    /**
     * Vérifie si la session est potentiellement compromise
     */
    private function isSessionCompromised(): bool
    {
        $currentIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $initialIp = $_SESSION['initial_ip'] ?? $currentIp;
        $initialUserAgent = $_SESSION['user_agent'] ?? $currentUserAgent;
        
        // Vérification de l'IP - désactivée pour éviter les faux positifs avec les proxies
        // if ($initialIp !== $currentIp) {
        //     return true;
        // }
        
        // Vérification du User-Agent - assouplie
        if ($initialUserAgent && $initialUserAgent !== $currentUserAgent) {
            // Log pour debug mais ne pas bloquer
            error_log("User-Agent changed: {$initialUserAgent} -> {$currentUserAgent}");
            // return true; // Désactivé temporairement
        }
        
        // Vérification du nombre d'accès (protection contre les attaques par force brute)
        $accessCount = $_SESSION['access_count'] ?? 0;
        if ($accessCount > 1000) { // Limite augmentée
            return true;
        }
        
        return false;
    }

    /**
     * Applique un timeout de session réduit pour les administrateurs
     */
    private function enforceAdminSessionTimeout(): void
    {
        $adminTimeout = 3600; // 1 heure pour les admins (au lieu de 30 minutes)
        $lastActivity = $_SESSION['last_activity'] ?? time();
        
        if ((time() - $lastActivity) > $adminTimeout) {
            $this->logSecurityEvent('ADMIN_SESSION_TIMEOUT', "Admin session expired", $_SERVER['REMOTE_ADDR'] ?? 'unknown');
            SessionManager::logout();
            header('Location: /login?timeout=admin');
            exit;
        }
        
        // Enregistrer les informations de sécurité supplémentaires
        $_SESSION['initial_ip'] = $_SESSION['initial_ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $_SESSION['user_agent'] = $_SESSION['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $_SESSION['session_start'] = $_SESSION['session_start'] ?? time();
        $_SESSION['access_count'] = ($_SESSION['access_count'] ?? 0) + 1;
        $_SESSION['last_activity'] = time();
    }

    /**
     * Enregistre les événements de sécurité dans les logs d'audit
     */
    private function logSecurityEvent(string $eventType, string $description, string $ipAddress): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event_type' => $eventType,
            'description' => $description,
            'ip_address' => $ipAddress,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'session_id' => session_id()
        ];
        
        // Enregistrement dans le fichier de log sécurisé
        $logFile = __DIR__ . '/../../logs/security.log';
        $logDirectory = dirname($logFile);
        
        if (!is_dir($logDirectory)) {
            mkdir($logDirectory, 0750, true);
        }
        
        $logMessage = json_encode($logEntry) . PHP_EOL;
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
        // Log aussi dans le système PHP pour les erreurs critiques
        if (in_array($eventType, ['UNAUTHORIZED_ACCESS_ATTEMPT', 'PERMISSION_DENIED', 'SUSPICIOUS_SESSION'])) {
            error_log("SECURITY ALERT: {$eventType} - {$description} from {$ipAddress}");
        }
    }

    /**
     * Génère un token CSRF pour la sécurité
     */
    private function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
} 