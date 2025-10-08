<?php
require_once __DIR__ . '/../vendor/autoload.php';

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
use App\Controller\AdminController;
use App\Controller\PermissionsManagementController;
use App\Controller\NotificationPreferencesController;
use App\Controller\TwoFactorController;
use App\Controller\SimpleTwoFactorController;
use App\Service\TwoFactorService;
use App\Repository\InterventionRepository;
use App\Repository\TeamRepository;
use App\Repository\VehicleRepository;
use App\Repository\TechnicianRepository;
use App\Repository\UserRepository;
use App\Repository\UserSettingsRepository;
use App\Repository\NotificationSettingsRepository;
use App\Repository\AppearanceSettingsRepository;
use App\Repository\NotificationRepository;
use App\Repository\NotificationLogsRepository;
use App\Repository\NotificationPreferencesRepository;
use App\Repository\MaintenanceSchedulesRepository;
use App\Repository\RoleRepository;
use App\Repository\PermissionRepository;
use App\Service\TwigService;
use App\Service\NotificationService;
use App\Service\EmailNotificationService;
use App\Service\SmsNotificationService;
use App\Service\ReminderService;
use App\Service\SessionManager;
use App\Service\PermissionService;
use App\Middleware\AuthorizationMiddleware;
use App\Router\Router;
use App\Container\Container;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

