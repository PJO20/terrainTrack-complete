<?php

namespace App\Service;

class SessionManager
{
    /**
     * Démarre une session si elle n'est pas déjà démarrée
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Démarre une session sécurisée
     */
    public static function startSecure(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Configuration sécurisée des sessions
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 0); // 1 en HTTPS
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_samesite', 'Strict');
            
            session_start();
            
            // Régénérer l'ID de session pour éviter les attaques de fixation
            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
            }
        }
    }

    /**
     * Vérifie si l'utilisateur est connecté
     */
    public static function isAuthenticated(): bool
    {
        self::start();
        return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
    }

    /**
     * Récupère les données de l'utilisateur connecté
     */
    public static function getUser(): ?array
    {
        self::start();
        return $_SESSION['user'] ?? null;
    }

    /**
     * Récupère l'utilisateur actuel (alias pour getUser)
     */
    public static function getCurrentUser(): ?array
    {
        return self::getUser();
    }

    /**
     * Vérifie si l'utilisateur est connecté et redirige si nécessaire
     */
    public static function requireLogin(string $redirectTo = '/login'): void
    {
        if (!self::isAuthenticated()) {
            header("Location: {$redirectTo}");
            exit;
        }
    }

    /**
     * Récupère le temps restant avant expiration de la session
     */
    public static function getTimeRemaining(int $timeout = null): int
    {
        self::start();
        
        if (!isset($_SESSION['last_activity'])) {
            return 0;
        }
        
        // Utiliser le timeout personnalisé de l'utilisateur ou la valeur par défaut
        $actualTimeout = $timeout ?? self::getUserSessionTimeout();
        
        $elapsed = time() - $_SESSION['last_activity'];
        $remaining = $actualTimeout - $elapsed;
        
        return max(0, $remaining);
    }

    /**
     * Vérifie si l'utilisateur est administrateur
     */
    public static function isAdmin(): bool
    {
        $user = self::getUser();
        return $user && isset($user['is_admin']) && $user['is_admin'] === true;
    }

    /**
     * Définit les données de l'utilisateur
     */
    public static function setUser(array $user): void
    {
        self::start();
        $_SESSION['user'] = $user;
        $_SESSION['authenticated'] = true;
        $_SESSION['last_activity'] = time();
    }

    /**
     * Déconnecte l'utilisateur
     */
    public static function logout(): void
    {
        self::start();
        
        // Détruire toutes les variables de session
        $_SESSION = [];
        
        // Détruire le cookie de session
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Détruire la session
        session_destroy();
    }

    /**
     * Vérifie si la session a expiré
     */
    public static function isExpired(int $timeout = null): bool
    {
        self::start();
        
        if (!isset($_SESSION['last_activity'])) {
            return true;
        }
        
        // Utiliser le timeout personnalisé de l'utilisateur ou la valeur par défaut
        $actualTimeout = $timeout ?? self::getUserSessionTimeout();
        
        return (time() - $_SESSION['last_activity']) > $actualTimeout;
    }

    /**
     * Met à jour l'activité de la session
     */
    public static function updateActivity(): void
    {
        self::start();
        $_SESSION['last_activity'] = time();
    }

    /**
     * Définit une variable de session
     */
    public static function set(string $key, $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Récupère une variable de session
     */
    public static function get(string $key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Supprime une variable de session
     */
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Vérifie si une variable de session existe
     */
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Nettoie les sessions expirées
     */
    public static function cleanup(): void
    {
        self::start();
        
        if (self::isExpired()) {
            self::logout();
        }
    }

    /**
     * Régénère l'ID de session
     */
    public static function regenerateId(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    /**
     * Définit un message flash
     */
    public static function setFlash(string $key, $message): void
    {
        self::start();
        $_SESSION['flash'][$key] = $message;
    }

    /**
     * Récupère et supprime un message flash
     */
    public static function getFlash(string $key, $default = null)
    {
        self::start();
        $message = $_SESSION['flash'][$key] ?? $default;
        unset($_SESSION['flash'][$key]);
        return $message;
    }

    /**
     * Récupère le timeout de session personnalisé de l'utilisateur (en secondes)
     */
    public static function getUserSessionTimeout(): int
    {
        self::start();
        
        // Vérifier si l'utilisateur est connecté
        if (!self::isAuthenticated()) {
            return 3600; // 1 heure par défaut si pas connecté
        }
        
        $user = self::getUser();
        if (!$user || !isset($user['session_timeout'])) {
            return 3600; // 1 heure par défaut
        }
        
        // Convertir les minutes en secondes
        return (int)$user['session_timeout'] * 60;
    }

    /**
     * Met à jour le timeout de session de l'utilisateur
     */
    public static function updateUserSessionTimeout(int $timeoutMinutes): bool
    {
        self::start();
        
        if (!self::isAuthenticated()) {
            return false;
        }
        
        $user = self::getUser();
        if (!$user || !isset($user['id'])) {
            return false;
        }
        
        try {
            // Connexion à la base de données
            $host = "localhost";
            $dbname = "exemple";
            $username = "root";
            $password = "root";
            $port = 8889;
            
            $pdo = new \PDO(
                "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                ]
            );
            
            // Mettre à jour le timeout dans la base de données
            $sql = "UPDATE users SET session_timeout = :timeout WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                'timeout' => $timeoutMinutes,
                'id' => $user['id']
            ]);
            
            if ($result) {
                // Mettre à jour la session avec la nouvelle valeur
                $_SESSION['user']['session_timeout'] = $timeoutMinutes;
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            error_log("Erreur lors de la mise à jour du timeout de session: " . $e->getMessage());
            return false;
        }
    }
}