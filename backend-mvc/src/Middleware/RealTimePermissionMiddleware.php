<?php

namespace App\Middleware;

use App\Service\RealTimePermissionService;
use App\Service\SessionManager;

/**
 * Middleware de vérification des permissions en temps réel
 * Utilise la matrice des permissions de la base de données
 */
class RealTimePermissionMiddleware
{
    private RealTimePermissionService $permissionService;

    public function __construct()
    {
        $this->permissionService = new RealTimePermissionService();
    }

    /**
     * Vérifie si l'utilisateur connecté a une permission spécifique
     */
    public function requirePermission(string $permission): void
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            $this->redirectToLogin();
        }

        if (!$this->permissionService->hasPermission($user['id'], $permission)) {
            $this->handleUnauthorized($permission);
        }
    }

    /**
     * Vérifie si l'utilisateur connecté peut accéder à un module
     */
    public function requireModuleAccess(string $module): void
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            $this->redirectToLogin();
        }

        if (!$this->permissionService->canAccessModule($user['id'], $module)) {
            $this->handleUnauthorized("Module: $module");
        }
    }

    /**
     * Vérifie si l'utilisateur connecté peut effectuer une action CRUD
     */
    public function requireCrudPermission(string $module, string $action): void
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            $this->redirectToLogin();
        }

        if (!$this->permissionService->canPerformAction($user['id'], $module, $action)) {
            $this->handleUnauthorized("$module.$action");
        }
    }

    /**
     * Vérifie si l'utilisateur connecté est administrateur
     */
    public function requireAdmin(): void
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            $this->redirectToLogin();
        }

        if (!$this->permissionService->isAdmin($user['id'])) {
            $this->handleUnauthorized("Admin access");
        }
    }

    /**
     * Vérifie si l'utilisateur connecté peut gérer les permissions
     */
    public function requirePermissionManagement(): void
    {
        $this->requirePermission('roles.manage');
    }

    /**
     * Vérifie si l'utilisateur connecté peut gérer les utilisateurs
     */
    public function requireUserManagement(): void
    {
        $this->requirePermission('users.manage');
    }

    /**
     * Vérifie si l'utilisateur connecté peut accéder au dashboard
     */
    public function requireDashboardAccess(): void
    {
        $this->requireModuleAccess('dashboard');
    }

    /**
     * Récupère l'utilisateur actuellement connecté
     */
    private function getCurrentUser(): ?array
    {
        SessionManager::start();
        return SessionManager::getCurrentUser();
    }

    /**
     * Redirige vers la page de connexion
     */
    private function redirectToLogin(): void
    {
        header('Location: /login');
        exit;
    }

    /**
     * Gère les accès non autorisés
     */
    private function handleUnauthorized(string $permission): void
    {
        $user = $this->getCurrentUser();
        $userId = $user ? $user['id'] : 'unknown';
        $userEmail = $user ? $user['email'] : 'unknown';
        
        // Log de sécurité
        error_log("UNAUTHORIZED ACCESS: User $userId ($userEmail) attempted to access $permission");
        
        // Redirection selon le contexte
        if ($this->isAjaxRequest()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Accès non autorisé',
                'permission_required' => $permission,
                'redirect' => '/unauthorized'
            ]);
        } else {
            header('Location: /unauthorized');
        }
        exit;
    }

    /**
     * Vérifie si la requête est AJAX
     */
    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Test des permissions pour l'utilisateur connecté
     */
    public function testCurrentUserPermissions(): array
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return ['error' => 'Utilisateur non connecté'];
        }

        return $this->permissionService->testUserPermissions($user['id']);
    }
}
