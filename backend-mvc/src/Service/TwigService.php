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

        // Filtres de localisation
        $this->addLocalizationFilters();

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
            if (in_array('name', $columns)) {
                $selectColumns .= ", name";
            }
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
            if (in_array('phone', $columns)) {
                $selectColumns .= ", phone";
            }
            if (in_array('location', $columns)) {
                $selectColumns .= ", location";
            }
            if (in_array('department', $columns)) {
                $selectColumns .= ", department";
            }
            if (in_array('role', $columns)) {
                $selectColumns .= ", role";
            }
            
            // Forcer la récupération depuis la BDD si demandé
            if ($forceRefresh) {
                error_log("TwigService: Force refresh des données utilisateur depuis la BDD");
            }
            
            error_log("TwigService: Colonnes sélectionnées: " . $selectColumns);
            
            $stmt = $pdo->prepare("SELECT $selectColumns FROM users WHERE id = ?");
            $stmt->execute([$sessionUser['id']]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            error_log("TwigService: Données utilisateur récupérées: " . print_r($user, true));
            
            if (!$user) {
                error_log("TwigService: Aucune donnée utilisateur trouvée, retour de la session");
                return $sessionUser;
            }
            
            // Construire le nom complet - utiliser la colonne 'name' en priorité
            $fullName = '';
            if (isset($user['name']) && !empty($user['name'])) {
                $fullName = $user['name'];
            } elseif (isset($user['first_name']) && isset($user['last_name'])) {
                $fullName = trim($user['first_name'] . ' ' . $user['last_name']);
            } elseif (isset($user['first_name'])) {
                $fullName = $user['first_name'];
            } else {
                $fullName = $user['email']; // Fallback sur l'email
            }
            $user['name'] = $fullName; // S'assurer que le nom est défini
            
            error_log("TwigService: Nom complet construit: " . $fullName);
            
            // Générer les initiales de manière cohérente
            $user['initials'] = $this->generateInitials($fullName);
            
            error_log("TwigService: Initiales générées: " . $user['initials']);
            
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
    
    /**
     * Génère les initiales à partir du nom complet (même logique que UserSettingsRepository)
     */
    private function generateInitials(string $fullName): string
    {
        $words = explode(' ', trim($fullName));
        $initials = '';
        
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
                if (strlen($initials) >= 2) {
                    break;
                }
            }
        }
        
        return $initials ?: 'U';
    }

    /**
     * Ajoute les filtres de localisation à Twig
     */
    private function addLocalizationFilters(): void
    {
        // Filtre pour formater une date selon les préférences utilisateur
        $this->twig->addFilter(new \Twig\TwigFilter('user_date', function($date, ?string $customFormat = null): string {
            if (!$date) {
                return '';
            }
            
            // Convertir en DateTime si ce n'est pas déjà le cas
            if (is_string($date)) {
                $date = new \DateTime($date);
            } elseif (!$date instanceof \DateTime) {
                return '';
            }
            
            return \App\Service\LocalizationService::formatDate($date, $customFormat);
        }));

        // Filtre pour formater une heure selon les préférences utilisateur
        $this->twig->addFilter(new \Twig\TwigFilter('user_time', function($date, ?string $customFormat = null): string {
            if (!$date) {
                return '';
            }
            
            // Convertir en DateTime si ce n'est pas déjà le cas
            if (is_string($date)) {
                $date = new \DateTime($date);
            } elseif (!$date instanceof \DateTime) {
                return '';
            }
            
            return \App\Service\LocalizationService::formatTime($date, $customFormat);
        }));

        // Filtre pour formater une date et heure complète
        $this->twig->addFilter(new \Twig\TwigFilter('user_datetime', function($date, ?string $customFormat = null): string {
            if (!$date) {
                return '';
            }
            
            // Convertir en DateTime si ce n'est pas déjà le cas
            if (is_string($date)) {
                $date = new \DateTime($date);
            } elseif (!$date instanceof \DateTime) {
                return '';
            }
            
            return \App\Service\LocalizationService::formatDateTime($date, $customFormat);
        }));

        // Fonction pour obtenir la langue de l'utilisateur
        $this->twig->addFunction(new \Twig\TwigFunction('user_language', function(): string {
            return \App\Service\LocalizationService::getLanguage();
        }));

        // Fonction pour obtenir le fuseau horaire de l'utilisateur
        $this->twig->addFunction(new \Twig\TwigFunction('user_timezone', function(): string {
            return \App\Service\LocalizationService::getTimezone();
        }));
    }
} 