$services = [
    // Base de données
    PDO::class => function(Container $container) {
        require_once __DIR__ . '/database.php';
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    },

    // Services de base
    SessionManager::class => function(Container $container) {
        return new SessionManager();
    },

    // Repositories pour les permissions
    RoleRepository::class => function(Container $container) {
        return new RoleRepository($container->get(PDO::class));
    },

    PermissionRepository::class => function(Container $container) {
        return new PermissionRepository($container->get(PDO::class));
    },

    // Service de permissions
    PermissionService::class => function(Container $container) {
        return new PermissionService(
            $container->get(UserRepository::class),
            $container->get(RoleRepository::class),
            $container->get(PermissionRepository::class)
        );
    },

    // Middleware d'autorisation
    AuthorizationMiddleware::class => function(Container $container) {
        return new AuthorizationMiddleware(
            $container->get(PermissionService::class),
            $container->get(SessionManager::class)
        );
    },

    // Services
    TwigService::class => function(Container $container) {
        return new TwigService(
            $container->get(SessionManager::class),
            $container->get(PermissionService::class)
        );
    },

    Router::class => function(Container $container) {
        return new Router($container);
    },

    // Repositories
    InterventionRepository::class => function(Container $container) {
        return new InterventionRepository($container->get(PDO::class));
    },

    VehicleRepository::class => function(Container $container) {
        return new VehicleRepository($container->get(PDO::class));
    },

    TechnicianRepository::class => function(Container $container) {
        return new TechnicianRepository($container->get(PDO::class));
    },

    TeamRepository::class => function(Container $container) {
        return new TeamRepository($container->get(PDO::class));
    },

    UserRepository::class => function(Container $container) {
        return new UserRepository($container->get(PDO::class));
    },

    UserSettingsRepository::class => function(Container $container) {
        return new UserSettingsRepository($container->get(PDO::class));
    },

    NotificationSettingsRepository::class => function(Container $container) {
        return new NotificationSettingsRepository($container->get(PDO::class));
    },

    AppearanceSettingsRepository::class => function(Container $container) {
        return new AppearanceSettingsRepository($container->get(PDO::class));
    },

    NotificationRepository::class => function(Container $container) {
        return new NotificationRepository($container->get(PDO::class));
    },

    NotificationLogsRepository::class => function(Container $container) {
        return new NotificationLogsRepository($container->get(PDO::class));
    },

    NotificationPreferencesRepository::class => function(Container $container) {
        return new NotificationPreferencesRepository($container->get(PDO::class));
    },

    MaintenanceSchedulesRepository::class => function(Container $container) {
        return new MaintenanceSchedulesRepository($container->get(PDO::class));
    },

    // Controllers
    InterventionController::class => function(Container $container) {
        return new InterventionController(
            $container->get(TwigService::class),
            $container->get(InterventionRepository::class),
            $container->get(VehicleRepository::class),
            $container->get(TechnicianRepository::class),
            $container->get(NotificationService::class),
            $container->get(EmailNotificationService::class),
            $container->get(SmsNotificationService::class),
            $container->get(AuthorizationMiddleware::class)
        );
    },

    HomeController::class => function(Container $container) {
        return new HomeController(
            $container->get(TwigService::class)
        );
    },

    AuthController::class => function(Container $container) {
        return new AuthController(
            $container->get(TwigService::class),
            $container->get(TwoFactorService::class)
        );
    },

    DashboardController::class => function(Container $container) {
        return new DashboardController(
            $container->get(TwigService::class),
            $container->get(VehicleRepository::class),
            $container->get(InterventionRepository::class),
            $container->get(TeamRepository::class)
        );
    },

    MapViewController::class => function(Container $container) {
        return new MapViewController(
            $container->get(TwigService::class),
            $container->get(VehicleRepository::class),
            $container->get(InterventionRepository::class),
            $container->get(TeamRepository::class)
        );
    },

    VehicleController::class => function(Container $container) {
        return new VehicleController(
            $container->get(TwigService::class),
            $container->get(VehicleRepository::class),
            $container->get(InterventionRepository::class),
            $container->get(EmailNotificationService::class),
            $container->get(SmsNotificationService::class),
            $container->get(MaintenanceSchedulesRepository::class)
        );
    },

    TeamController::class => function(Container $container) {
        return new TeamController(
            $container->get(TwigService::class),
            $container->get(TeamRepository::class),
            $container->get(VehicleRepository::class),
            $container->get(UserRepository::class),
            $container->get(InterventionRepository::class)
        );
    },

    SettingsController::class => function(Container $container) {
        return new SettingsController(
            $container->get(TwigService::class),
            $container->get(UserRepository::class),
            $container->get(UserSettingsRepository::class),
            $container->get(NotificationSettingsRepository::class),
            $container->get(AppearanceSettingsRepository::class)
        );
    },

    TwoFactorService::class => function(Container $container) {
        try {
            return new TwoFactorService(
                $container->get(EmailNotificationService::class)
            );
        } catch (Exception $e) {
            // En cas d'erreur, créer sans EmailService
            return new TwoFactorService(null);
        }
    },

    TwoFactorController::class => function(Container $container) {
        return new TwoFactorController();
    },

    SimpleTwoFactorController::class => function(Container $container) {
        return new SimpleTwoFactorController();
    },

    ReportsController::class => function(Container $container) {
        return new ReportsController(
            $container->get(TwigService::class)
        );
    },

    NotificationController::class => function(Container $container) {
        return new NotificationController(
            $container->get(TwigService::class),
            $container->get(NotificationService::class)
        );
    },

    HelpController::class => function(Container $container) {
        return new HelpController(
            $container->get(TwigService::class)
        );
    },

    ProfileController::class => function(Container $container) {
        return new ProfileController(
            $container->get(TwigService::class),
            $container->get(UserRepository::class)
        );
    },

    PermissionController::class => function(Container $container) {
        return new PermissionController(
            $container->get(TwigService::class),
            $container->get(PermissionService::class),
            $container->get(SessionManager::class),
            $container->get(AuthorizationMiddleware::class)
        );
    },

    AdminController::class => function(Container $container) {
        return new AdminController(
            $container->get(TwigService::class),
            $container->get(PermissionService::class),
            $container->get(AuthorizationMiddleware::class),
            $container->get(SessionManager::class)
        );
    },

    PermissionsManagementController::class => function(Container $container) {
        return new PermissionsManagementController(
            $container->get(TwigService::class),
            $container->get(PermissionService::class),
            $container->get(SessionManager::class),
            $container->get(AuthorizationMiddleware::class)
        );
    },

    NotificationPreferencesController::class => function(Container $container) {
        return new NotificationPreferencesController(
            $container->get(TwigService::class),
            $container->get(SessionManager::class),
            $container->get(NotificationPreferencesRepository::class),
            $container->get(UserRepository::class),
            $container->get(EmailNotificationService::class),
            $container->get(SmsNotificationService::class)
        );
    },

    NotificationService::class => function(Container $container) {
        return new NotificationService(
            $container->get(NotificationRepository::class)
        );
    },

    EmailNotificationService::class => function(Container $container) {
        return new EmailNotificationService(
            $container->get(UserRepository::class),
            $container->get(NotificationLogsRepository::class)
        );
    },

    SmsNotificationService::class => function(Container $container) {
        return new SmsNotificationService(
            $container->get(UserRepository::class),
            $container->get(NotificationLogsRepository::class)
        );
    },

    ReminderService::class => function(Container $container) {
        return new ReminderService(
            $container->get(MaintenanceSchedulesRepository::class),
            $container->get(NotificationPreferencesRepository::class),
            $container->get(UserRepository::class),
            $container->get(EmailNotificationService::class),
            $container->get(SmsNotificationService::class)
        );
    }
];

// On retourne le tableau de services
return $services;
