<?php

namespace App\Middleware;

use App\Service\PermissionService;
use App\Service\SessionManager;
use App\Entity\User;

class AuthorizationMiddleware
{
    private PermissionService $permissionService;
    private SessionManager $sessionManager;

    public function __construct(PermissionService $permissionService, SessionManager $sessionManager)
    {
        $this->permissionService = $permissionService;
        $this->sessionManager = $sessionManager;
    }

    /**
     * Vérifie si l'utilisateur a la permission requise
     */
    public function requirePermission(string $permission): void
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            $this->redirectToLogin();
        }

        if (!$this->permissionService->hasPermission($user, $permission)) {
            $this->handleUnauthorized();
        }
    }

    /**
     * Vérifie si l'utilisateur a au moins une des permissions
     */
    public function requireAnyPermission(array $permissions): void
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            $this->redirectToLogin();
        }

        if (!$this->permissionService->hasAnyPermission($user, $permissions)) {
            $this->handleUnauthorized();
        }
    }

    /**
     * Vérifie si l'utilisateur a toutes les permissions
     */
    public function requireAllPermissions(array $permissions): void
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            $this->redirectToLogin();
        }

        if (!$this->permissionService->hasAllPermissions($user, $permissions)) {
            $this->handleUnauthorized();
        }
    }

    /**
     * Vérifie si l'utilisateur peut accéder à un module
     */
    public function requireModuleAccess(string $module): void
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            $this->redirectToLogin();
        }

        if (!$this->permissionService->canAccessModule($user, $module)) {
            $this->handleUnauthorized();
        }
    }

    /**
     * Vérifie si l'utilisateur peut effectuer une action CRUD
     */
    public function requireCrudPermission(string $module, string $action): void
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            $this->redirectToLogin();
        }

        $permission = $module . '.' . $action;
        if (!$this->permissionService->hasPermission($user, $permission)) {
            $this->handleUnauthorized();
        }
    }

    /**
     * Vérifie les permissions pour les actions CRUD spécifiques
     */
    public function requireCreatePermission(string $module): void
    {
        $this->requireCrudPermission($module, 'create');
    }

    public function requireReadPermission(string $module): void
    {
        $this->requireCrudPermission($module, 'read');
    }

    public function requireUpdatePermission(string $module): void
    {
        $this->requireCrudPermission($module, 'update');
    }

    public function requireDeletePermission(string $module): void
    {
        $this->requireCrudPermission($module, 'delete');
    }

    public function requireManagePermission(string $module): void
    {
        $this->requireCrudPermission($module, 'manage');
    }

    /**
     * Vérifie si l'utilisateur est administrateur
     */
    public function requireAdmin(): void
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            $this->redirectToLogin();
        }

        if (!$user->isAdmin() && !$user->isSuperAdmin()) {
            $this->handleUnauthorized();
        }
    }

    /**
     * Vérifie si l'utilisateur est super administrateur
     */
    public function requireSuperAdmin(): void
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            $this->redirectToLogin();
        }

        if (!$user->isSuperAdmin()) {
            $this->handleUnauthorized();
        }
    }

    /**
     * Récupère l'utilisateur actuel
     */
    private function getCurrentUser(): ?User
    {
        // Démarrer la session si nécessaire
        SessionManager::start();
        
        if (!isset($_SESSION['user'])) {
            return null;
        }
        
        // Vérifier l'expiration de la session
        if (SessionManager::isExpired()) {
            SessionManager::logout();
            return null;
        }
        
        // Mettre à jour l'activité
        SessionManager::updateActivity();
        
        // Récupérer l'utilisateur depuis la base de données
        try {
            $pdo = \App\Service\Database::connect();
            
            $userRepository = new \App\Repository\UserRepository($pdo);
            return $userRepository->findById($_SESSION['user']['id']);
        } catch (\Throwable $e) {
            error_log("Erreur lors de la récupération de l'utilisateur : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Redirige vers la page de connexion
     */
    private function redirectToLogin(): void
    {
        header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }

    /**
     * Gère l'accès non autorisé
     */
    private function handleUnauthorized(): void
    {
        if ($this->isAjaxRequest()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Accès refusé',
                'message' => 'Vous n\'avez pas les permissions nécessaires pour effectuer cette action.'
            ]);
            exit;
        } else {
            header('Location: /unauthorized');
            exit;
        }
    }

    /**
     * Vérifie si la requête est AJAX
     */
    private function isAjaxRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Décorateur pour les contrôleurs - vérifie automatiquement les permissions
     */
    public function checkPermission(string $permission): callable
    {
        return function() use ($permission) {
            $this->requirePermission($permission);
        };
    }

    /**
     * Décorateur pour les contrôleurs - vérifie l'accès au module
     */
    public function checkModuleAccess(string $module): callable
    {
        return function() use ($module) {
            $this->requireModuleAccess($module);
        };
    }

    /**
     * Décorateur pour les contrôleurs - vérifie les permissions CRUD
     */
    public function checkCrudPermission(string $module, string $action): callable
    {
        return function() use ($module, $action) {
            $this->requireCrudPermission($module, $action);
        };
    }
} 