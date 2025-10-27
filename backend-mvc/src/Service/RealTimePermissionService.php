<?php

namespace App\Service;

use PDO;
use App\Service\Database;

/**
 * Service de vérification des permissions en temps réel
 * Utilise la matrice des permissions de la base de données
 */
class RealTimePermissionService
{
    private PDO $pdo;
    private array $permissionCache = [];

    public function __construct()
    {
        $this->pdo = Database::connect();
    }

    /**
     * Vérifie si un utilisateur a une permission spécifique
     * Utilise la matrice des permissions de la base de données
     */
    public function hasPermission(int $userId, string $permission): bool
    {
        // Cache pour optimiser les performances
        $cacheKey = $userId . '_' . $permission;
        if (isset($this->permissionCache[$cacheKey])) {
            return $this->permissionCache[$cacheKey];
        }

        try {
            // Récupérer le rôle de l'utilisateur
            $userStmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !$user['role']) {
                $this->permissionCache[$cacheKey] = false;
                return false;
            }

            // Récupérer l'ID du rôle depuis la table roles
            $roleStmt = $this->pdo->prepare("SELECT id FROM roles WHERE name = ?");
            $roleStmt->execute([$user['role']]);
            $role = $roleStmt->fetch(PDO::FETCH_ASSOC);

            if (!$role) {
                $this->permissionCache[$cacheKey] = false;
                return false;
            }

            // Vérifier si le rôle a cette permission
            $permissionStmt = $this->pdo->prepare("
                SELECT 1 FROM role_permissions 
                WHERE role_id = ? AND permission = ?
            ");
            $permissionStmt->execute([$role['id'], $permission]);
            $hasPermission = $permissionStmt->fetch() !== false;

            $this->permissionCache[$cacheKey] = $hasPermission;
            return $hasPermission;

        } catch (\Exception $e) {
            error_log("Erreur vérification permission: " . $e->getMessage());
            $this->permissionCache[$cacheKey] = false;
            return false;
        }
    }

    /**
     * Vérifie si un utilisateur peut accéder à un module
     */
    public function canAccessModule(int $userId, string $module): bool
    {
        return $this->hasPermission($userId, $module . '.access') ||
               $this->hasPermission($userId, $module . '.read') ||
               $this->hasPermission($userId, $module . '.manage');
    }

    /**
     * Vérifie si un utilisateur peut effectuer une action CRUD
     */
    public function canPerformAction(int $userId, string $module, string $action): bool
    {
        return $this->hasPermission($userId, $module . '.' . $action);
    }

    /**
     * Récupère toutes les permissions d'un utilisateur
     */
    public function getUserPermissions(int $userId): array
    {
        try {
            // Récupérer le rôle de l'utilisateur
            $userStmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !$user['role']) {
                return [];
            }

            // Récupérer l'ID du rôle depuis la table roles
            $roleStmt = $this->pdo->prepare("SELECT id FROM roles WHERE name = ?");
            $roleStmt->execute([$user['role']]);
            $role = $roleStmt->fetch(PDO::FETCH_ASSOC);

            if (!$role) {
                return [];
            }

            // Récupérer toutes les permissions du rôle
            $permissionsStmt = $this->pdo->prepare("
                SELECT permission FROM role_permissions 
                WHERE role_id = ?
                ORDER BY permission
            ");
            $permissionsStmt->execute([$role['id']]);
            $permissions = $permissionsStmt->fetchAll(PDO::FETCH_COLUMN);

            return $permissions;

        } catch (\Exception $e) {
            error_log("Erreur récupération permissions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les permissions par module pour un utilisateur
     */
    public function getUserPermissionsByModule(int $userId): array
    {
        $permissions = $this->getUserPermissions($userId);
        $grouped = [];

        foreach ($permissions as $permission) {
            $parts = explode('.', $permission);
            if (count($parts) >= 2) {
                $module = $parts[0];
                $action = $parts[1];
                
                if (!isset($grouped[$module])) {
                    $grouped[$module] = [];
                }
                $grouped[$module][] = $action;
            }
        }

        return $grouped;
    }

    /**
     * Vérifie si un utilisateur est administrateur
     */
    public function isAdmin(int $userId): bool
    {
        return $this->hasPermission($userId, 'system.manage') ||
               $this->hasPermission($userId, 'roles.manage') ||
               $this->hasPermission($userId, 'users.manage');
    }

    /**
     * Nettoie le cache des permissions pour un utilisateur
     */
    public function clearUserCache(int $userId): void
    {
        foreach ($this->permissionCache as $key => $value) {
            if (strpos($key, $userId . '_') === 0) {
                unset($this->permissionCache[$key]);
            }
        }
    }

    /**
     * Nettoie tout le cache des permissions
     */
    public function clearAllCache(): void
    {
        $this->permissionCache = [];
    }

    /**
     * Test de vérification des permissions pour un utilisateur
     */
    public function testUserPermissions(int $userId): array
    {
        $permissions = $this->getUserPermissions($userId);
        $permissionsByModule = $this->getUserPermissionsByModule($userId);
        
        return [
            'user_id' => $userId,
            'total_permissions' => count($permissions),
            'permissions' => $permissions,
            'permissions_by_module' => $permissionsByModule,
            'is_admin' => $this->isAdmin($userId),
            'can_access_dashboard' => $this->canAccessModule($userId, 'dashboard'),
            'can_manage_users' => $this->hasPermission($userId, 'users.manage'),
            'can_manage_roles' => $this->hasPermission($userId, 'roles.manage'),
        ];
    }
}
