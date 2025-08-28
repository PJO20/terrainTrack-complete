<?php

namespace App\Service;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use App\Service\SessionManager;
use App\Service\PermissionService;

class TwigService
{
    private Environment $twig;
    private SessionManager $sessionManager;
    private PermissionService $permissionService;

    public function __construct(SessionManager $sessionManager, PermissionService $permissionService)
    {
        $this->sessionManager = $sessionManager;
        $this->permissionService = $permissionService;
        
        $loader = new FilesystemLoader(__DIR__ . '/../../template');
        $this->twig = new Environment($loader, [
            'cache' => false,
            'debug' => true,
            'auto_reload' => true
        ]);

        // Ajouter les fonctions personnalisées
        $this->addCustomFunctions();
    }

    private function addCustomFunctions(): void
    {
        // Fonction pour vérifier si l'utilisateur a une permission
        $this->twig->addFunction(new \Twig\TwigFunction('hasPermission', function(string $permission): bool {
            $user = $this->sessionManager->getCurrentUser();
            if (!$user) {
                return false;
            }
            return $this->permissionService->hasPermission($user, $permission);
        }));

        // Fonction pour vérifier si l'utilisateur a au moins une des permissions
        $this->twig->addFunction(new \Twig\TwigFunction('hasAnyPermission', function(array $permissions): bool {
            $user = $this->sessionManager->getCurrentUser();
            if (!$user) {
                return false;
            }
            return $this->permissionService->hasAnyPermission($user, $permissions);
        }));

        // Fonction pour vérifier si l'utilisateur a toutes les permissions
        $this->twig->addFunction(new \Twig\TwigFunction('hasAllPermissions', function(array $permissions): bool {
            $user = $this->sessionManager->getCurrentUser();
            if (!$user) {
                return false;
            }
            return $this->permissionService->hasAllPermissions($user, $permissions);
        }));

        // Fonction pour vérifier si l'utilisateur peut accéder à un module
        $this->twig->addFunction(new \Twig\TwigFunction('canAccessModule', function(string $module): bool {
            $user = $this->sessionManager->getCurrentUser();
            if (!$user) {
                return false;
            }
            return $this->permissionService->canAccessModule($user, $module);
        }));

        // Fonction pour vérifier si l'utilisateur peut effectuer une action CRUD
        $this->twig->addFunction(new \Twig\TwigFunction('canPerformAction', function(string $module, string $action): bool {
            $user = $this->sessionManager->getCurrentUser();
            if (!$user) {
                return false;
            }
            return $this->permissionService->canPerformAction($user, $module, $action);
        }));

        // Fonction pour vérifier si l'utilisateur est admin
        $this->twig->addFunction(new \Twig\TwigFunction('isAdmin', function(): bool {
            $user = $this->sessionManager->getCurrentUser();
            return $user && $user->isAdmin();
        }));

        // Fonction pour vérifier si l'utilisateur est super admin
        $this->twig->addFunction(new \Twig\TwigFunction('isSuperAdmin', function(): bool {
            $user = $this->sessionManager->getCurrentUser();
            return $user && $user->isSuperAdmin();
        }));

        // Fonction pour obtenir les permissions de l'utilisateur par module
        $this->twig->addFunction(new \Twig\TwigFunction('getUserPermissionsByModule', function(): array {
            $user = $this->sessionManager->getCurrentUser();
            if (!$user) {
                return [];
            }
            return $this->permissionService->getUserPermissionsByModule($user);
        }));

        // Fonction pour obtenir les rôles de l'utilisateur
        $this->twig->addFunction(new \Twig\TwigFunction('getUserRoles', function(): array {
            $user = $this->sessionManager->getCurrentUser();
            if (!$user) {
                return [];
            }
            return $user->getRoles();
        }));
    }

    public function render(string $template, array $data = []): string
    {
        // Ajouter automatiquement l'utilisateur actuel aux données
        $data['currentUser'] = $this->sessionManager->getCurrentUser();
        
        return $this->twig->render($template, $data);
    }
} 