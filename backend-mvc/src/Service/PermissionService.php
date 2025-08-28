<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Role;
use App\Entity\Permission;
use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use App\Repository\PermissionRepository;

class PermissionService
{
    private UserRepository $userRepository;
    private RoleRepository $roleRepository;
    private PermissionRepository $permissionRepository;
    private array $permissionCache = [];

    public function __construct(
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        PermissionRepository $permissionRepository
    ) {
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->permissionRepository = $permissionRepository;
    }

    /**
     * Vérifie si un utilisateur a une permission spécifique
     */
    public function hasPermission(User $user, string $permission): bool
    {
        // Cache pour optimiser les performances
        $cacheKey = $user->getId() . '_' . $permission;
        if (isset($this->permissionCache[$cacheKey])) {
            return $this->permissionCache[$cacheKey];
        }

        $hasPermission = $user->hasPermission($permission);
        $this->permissionCache[$cacheKey] = $hasPermission;

        return $hasPermission;
    }

    /**
     * Vérifie si un utilisateur a au moins une des permissions
     */
    public function hasAnyPermission(User $user, array $permissions): bool
    {
        return $user->hasAnyPermission($permissions);
    }

    /**
     * Vérifie si un utilisateur a toutes les permissions
     */
    public function hasAllPermissions(User $user, array $permissions): bool
    {
        return $user->hasAllPermissions($permissions);
    }

    /**
     * Vérifie si un utilisateur peut accéder à un module
     */
    public function canAccessModule(User $user, string $module): bool
    {
        return $this->hasPermission($user, $module . '.access');
    }

    /**
     * Vérifie si un utilisateur peut effectuer une action sur une ressource
     */
    public function canPerformAction(User $user, string $module, string $action): bool
    {
        return $this->hasPermission($user, $module . '.' . $action);
    }

    /**
     * Récupère toutes les permissions d'un utilisateur (directes + via rôles)
     */
    public function getUserPermissions(User $user): array
    {
        $permissions = $user->getPermissions();

        // Ajouter les permissions des rôles
        foreach ($user->getRoles() as $role) {
            $rolePermissions = $role->getPermissions();
            $permissions = array_merge($permissions, $rolePermissions);
        }

        return array_unique($permissions);
    }

