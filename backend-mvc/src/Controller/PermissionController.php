<?php

namespace App\Controller;

use App\Service\TwigService;
use App\Service\PermissionService;
use App\Service\SessionManager;
use App\Middleware\AuthorizationMiddleware;
use App\Entity\Role;
use App\Entity\Permission;
use App\Entity\User;

class PermissionController
{
    private TwigService $twig;
    private PermissionService $permissionService;
    private SessionManager $sessionManager;
    private AuthorizationMiddleware $auth;

    public function __construct(
        TwigService $twig,
        PermissionService $permissionService,
        SessionManager $sessionManager,
        AuthorizationMiddleware $auth
    ) {
        $this->twig = $twig;
        $this->permissionService = $permissionService;
        $this->sessionManager = $sessionManager;
        $this->auth = $auth;
    }

    /**
     * Page d'administration des permissions
     */
    public function index(): string
    {
        $this->auth->requirePermission('roles.manage');

        $roles = $this->permissionService->getAllRoles();
        $permissions = $this->permissionService->getPermissionsByModule();
        $currentUser = $this->sessionManager->getCurrentUser();

        return $this->twig->render('permissions/index.html.twig', [
            'roles' => $roles,
            'permissions' => $permissions,
            'currentUser' => $currentUser
        ]);
    }

    /**
     * Page de gestion des rôles
     */
    public function roles(): string
    {
        $this->auth->requirePermission('roles.manage');

        $roles = $this->permissionService->getAllRoles();
        $permissions = $this->permissionService->getAllPermissions();
        $currentUser = $this->sessionManager->getCurrentUser();

        return $this->twig->render('permissions/roles.html.twig', [
            'roles' => $roles,
            'permissions' => $permissions,
            'currentUser' => $currentUser
        ]);
    }

    /**
     * Page de gestion des utilisateurs et leurs permissions
     */
    public function users(): string
    {
        $this->auth->requirePermission('users.manage');

        $users = $this->getUsersWithRoles();
        $roles = $this->permissionService->getAllRoles();
        $currentUser = $this->sessionManager->getCurrentUser();

        return $this->twig->render('permissions/users.html.twig', [
            'users' => $users,
            'roles' => $roles,
            'currentUser' => $currentUser
        ]);
    }

    /**
     * API pour assigner un rôle à un utilisateur
     */
    public function assignRoleToUser(): void
    {
        $this->auth->requirePermission('users.manage');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            return;
        }

        $userId = $_POST['user_id'] ?? null;
        $roleId = $_POST['role_id'] ?? null;

        if (!$userId || !$roleId) {
            http_response_code(400);
            echo json_encode(['error' => 'Paramètres manquants']);
            return;
        }

        $user = $this->getUserById($userId);
        $role = $this->getRoleById($roleId);

        if (!$user || !$role) {
            http_response_code(404);
            echo json_encode(['error' => 'Utilisateur ou rôle non trouvé']);
            return;
        }

