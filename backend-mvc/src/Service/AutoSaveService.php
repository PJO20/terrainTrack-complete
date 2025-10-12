<?php

declare(strict_types=1);

namespace App\Service;

class AutoSaveService
{
    private static ?array $autoSaveData = null;
    private static int $autoSaveInterval = 30; // secondes
    private static string $autoSavePrefix = 'autosave_';

    /**
     * Vérifie si l'auto-save est activé pour l'utilisateur
     */
    public static function isAutoSaveEnabled(?int $userId = null): bool
    {
        if ($userId === null) {
            $user = SessionManager::getCurrentUser();
            $userId = $user ? $user['id'] : null;
        }

        if (!$userId) {
            return false;
        }

        try {
            $pdo = Database::connect();
            $stmt = $pdo->prepare("SELECT auto_save FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return (bool)($result['auto_save'] ?? true);
        } catch (\Exception $e) {
            error_log("Erreur vérification auto-save: " . $e->getMessage());
            return true; // Par défaut activé
        }
    }

    /**
     * Active ou désactive l'auto-save pour l'utilisateur
     */
    public static function setAutoSaveEnabled(int $userId, bool $enabled): bool
    {
        try {
            $pdo = Database::connect();
            $stmt = $pdo->prepare("UPDATE users SET auto_save = ? WHERE id = ?");
            $success = $stmt->execute([$enabled ? 1 : 0, $userId]);
            
            // Mettre à jour la session si c'est l'utilisateur actuel
            $currentUser = SessionManager::getCurrentUser();
            if ($currentUser && $currentUser['id'] == $userId) {
                $_SESSION['user']['auto_save'] = $enabled;
            }
            
            return $success;
        } catch (\Exception $e) {
            error_log("Erreur mise à jour auto-save: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sauvegarde automatique des données d'un formulaire
     */
    public static function saveFormData(string $formId, array $formData, ?int $userId = null): bool
    {
        if (!$userId) {
            $user = SessionManager::getCurrentUser();
            $userId = $user ? $user['id'] : null;
        }

        if (!$userId || !self::isAutoSaveEnabled($userId)) {
            return false;
        }

        try {
            $cacheKey = self::$autoSavePrefix . $userId . '_' . $formId;
            $cacheData = [
                'form_id' => $formId,
                'user_id' => $userId,
                'data' => $formData,
                'timestamp' => time(),
                'expires_at' => time() + (24 * 60 * 60) // 24 heures
            ];

            // Utiliser le cache ou stocker en base
            $cache = new CacheService();
            return $cache->set($cacheKey, $cacheData, 24 * 60 * 60);
        } catch (\Exception $e) {
            error_log("Erreur sauvegarde auto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les données sauvegardées automatiquement
     */
    public static function getFormData(string $formId, ?int $userId = null): ?array
    {
        if (!$userId) {
            $user = SessionManager::getCurrentUser();
            $userId = $user ? $user['id'] : null;
        }

        if (!$userId || !self::isAutoSaveEnabled($userId)) {
            return null;
        }

        try {
            $cacheKey = self::$autoSavePrefix . $userId . '_' . $formId;
            $cache = new CacheService();
            $cachedData = $cache->get($cacheKey);

            if ($cachedData && isset($cachedData['data'])) {
                // Vérifier si les données ne sont pas expirées
                if (isset($cachedData['expires_at']) && $cachedData['expires_at'] > time()) {
                    return $cachedData['data'];
                } else {
                    // Supprimer les données expirées
                    $cache->delete($cacheKey);
                }
            }

            return null;
        } catch (\Exception $e) {
            error_log("Erreur récupération auto-save: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Supprime les données sauvegardées automatiquement
     */
    public static function clearFormData(string $formId, ?int $userId = null): bool
    {
        if (!$userId) {
            $user = SessionManager::getCurrentUser();
            $userId = $user ? $user['id'] : null;
        }

        if (!$userId) {
            return false;
        }

        try {
            $cacheKey = self::$autoSavePrefix . $userId . '_' . $formId;
            $cache = new CacheService();
            return $cache->delete($cacheKey);
        } catch (\Exception $e) {
            error_log("Erreur suppression auto-save: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Nettoie toutes les données auto-save expirées
     */
    public static function cleanupExpiredData(): int
    {
        $cleaned = 0;
        try {
            $cache = new CacheService();
            // Cette méthode dépend de l'implémentation du CacheService
            // Pour l'instant, on retourne 0
            return $cleaned;
        } catch (\Exception $e) {
            error_log("Erreur nettoyage auto-save: " . $e->getMessage());
            return $cleaned;
        }
    }

    /**
     * Définit l'intervalle d'auto-save
     */
    public static function setAutoSaveInterval(int $seconds): void
    {
        self::$autoSaveInterval = max(10, min(300, $seconds)); // Entre 10s et 5min
    }

    /**
     * Récupère l'intervalle d'auto-save
     */
    public static function getAutoSaveInterval(): int
    {
        return self::$autoSaveInterval;
    }

    /**
     * Génère une clé unique pour un formulaire
     */
    public static function generateFormId(string $page, string $formName = 'main'): string
    {
        return md5($page . '_' . $formName);
    }
}

