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
        // Récupérer les données utilisateur enrichies depuis la BDD (forcer la récupération)
        $data['currentUser'] = $this->getEnrichedCurrentUser(true);
        
        return $this->twig->render($template, $data);
    }

    /**
     * Récupère les données utilisateur enrichies depuis la base de données
     * @param bool $forceRefresh Force la récupération depuis la BDD au lieu de la session
     */
    private function getEnrichedCurrentUser(bool $forceRefresh = false): ?array
    {
        $sessionUser = $this->sessionManager->getCurrentUser();
        if (!$sessionUser) {
            return null;
        }
        
        try {
            $pdo = \App\Service\Database::connect();
            
            // D'abord, vérifier quelles colonnes existent
            $stmt = $pdo->query("DESCRIBE users");
            $columns = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            // Construire la requête SELECT selon les colonnes disponibles
            $selectColumns = "id, email";
            if (in_array('username', $columns)) {
                $selectColumns .= ", username";
            }
            if (in_array('first_name', $columns)) {
                $selectColumns .= ", first_name";
            }
            if (in_array('last_name', $columns)) {
                $selectColumns .= ", last_name";
            }
            if (in_array('avatar', $columns)) {
                $selectColumns .= ", avatar";
            }
            if (in_array('is_admin', $columns)) {
                $selectColumns .= ", is_admin";
            }
            
            // Forcer la récupération depuis la BDD si demandé
            if ($forceRefresh) {
                error_log("TwigService: Force refresh des données utilisateur depuis la BDD");
            }
            
            $stmt = $pdo->prepare("SELECT $selectColumns FROM users WHERE id = ?");
            $stmt->execute([$sessionUser['id']]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$user) {
                return $sessionUser;
            }
            
            // Construire le nom selon les colonnes disponibles
            if (isset($user['first_name']) && isset($user['last_name'])) {
                $user['name'] = trim($user['first_name'] . ' ' . $user['last_name']);
            } elseif (isset($user['username'])) {
                $user['name'] = $user['username'];
            } else {
                $user['name'] = $user['email'];
            }
            
            // Générer les initiales
            $initials = '';
            if (isset($user['first_name']) && !empty($user['first_name'])) {
                $initials .= strtoupper(substr($user['first_name'], 0, 1));
            }
            if (isset($user['last_name']) && !empty($user['last_name'])) {
                $initials .= strtoupper(substr($user['last_name'], 0, 1));
            }
            if (empty($initials) && isset($user['username'])) {
                $initials = strtoupper(substr($user['username'], 0, 2));
            }
            if (empty($initials)) {
                $initials = strtoupper(substr($user['email'], 0, 2));
            }
            $user['initials'] = $initials ?: 'U';
            
            // Avatar par défaut
            if (empty(isset($user['avatar']) ? $user['avatar'] : '')) {
                $user['avatar'] = "https://ui-avatars.com/api/?name=" . urlencode($user['initials']) . "&background=2563eb&color=fff&size=128&rounded=true";
            }
            
            $user['role'] = (isset($user['is_admin']) && $user['is_admin']) ? 'admin' : 'user';
            $user['role_display'] = (isset($user['is_admin']) && $user['is_admin']) ? 'Administrateur' : 'Utilisateur';
            
            return $user;
            
        } catch (\Exception $e) {
            error_log("Erreur TwigService::getEnrichedCurrentUser : " . $e->getMessage());
            return $sessionUser;
        }
    }
} 