        $success = $this->permissionService->assignRoleToUser($user, $role);

        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Rôle assigné avec succès']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de l\'assignation du rôle']);
        }
    }

    /**
     * API pour retirer un rôle d'un utilisateur
     */
    public function removeRoleFromUser(): void
    {
        $this->auth->requirePermission('users.manage');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            return;
        }

        $userId = $_POST['user_id'] ?? null;
        $roleId = $_POST['role_id'] ?? null;

        if (!$userId || !$roleId) {
            http_response_code(400);
            echo json_encode(['error' => 'Paramètres manquants']);
            return;
        }

        $user = $this->getUserById($userId);
        $role = $this->getRoleById($roleId);

        if (!$user || !$role) {
            http_response_code(404);
            echo json_encode(['error' => 'Utilisateur ou rôle non trouvé']);
            return;
        }

        $success = $this->permissionService->removeRoleFromUser($user, $role);

        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Rôle retiré avec succès']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la suppression du rôle']);
        }
    }

    /**
     * API pour assigner une permission à un rôle
     */
    public function assignPermissionToRole(): void
    {
        $this->auth->requirePermission('roles.manage');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            return;
        }

        $roleId = $_POST['role_id'] ?? null;
        $permission = $_POST['permission'] ?? null;

        if (!$roleId || !$permission) {
            http_response_code(400);
            echo json_encode(['error' => 'Paramètres manquants']);
            return;
        }

        $role = $this->getRoleById($roleId);

        if (!$role) {
            http_response_code(404);
            echo json_encode(['error' => 'Rôle non trouvé']);
            return;
        }

        $success = $this->permissionService->assignPermissionToRole($role, $permission);

        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Permission assignée avec succès']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de l\'assignation de la permission']);
        }
    }

    /**
     * API pour retirer une permission d'un rôle
     */
    public function removePermissionFromRole(): void
    {
        $this->auth->requirePermission('roles.manage');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            return;
        }

        $roleId = $_POST['role_id'] ?? null;
        $permission = $_POST['permission'] ?? null;

        if (!$roleId || !$permission) {
            http_response_code(400);
            echo json_encode(['error' => 'Paramètres manquants']);
            return;
        }

        $role = $this->getRoleById($roleId);

        if (!$role) {
            http_response_code(404);
            echo json_encode(['error' => 'Rôle non trouvé']);
            return;
        }

        $success = $this->permissionService->removePermissionFromRole($role, $permission);

        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Permission retirée avec succès']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la suppression de la permission']);
        }
    }

    /**
     * API pour créer un nouveau rôle
     */
    public function createRole(): void
    {
        $this->auth->requirePermission('roles.create');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            return;
        }

        $name = $_POST['name'] ?? '';
        $displayName = $_POST['display_name'] ?? '';
        $description = $_POST['description'] ?? '';
        $permissions = $_POST['permissions'] ?? [];

        if (!$name || !$displayName) {
            http_response_code(400);
            echo json_encode(['error' => 'Nom et nom d\'affichage requis']);
            return;
        }

        $role = new Role();
        $role->setName($name);
        $role->setDisplayName($displayName);
        $role->setDescription($description);
        $role->setPermissions($permissions);

        $success = $this->saveRole($role);

        if ($success) {
            echo json_encode([
                'success' => true, 
                'message' => 'Rôle créé avec succès',
                'role' => [
                    'id' => $role->getId(),
                    'name' => $role->getName(),
                    'display_name' => $role->getDisplayName()
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la création du rôle']);
        }
    }

    /**
     * API pour supprimer un rôle
     */
    public function deleteRole(): void
    {
        $this->auth->requirePermission('roles.delete');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            return;
        }

        $roleId = $_POST['role_id'] ?? null;

        if (!$roleId) {
            http_response_code(400);
            echo json_encode(['error' => 'ID du rôle requis']);
            return;
        }

        $role = $this->getRoleById($roleId);

        if (!$role) {
            http_response_code(404);
            echo json_encode(['error' => 'Rôle non trouvé']);
            return;
        }

        if ($this->isRoleInUse($role)) {
            http_response_code(400);
            echo json_encode(['error' => 'Ce rôle est utilisé par des utilisateurs et ne peut pas être supprimé']);
            return;
        }

        $success = $this->deleteRoleFromDatabase($role);

        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Rôle supprimé avec succès']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la suppression du rôle']);
        }
    }

    /**
     * Page de profil utilisateur avec ses permissions
     */
    public function profile(): string
    {
        $this->auth->requirePermission('users.read');

        $currentUser = $this->sessionManager->getCurrentUser();
        $userPermissions = $this->permissionService->getUserPermissionsByModule($currentUser);
        $userRoles = $currentUser->getRoles();

        return $this->twig->render('permissions/profile.html.twig', [
            'user' => $currentUser,
            'permissions' => $userPermissions,
            'roles' => $userRoles
        ]);
    }

    /**
     * Page d'administration des permissions (nouvelle interface)
     */
    public function admin(): string
    {
        $this->auth->requirePermission('roles.manage');

        $roles = $this->permissionService->getAllRoles();
        $permissions = $this->permissionService->getPermissionsByModule();
        $users = $this->getUsersWithRoles();
        $allPermissions = $this->permissionService->getAllPermissions();
        $currentUser = $this->sessionManager->getCurrentUser();

        // Grouper les permissions par module pour l'affichage
        $permissionsByModule = [];
        foreach ($allPermissions as $permission) {
            $module = $permission->getModule();
            if (!isset($permissionsByModule[$module])) {
                $permissionsByModule[$module] = [];
            }
            $permissionsByModule[$module][] = $permission;
        }

        // Extraire les modules uniques
        $modules = array_keys($permissionsByModule);

        return $this->twig->render('permissions/admin.html.twig', [
            'roles' => $roles,
            'permissions' => $permissionsByModule,
            'users' => $users,
            'modules' => $modules,
            'currentUser' => $currentUser
        ]);
    }

    // Méthodes utilitaires privées
    private function getUserById(int $id): ?User
    {
        $userRepository = new \App\Repository\UserRepository($this->getPdo());
        return $userRepository->findById($id);
    }

    private function getRoleById(int $id): ?Role
    {
        $roleRepository = new \App\Repository\RoleRepository($this->getPdo());
        return $roleRepository->findById($id);
    }

    private function getUsersWithRoles(): array
    {
        $userRepository = new \App\Repository\UserRepository($this->getPdo());
        return $userRepository->findAll();
    }

    private function saveRole(Role $role): bool
    {
        $roleRepository = new \App\Repository\RoleRepository($this->getPdo());
        return $roleRepository->save($role);
    }

    private function isRoleInUse(Role $role): bool
    {
        $roleRepository = new \App\Repository\RoleRepository($this->getPdo());
        return $roleRepository->isRoleInUse($role->getId());
    }

    private function deleteRoleFromDatabase(Role $role): bool
    {
        $roleRepository = new \App\Repository\RoleRepository($this->getPdo());
        return $roleRepository->delete($role->getId());
    }

    private function getPdo(): PDO
    {
        return \App\Service\Database::connect();
    }
} 