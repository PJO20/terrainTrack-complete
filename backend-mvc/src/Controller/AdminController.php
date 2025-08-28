<?php

namespace App\Controller;

use App\Service\TwigService;
use App\Service\PermissionService;
use App\Service\SessionManager;
use App\Middleware\AuthorizationMiddleware;
use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use App\Repository\PermissionRepository;
use App\Entity\Role;
use App\Entity\Permission;
use App\Entity\User;

class AdminController
{
    private TwigService $twig;
    private PermissionService $permissionService;
    private AuthorizationMiddleware $auth;
    private SessionManager $sessionManager;

    public function __construct(
        TwigService $twig,
        PermissionService $permissionService,
        AuthorizationMiddleware $auth,
        SessionManager $sessionManager
    ) {
        $this->twig = $twig;
        $this->permissionService = $permissionService;
        $this->auth = $auth;
        $this->sessionManager = $sessionManager;
    }

    /**
     * Page d'accueil de l'administration
     */
    public function index(): string
    {
        $this->auth->requirePermission('system.admin');

        $stats = [
            'users' => count($this->permissionService->getAllUsers()),
            'roles' => count($this->permissionService->getAllRoles()),
            'permissions' => count($this->permissionService->getAllPermissions())
        ];

        return $this->twig->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'currentUser' => $this->sessionManager->getCurrentUser()
        ]);
    }

    /**
     * Gestion des utilisateurs
     */
    public function users(): string
    {
        $this->auth->requirePermission('users.manage');

        $users = $this->permissionService->getAllUsers();
        $roles = $this->permissionService->getAllRoles();

        return $this->twig->render('admin/users.html.twig', [
            'users' => $users,
            'roles' => $roles,
            'currentUser' => $this->sessionManager->getCurrentUser()
        ]);
    }

    /**
     * Gestion des rôles
     */
    public function roles(): string
    {
        $this->auth->requirePermission('roles.manage');

        $roles = $this->permissionService->getAllRoles();
        $permissions = $this->permissionService->getPermissionsByModule();

        return $this->twig->render('admin/roles.html.twig', [
            'roles' => $roles,
            'permissions' => $permissions,
            'currentUser' => $this->sessionManager->getCurrentUser()
        ]);
    }

    /**
     * Gestion des permissions
     */
    public function permissions(): string
    {
        $this->auth->requirePermission('system.admin');

        $permissions = $this->permissionService->getPermissionsByModule();
        $allPermissions = $this->permissionService->getAllPermissions();

        return $this->twig->render('admin/permissions.html.twig', [
            'permissions' => $permissions,
            'allPermissions' => $allPermissions,
            'currentUser' => $this->sessionManager->getCurrentUser()
        ]);
    }

    /**
     * Assigner un rôle à un utilisateur
     */
    public function assignRole(): void
    {
        $this->auth->requirePermission('users.manage');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/users');
            exit;
        }

        $userId = (int)$_POST['user_id'];
        $roleId = (int)$_POST['role_id'];

        $user = $this->getUserById($userId);
        $role = $this->getRoleById($roleId);

        if ($user && $role) {
            $this->permissionService->assignRoleToUser($user, $role);
            header('Location: /admin/users?success=role_assigned');
        } else {
            header('Location: /admin/users?error=invalid_data');
        }
        exit;
    }

    /**
     * Retirer un rôle d'un utilisateur
     */
    public function removeRole(): void
    {
        $this->auth->requirePermission('users.manage');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/users');
            exit;
        }

        $userId = (int)$_POST['user_id'];
        $roleId = (int)$_POST['role_id'];

        $user = $this->getUserById($userId);
        $role = $this->getRoleById($roleId);

        if ($user && $role) {
            $this->permissionService->removeRoleFromUser($user, $role);
            header('Location: /admin/users?success=role_removed');
        } else {
            header('Location: /admin/users?error=invalid_data');
        }
        exit;
    }

    /**
     * Créer un nouveau rôle
     */
    public function createRole(): void
    {
        $this->auth->requirePermission('roles.manage');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/roles');
            exit;
        }

        $role = new Role();
        $role->setName($_POST['name']);
        $role->setDisplayName($_POST['display_name']);
        $role->setDescription($_POST['description'] ?? '');
        
        $permissions = $_POST['permissions'] ?? [];
        $role->setPermissions($permissions);

        if ($this->saveRole($role)) {
            header('Location: /admin/roles?success=role_created');
        } else {
            header('Location: /admin/roles?error=creation_failed');
        }
        exit;
    }

    /**
     * Mettre à jour un rôle
     */
    public function updateRole(): void
    {
        $this->auth->requirePermission('roles.manage');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/roles');
            exit;
        }

        $roleId = (int)$_POST['role_id'];
        $role = $this->getRoleById($roleId);

        if ($role) {
            $role->setName($_POST['name']);
            $role->setDisplayName($_POST['display_name']);
            $role->setDescription($_POST['description'] ?? '');
            
            $permissions = $_POST['permissions'] ?? [];
            $role->setPermissions($permissions);

            if ($this->saveRole($role)) {
                header('Location: /admin/roles?success=role_updated');
            } else {
                header('Location: /admin/roles?error=update_failed');
            }
        } else {
            header('Location: /admin/roles?error=role_not_found');
        }
        exit;
    }

    /**
     * Supprimer un rôle
     */
    public function deleteRole(): void
    {
        $this->auth->requirePermission('roles.manage');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/roles');
            exit;
        }

        $roleId = (int)$_POST['role_id'];
        $role = $this->getRoleById($roleId);

        if ($role && !$this->isRoleInUse($role)) {
            $roleRepository = new RoleRepository($this->getPdo());
            if ($roleRepository->delete($roleId)) {
                header('Location: /admin/roles?success=role_deleted');
            } else {
                header('Location: /admin/roles?error=deletion_failed');
            }
        } else {
            header('Location: /admin/roles?error=role_in_use');
        }
        exit;
    }

    // Méthodes utilitaires privées
    private function getUserById(int $id): ?User
    {
        $userRepository = new UserRepository($this->getPdo());
        return $userRepository->findById($id);
    }

    private function getRoleById(int $id): ?Role
    {
        $roleRepository = new RoleRepository($this->getPdo());
        return $roleRepository->findById($id);
    }

    private function saveRole(Role $role): bool
    {
        $roleRepository = new RoleRepository($this->getPdo());
        return $roleRepository->save($role);
    }

    private function isRoleInUse(Role $role): bool
    {
        $roleRepository = new RoleRepository($this->getPdo());
        return $roleRepository->isRoleInUse($role->getId());
    }

    private function getPdo(): \PDO
    {
        return \App\Service\Database::connect();
    }
} 