    /**
     * Récupère les permissions par module pour un utilisateur
     */
    public function getUserPermissionsByModule(User $user): array
    {
        $permissions = $this->getUserPermissions($user);
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
     * Assigne un rôle à un utilisateur
     */
    public function assignRoleToUser(User $user, Role $role): bool
    {
        try {
            $user->addRole($role);
            $this->userRepository->updateUserRoles($user);
            $this->clearUserCache($user);
            return true;
        } catch (\Exception $e) {
            error_log("Erreur lors de l'assignation du rôle: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retire un rôle d'un utilisateur
     */
    public function removeRoleFromUser(User $user, Role $role): bool
    {
        try {
            $user->removeRole($role);
            $this->userRepository->updateUserRoles($user);
            $this->clearUserCache($user);
            return true;
        } catch (\Exception $e) {
            error_log("Erreur lors de la suppression du rôle: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Assigne une permission directe à un utilisateur
     */
    public function assignPermissionToUser(User $user, string $permission): bool
    {
        try {
            $user->addPermission($permission);
            $this->userRepository->updateUserPermissions($user);
            $this->clearUserCache($user);
            return true;
        } catch (\Exception $e) {
            error_log("Erreur lors de l'assignation de la permission: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retire une permission directe d'un utilisateur
     */
    public function removePermissionFromUser(User $user, string $permission): bool
    {
        try {
            $user->removePermission($permission);
            $this->userRepository->updateUserPermissions($user);
            $this->clearUserCache($user);
            return true;
        } catch (\Exception $e) {
            error_log("Erreur lors de la suppression de la permission: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Assigne une permission à un rôle
     */
    public function assignPermissionToRole(Role $role, string $permission): bool
    {
        try {
            $role->addPermission($permission);
            $this->roleRepository->updateRolePermissions($role);
            $this->clearRoleCache($role);
            return true;
        } catch (\Exception $e) {
            error_log("Erreur lors de l'assignation de la permission au rôle: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retire une permission d'un rôle
     */
    public function removePermissionFromRole(Role $role, string $permission): bool
    {
        try {
            $role->removePermission($permission);
            $this->roleRepository->updateRolePermissions($role);
            $this->clearRoleCache($role);
            return true;
        } catch (\Exception $e) {
            error_log("Erreur lors de la suppression de la permission du rôle: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère tous les rôles disponibles
     */
    public function getAllRoles(): array
    {
        return $this->roleRepository->findAll();
    }

    /**
     * Récupère toutes les permissions disponibles
     */
    public function getAllPermissions(): array
    {
        return $this->permissionRepository->findAll();
    }

    /**
     * Récupère les permissions par module
     */
    public function getPermissionsByModule(): array
    {
        $permissions = $this->getAllPermissions();
        $grouped = [];

        foreach ($permissions as $permission) {
            $module = $permission->getModule();
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = $permission;
        }

        return $grouped;
    }

    /**
     * Vérifie les permissions pour les actions CRUD
     */
    public function canCreate(User $user, string $module): bool
    {
        return $this->hasPermission($user, $module . '.create');
    }

    public function canRead(User $user, string $module): bool
    {
        return $this->hasPermission($user, $module . '.read');
    }

    public function canUpdate(User $user, string $module): bool
    {
        return $this->hasPermission($user, $module . '.update');
    }

    public function canDelete(User $user, string $module): bool
    {
        return $this->hasPermission($user, $module . '.delete');
    }

    public function canManage(User $user, string $module): bool
    {
        return $this->hasPermission($user, $module . '.manage');
    }

    /**
     * Nettoie le cache des permissions pour un utilisateur
     */
    private function clearUserCache(User $user): void
    {
        $userId = $user->getId();
        foreach ($this->permissionCache as $key => $value) {
            if (strpos($key, $userId . '_') === 0) {
                unset($this->permissionCache[$key]);
            }
        }
    }

    /**
     * Nettoie le cache des permissions pour un rôle
     */
    private function clearRoleCache(Role $role): void
    {
        // Vider tout le cache car les rôles affectent tous les utilisateurs
        $this->permissionCache = [];
    }

    /**
     * Définit les permissions par défaut pour TerrainTrack
     */
    public function getDefaultPermissions(): array
    {
        return [
            // Système
            'system.access' => 'Accès au système',
            'system.settings' => 'Gestion des paramètres',
            'system.logs' => 'Consultation des logs',

            // Utilisateurs
            'users.access' => 'Accès aux utilisateurs',
            'users.read' => 'Lire les utilisateurs',
            'users.create' => 'Créer des utilisateurs',
            'users.update' => 'Modifier les utilisateurs',
            'users.delete' => 'Supprimer les utilisateurs',
            'users.manage' => 'Gestion complète des utilisateurs',

            // Rôles
            'roles.access' => 'Accès aux rôles',
            'roles.read' => 'Lire les rôles',
            'roles.create' => 'Créer des rôles',
            'roles.update' => 'Modifier les rôles',
            'roles.delete' => 'Supprimer les rôles',
            'roles.manage' => 'Gestion complète des rôles',

            // Véhicules
            'vehicles.access' => 'Accès aux véhicules',
            'vehicles.read' => 'Lire les véhicules',
            'vehicles.create' => 'Créer des véhicules',
            'vehicles.update' => 'Modifier les véhicules',
            'vehicles.delete' => 'Supprimer les véhicules',
            'vehicles.manage' => 'Gestion complète des véhicules',

            // Interventions
            'interventions.access' => 'Accès aux interventions',
            'interventions.read' => 'Lire les interventions',
            'interventions.create' => 'Créer des interventions',
            'interventions.update' => 'Modifier les interventions',
            'interventions.delete' => 'Supprimer les interventions',
            'interventions.manage' => 'Gestion complète des interventions',

            // Équipes
            'teams.access' => 'Accès aux équipes',
            'teams.read' => 'Lire les équipes',
            'teams.create' => 'Créer des équipes',
            'teams.update' => 'Modifier les équipes',
            'teams.delete' => 'Supprimer les équipes',
            'teams.manage' => 'Gestion complète des équipes',

            // Carte
            'map.access' => 'Accès à la carte',
            'map.view' => 'Voir la carte',
            'map.edit' => 'Modifier la carte',

            // Notifications
            'notifications.access' => 'Accès aux notifications',
            'notifications.read' => 'Lire les notifications',
            'notifications.create' => 'Créer des notifications',
            'notifications.update' => 'Modifier les notifications',
            'notifications.delete' => 'Supprimer les notifications',
            'notifications.manage' => 'Gestion complète des notifications',

            // Rapports
            'reports.access' => 'Accès aux rapports',
            'reports.read' => 'Lire les rapports',
            'reports.create' => 'Créer des rapports',
            'reports.export' => 'Exporter les rapports',

            // Dashboard
            'dashboard.access' => 'Accès au tableau de bord',
            'dashboard.view' => 'Voir le tableau de bord',
            'dashboard.manage' => 'Gérer le tableau de bord',
        ];
    }

    /**
     * Définit les rôles par défaut pour TerrainTrack
     */
    public function getDefaultRoles(): array
    {
        return [
            'super_admin' => [
                'display_name' => 'Super Administrateur',
                'description' => 'Accès complet à toutes les fonctionnalités',
                'permissions' => array_keys($this->getDefaultPermissions())
            ],
            'admin' => [
                'display_name' => 'Administrateur',
                'description' => 'Gestion complète du système',
                'permissions' => [
                    'system.access', 'system.settings',
                    'users.access', 'users.read', 'users.create', 'users.update',
                    'roles.access', 'roles.read',
                    'vehicles.manage', 'interventions.manage', 'teams.manage',
                    'map.access', 'map.view', 'map.edit',
                    'notifications.manage', 'reports.access', 'reports.read',
                    'dashboard.access', 'dashboard.view'
                ]
            ],
            'manager' => [
                'display_name' => 'Manager',
                'description' => 'Gestion des équipes et interventions',
                'permissions' => [
                    'vehicles.read', 'vehicles.create', 'vehicles.update',
                    'interventions.manage', 'teams.manage',
                    'map.access', 'map.view',
                    'notifications.access', 'notifications.read',
                    'reports.access', 'reports.read',
                    'dashboard.access', 'dashboard.view'
                ]
            ],
            'technician' => [
                'display_name' => 'Technicien',
                'description' => 'Exécution des interventions',
                'permissions' => [
                    'vehicles.read',
                    'interventions.read', 'interventions.update',
                    'map.access', 'map.view',
                    'notifications.access', 'notifications.read',
                    'dashboard.access', 'dashboard.view'
                ]
            ],
            'viewer' => [
                'display_name' => 'Observateur',
                'description' => 'Consultation uniquement',
                'permissions' => [
                    'vehicles.read',
                    'interventions.read',
                    'map.access', 'map.view',
                    'notifications.access', 'notifications.read',
                    'dashboard.access', 'dashboard.view'
                ]
            ]
        ];
    }
} 