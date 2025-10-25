<?php
namespace App\Router;

use App\Container\Container;
use App\Controller\InterventionController;
use App\Controller\HomeController;
use App\Controller\AuthController;
use App\Controller\DashboardController;
use App\Controller\MapViewController;
use App\Controller\VehicleController;
use App\Controller\TeamController;
use App\Controller\SettingsController;
use App\Controller\ReportsController;
use App\Controller\NotificationController;
use App\Controller\HelpController;
use App\Controller\ProfileController;
use App\Controller\PermissionController;
use App\Controller\PermissionsManagementController;
use App\Controller\NotificationPreferencesController;
use App\Controller\ForgotPasswordController;

class Router
{
    private array $routes = [];
    private array $namedRoutes = [];
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->initializeRoutes();
    }

    public function add(string $name, string $path, array $action, string $method = 'GET'): void
    {
        $routeKey = $method . ':' . $path;
        $this->routes[$routeKey] = $action;
        $this->namedRoutes[$name] = $path;
    }

    private function initializeRoutes()
    {
        $this->add('home', '/', ['App\Controller\HomeController', 'index']);
        $this->add('index.php', 'index.php', ['App\Controller\HomeController', 'index']);
        $this->add('login', '/login', ['App\Controller\AuthController', 'login'], 'GET');
        $this->add('login_post', '/login', ['App\Controller\AuthController', 'login'], 'POST');
        $this->add('auth_login', '/auth/login', ['App\Controller\AuthController', 'login'], 'GET');
        $this->add('auth_login_post', '/auth/login', ['App\Controller\AuthController', 'login'], 'POST');
        $this->add('register', '/register', ['App\Controller\AuthController', 'register'], 'GET');
        $this->add('register_post', '/register', ['App\Controller\AuthController', 'register'], 'POST');
        $this->add('auth_register', '/auth/register', ['App\Controller\AuthController', 'register'], 'GET');
        $this->add('auth_register_post', '/auth/register', ['App\Controller\AuthController', 'register'], 'POST');
        $this->add('forgot_password', '/forgot-password', ['App\Controller\ForgotPasswordControllerSimple', 'showForgotPassword'], 'GET');
        $this->add('forgot_password_post', '/forgot-password', ['App\Controller\ForgotPasswordControllerSimple', 'handleForgotPassword'], 'POST');
        $this->add('reset_password', '/reset-password', ['App\Controller\ResetPasswordControllerSimple', 'showResetPassword'], 'GET');
        $this->add('reset_password_post', '/reset-password', ['App\Controller\ResetPasswordControllerSimple', 'handleResetPassword'], 'POST');
        $this->add('logout', '/auth/logout', ['App\Controller\AuthController', 'logout']);
        $this->add('unauthorized', '/unauthorized', ['App\Controller\AuthController', 'unauthorized']);
        $this->add('dashboard', '/dashboard', ['App\Controller\DashboardController', 'index']);
        $this->add('map_view', '/map-view', ['App\Controller\MapViewController', 'index']);
        $this->add('map_api_data', '/map-api-data', ['App\Controller\MapViewController', 'apiData']);
        $this->add('settings', '/settings', ['App\Controller\SettingsController', 'index']);
        $this->add('settings_update_profile', '/settings/update-profile', ['App\Controller\SettingsController', 'updateProfile'], 'POST');
        $this->add('settings_update_notifications', '/settings/update-notifications', ['App\Controller\SettingsController', 'updateNotifications'], 'POST');
        $this->add('settings_update_appearance', '/settings/update-appearance', ['App\Controller\SettingsController', 'updateAppearance'], 'POST');
        $this->add('settings_update_autosave', '/settings/update-autosave', ['App\Controller\SettingsController', 'updateAutoSave'], 'POST');
        $this->add('settings_update_system', '/settings/update-system', ['App\Controller\SettingsController', 'updateSystemSettings'], 'POST');
        $this->add('settings_change_password', '/settings/change-password', ['App\Controller\SettingsController', 'changePassword'], 'POST');
        
        // Routes de sécurité
        $this->add('security_update_session_timeout', '/settings/security/update-session-timeout', ['App\Controller\SecurityController', 'updateSessionTimeout'], 'POST');
        $this->add('security_2fa', '/settings/security/2fa', ['App\Controller\SecurityController', 'handle2FA'], 'POST');
        
        // Routes pour l'authentification à deux facteurs
        $this->add('two_factor_index', '/security/two-factor', ['App\Controller\SimpleTwoFactorController', 'index']);
        $this->add('two_factor_enable', '/security/two-factor/enable', ['App\Controller\TwoFactorController', 'enable']);
        $this->add('two_factor_verify', '/security/two-factor/verify', ['App\Controller\TwoFactorController', 'verify']);
        $this->add('two_factor_disable', '/security/two-factor/disable', ['App\Controller\TwoFactorController', 'disable']);
        $this->add('two_factor_verify_page', '/auth/verify-2fa', ['App\Controller\TwoFactorController', 'verifyPage']);
        $this->add('two_factor_verify_login', '/auth/verify-2fa/process', ['App\Controller\TwoFactorController', 'verifyLogin']);
        $this->add('two_factor_resend', '/auth/verify-2fa/resend', ['App\Controller\TwoFactorController', 'resendCode']);
        
        // Routes de gestion des permissions (accès sécurisé)
        $this->add('permissions_management', '/permissions/management', ['App\\Controller\\PermissionsManagementController', 'management']);
        $this->add('permissions_session_check', '/permissions/session-check', ['App\\Controller\\PermissionsManagementController', 'checkSessionStatus']);
        
        $this->add('profile', '/profile', ['App\Controller\ProfileController', 'index']);
        $this->add('profile_update', '/profile/update', ['App\Controller\ProfileController', 'update']);
        $this->add('help_center', '/help', ['App\Controller\HelpController', 'index']);
        $this->add('reports', '/reports', ['App\Controller\ReportsController', 'index']);
        $this->add('notifications', '/notifications', ['App\Controller\NotificationController', 'index']);
        $this->add('notifications_recent', '/notifications/recent', ['App\Controller\NotificationController', 'recent']);
        $this->add('notifications_settings', '/notifications/settings', ['App\Controller\NotificationController', 'settings']);
        $this->add('notifications_mark_all_read', '/notifications/mark-all-read', ['App\Controller\NotificationController', 'markAllRead'], 'POST');
        $this->add('notifications_mark_read', '/notifications/mark-read', ['App\Controller\NotificationController', 'markRead'], 'POST');
        $this->add('notifications_mark_unread', '/notifications/mark-unread', ['App\Controller\NotificationController', 'markUnread'], 'POST');
        $this->add('notifications_delete', '/notifications/delete', ['App\Controller\NotificationController', 'delete'], 'POST');
        $this->add('api_notifications', '/api/notifications', ['App\Controller\NotificationController', 'recent']);
        $this->add('api_autosave', '/api/autosave', ['App\Controller\AutoSaveController', 'handleRequest']);
        
        // Routes avec noms d'équipes (alpha, beta, gamma) - AVANT les routes génériques
        $this->add('teams_alpha_members_create', '/teams/alpha/members/create', ['App\Controller\TeamController', 'createMemberAlpha']);
        $this->add('teams_beta_members_create', '/teams/beta/members/create', ['App\Controller\TeamController', 'createMemberBeta']);
        $this->add('teams_gamma_members_create', '/teams/gamma/members/create', ['App\Controller\TeamController', 'createMemberGamma']);
        $this->add('teams_alpha_members_store', '/teams/alpha/members/store', ['App\Controller\TeamController', 'storeMemberAlpha']);
        $this->add('teams_beta_members_store', '/teams/beta/members/store', ['App\Controller\TeamController', 'storeMemberBeta']);
        $this->add('teams_gamma_members_store', '/teams/gamma/members/store', ['App\Controller\TeamController', 'storeMemberGamma']);
        $this->add('teams_alpha_edit', '/teams/alpha/edit', ['App\Controller\TeamController', 'editAlpha']);
        $this->add('teams_beta_edit', '/teams/beta/edit', ['App\Controller\TeamController', 'editBeta']);
        $this->add('teams_gamma_edit', '/teams/gamma/edit', ['App\Controller\TeamController', 'editGamma']);
        $this->add('teams_alpha_show', '/teams/alpha', ['App\Controller\TeamController', 'showAlpha']);
        $this->add('teams_beta_show', '/teams/beta', ['App\Controller\TeamController', 'showBeta']);
        $this->add('teams_gamma_show', '/teams/gamma', ['App\Controller\TeamController', 'showGamma']);
        
        // Routes génériques pour les équipes
        $this->add('teams_list', '/teams', ['App\Controller\TeamController', 'index']);
        $this->add('teams_create', '/teams/create', ['App\Controller\TeamController', 'create']);
        $this->add('teams_store', '/teams/store', ['App\Controller\TeamController', 'store']);
        $this->add('teams_members_create', '/teams/{id}/members/create', ['App\Controller\TeamController', 'createMember']);
        $this->add('teams_members_store', '/teams/{id}/members/store', ['App\Controller\TeamController', 'storeMember']);
        $this->add('teams_show', '/teams/{id}', ['App\Controller\TeamController', 'show']);
        $this->add('teams_show_alt', '/teams/show/{id}', ['App\Controller\TeamController', 'show']);
        $this->add('teams_edit', '/teams/{id}/edit', ['App\Controller\TeamController', 'edit']);
        $this->add('teams_update', '/teams/{id}/update', ['App\Controller\TeamController', 'update']);
        
        $this->add('vehicles_list', '/vehicles', ['App\\Controller\\VehicleController', 'index']);
        $this->add('vehicles_create', '/vehicles/create', ['App\\Controller\\VehicleController', 'create']);
        $this->add('vehicles_store', '/vehicles/store', ['App\\Controller\\VehicleController', 'store']);
        $this->add('vehicles_delete', '/vehicles/delete/{id}', ['App\\Controller\\VehicleController', 'delete']);
        $this->add('vehicles_show', '/vehicles/{id}', ['App\\Controller\\VehicleController', 'show']);
        $this->add('vehicles_edit', '/vehicles/{id}/edit', ['App\\Controller\\VehicleController', 'edit']);
        $this->add('vehicles_update', '/vehicles/{id}/update', ['App\\Controller\\VehicleController', 'update']);
        $this->add('interventions_list', '/intervention/list', ['App\Controller\InterventionController', 'list']);
        $this->add('interventions_create', '/intervention/create', ['App\Controller\InterventionController', 'create']);
        $this->add('interventions_store', '/intervention/store', ['App\Controller\InterventionController', 'store'], 'POST');
        $this->add('interventions_get_all', '/intervention/get-all', ['App\Controller\InterventionController', 'getAll']);
        $this->add('interventions_update_status', '/intervention/update-status', ['App\\Controller\\InterventionController', 'updateStatus'], 'POST');
        $this->add('interventions_update_technicians', '/intervention/update-technicians', ['App\\Controller\\InterventionController', 'updateTechnicians'], 'POST');
        $this->add('interventions_update_vehicle', '/intervention/update-vehicle', ['App\\Controller\\InterventionController', 'updateVehicle'], 'POST');
        $this->add('interventions_update_title', '/intervention/update-title', ['App\\Controller\\InterventionController', 'updateTitle'], 'POST');
        $this->add('interventions_update_description', '/intervention/update-description', ['App\\Controller\\InterventionController', 'updateDescription'], 'POST');
        $this->add('interventions_delete', '/intervention/delete/{id}', ['App\Controller\InterventionController', 'delete'], 'POST');
        $this->add('interventions_show', '/intervention/{id}', ['App\Controller\InterventionController', 'show']);
        $this->add('interventions_show_alt', '/intervention/show/{id}', ['App\Controller\InterventionController', 'show']);
        
        // Routes pour le système de permissions
        $this->add('permissions_index', '/permissions', ['App\Controller\PermissionController', 'index']);
        $this->add('permissions_admin', '/permissions/admin', ['App\Controller\PermissionController', 'admin']);
        $this->add('permissions_roles', '/permissions/roles', ['App\Controller\PermissionController', 'roles']);
        $this->add('permissions_users', '/permissions/users', ['App\Controller\PermissionController', 'users']);
        $this->add('permissions_profile', '/permissions/profile', ['App\Controller\PermissionController', 'profile']);
        $this->add('permissions_assign_role', '/permissions/assign-role', ['App\Controller\PermissionController', 'assignRoleToUser']);
        $this->add('permissions_remove_role', '/permissions/remove-role', ['App\Controller\PermissionController', 'removeRoleFromUser']);
        $this->add('permissions_assign_permission', '/permissions/assign-permission', ['App\Controller\PermissionController', 'assignPermissionToRole']);
        $this->add('permissions_remove_permission', '/permissions/remove-permission', ['App\Controller\PermissionController', 'removePermissionFromRole']);
        
        // Routes pour les préférences de notification
        $this->add('notification_preferences', '/notifications/preferences', ['App\Controller\NotificationPreferencesController', 'index']);
        $this->add('notification_preferences_update', '/notifications/preferences/update', ['App\Controller\NotificationPreferencesController', 'update']);
        $this->add('notification_preferences_test_email', '/notifications/preferences/test-email', ['App\Controller\NotificationPreferencesController', 'testEmail']);
        $this->add('notification_preferences_test_sms', '/notifications/preferences/test-sms', ['App\Controller\NotificationPreferencesController', 'testSms']);
        $this->add('notification_history', '/notifications/history', ['App\Controller\NotificationPreferencesController', 'history']);
        $this->add('notification_delete_log', '/notifications/delete-log', ['App\Controller\NotificationPreferencesController', 'deleteLog'], 'POST');
        $this->add('permissions_create_role', '/permissions/create-role', ['App\Controller\PermissionController', 'createRole']);
        $this->add('permissions_delete_role', '/permissions/delete-role', ['App\Controller\PermissionController', 'deleteRole']);
        
        // Routes pour la nouvelle interface de gestion des permissions
        $this->add('permissions_management_roles_api', '/permissions/management/api/roles', ['App\Controller\PermissionsManagementController', 'getRoles']);
        $this->add('permissions_management_users_api', '/permissions/management/api/users', ['App\Controller\PermissionsManagementController', 'getUsers']);
        $this->add('permissions_management_permissions_api', '/permissions/management/api/permissions', ['App\Controller\PermissionsManagementController', 'getPermissions']);
        $this->add('permissions_management_create_role_api', '/permissions/management/api/roles/create', ['App\Controller\PermissionsManagementController', 'createRole']);
        $this->add('permissions_management_update_role_api', '/permissions/management/api/roles/update', ['App\Controller\PermissionsManagementController', 'updateRole']);
        $this->add('permissions_management_delete_role_api', '/permissions/management/api/roles/delete', ['App\Controller\PermissionsManagementController', 'deleteRole']);
        
        // Routes d'administration
        $this->add('admin', '/admin', ['App\Controller\AdminController', 'index']);
        $this->add('admin_users', '/admin/users', ['App\Controller\AdminController', 'users']);
        $this->add('admin_roles', '/admin/roles', ['App\Controller\AdminController', 'roles']);
        $this->add('admin_permissions', '/admin/permissions', ['App\Controller\AdminController', 'permissions']);
        $this->add('admin_assign_role', '/admin/assign-role', ['App\Controller\AdminController', 'assignRole']);
        $this->add('admin_remove_role', '/admin/remove-role', ['App\Controller\AdminController', 'removeRole']);
        $this->add('admin_create_role', '/admin/create-role', ['App\Controller\AdminController', 'createRole']);
        $this->add('admin_update_role', '/admin/update-role', ['App\Controller\AdminController', 'updateRole']);
        $this->add('admin_delete_role', '/admin/delete-role', ['App\Controller\AdminController', 'deleteRole']);
    }

    public function generate(string $name, array $params = []): string
    {
        $basePath = '/exemple/backend-mvc/public';
        if (!isset($this->namedRoutes[$name])) {
            if (strpos($name, '.') !== false) {
                 return $basePath . '/assets/' . $name;
            }
            return $basePath . '/' . $name;
        }
        $path = $this->namedRoutes[$name];
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', $value, $path);
        }
        return $basePath . $path;
    }
    
    public function handleRequest()
    {
        try {
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            $uri = parse_url($uri, PHP_URL_PATH) ?? '/';
            
            // Détection automatique de l'environnement
            $basePath = '';
            
            // Check if we're in a subdirectory environment
            if (strpos($uri, '/exemple/backend-mvc/public/') === 0) {
                $basePath = '/exemple/backend-mvc/public';
            } elseif (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/exemple/backend-mvc/public/') !== false) {
                // Environnement Apache/serveur web traditionnel
                $basePath = '/exemple/backend-mvc/public';
            }
            // Pour le serveur PHP intégré, pas de basePath à retirer
            
            if ($basePath && strpos($uri, $basePath) === 0) {
                $uri = substr($uri, strlen($basePath));
            }
            $uri = trim((string)$uri, '/');
            $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            
            foreach ($this->routes as $routeKey => $action) {
                // Extraire la méthode et le chemin de la clé de route
                if (strpos($routeKey, ':') !== false) {
                    [$routeMethod, $path] = explode(':', $routeKey, 2);
                } else {
                    $routeMethod = 'GET';
                    $path = $routeKey;
                }
                
                // Vérifier si la méthode HTTP correspond
                if ($routeMethod !== $requestMethod) {
                    continue;
                }
                
                $path = trim($path, '/');
                $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
                if (preg_match("#^$pattern$#", $uri, $matches)) {
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    [$controllerClass, $method] = $action;
                    
                    $controller = $this->container->get($controllerClass);
                    
                    if (method_exists($controller, $method)) {
                        $result = call_user_func_array([$controller, $method], array_values($params));
                        if ($result !== null) {
                            echo $result;
                        }
                        return;
                    }
                }
            }
            echo "<pre style='color:red'>";
            echo "Route '$uri' non trouvée\n";
            echo "Routes disponibles :\n";
            print_r(array_keys($this->routes));
            echo "</pre>";
        } catch (\Throwable $e) {
            echo "<pre style='color:red'>";
            echo "Erreur dans Router::handleRequest : " . $e->getMessage() . "\n";
            echo $e->getTraceAsString();
            echo "</pre>";
        }
    }
}
