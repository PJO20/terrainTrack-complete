<?php
    // Charger EnvService si disponible
    if (!class_exists('App\Service\EnvService')) {
        require_once __DIR__ . '/../src/Service/EnvService.php';
    }


use App\Container\Container;
use App\Router\Router;
use App\Service\EnvService;
use App\Service\SecurityHeadersService;

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    // Charger les variables d'environnement en premier
    EnvService::load();
    
    // Appliquer les headers de sécurité dès le début
    SecurityHeadersService::applySecurityHeaders();
    
    // Configuration des erreurs basée sur l'environnement
    $isDebug = \App\Service\EnvService::getBool('APP_DEBUG', false);
    $isProduction = \App\Service\EnvService::get('APP_ENV', 'development') === 'production';
    
    if ($isProduction) {
        // Production : Aucune erreur affichée, logs uniquement
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        error_reporting(E_ERROR | E_WARNING | E_PARSE);
    } elseif ($isDebug) {
        // Développement avec debug : Toutes les erreurs
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        error_log('REQUEST_URI: ' . $_SERVER['REQUEST_URI']);
    } else {
        // Développement sans debug : Erreurs importantes seulement
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        error_reporting(E_ERROR | E_WARNING | E_PARSE);
    }
    
    // Configuration des logs
    ini_set('log_errors', 1);
    $logPath = __DIR__ . '/../logs/app.log';
    $logDir = dirname($logPath);
    
    // Créer le dossier de logs s'il n'existe pas
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    ini_set('error_log', $logPath);
    
    // Démarrage de l'application
    $services = require __DIR__ . '/../config/services.php';
    $container = new Container($services);
    $router = $container->get(Router::class);
    
    $router->handleRequest();
} catch (\Throwable $e) {
    // Log l'erreur
    error_log("Fatal error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    $isDebug = \App\Service\EnvService::getBool('APP_DEBUG', false);
    
    if ($isDebug) {
        echo "<h1>Erreur Fatale Inattendue</h1>";
        echo "<pre style='color:red; font-weight:bold; background-color: #fff; padding: 1rem; border: 1px solid red;'>";
        echo "Un problème critique est survenu :<br><br>";
        echo "<b>Message:</b> " . htmlspecialchars($e->getMessage()) . "<br>";
        echo "<b>Fichier:</b> " . htmlspecialchars($e->getFile()) . "<b> à la ligne </b>" . $e->getLine() . "<br><br>";
        echo "<b>Trace:</b><br>" . htmlspecialchars($e->getTraceAsString());
        echo "</pre>";
    } else {
        // Page d'erreur user-friendly en production
        http_response_code(500);
        echo "<!DOCTYPE html>";
        echo "<html><head><title>Erreur temporaire</title></head>";
        echo "<body><h1>Service temporairement indisponible</h1>";
        echo "<p>Une erreur technique est survenue. Veuillez réessayer dans quelques minutes.</p>";
        echo "</body></html>";
    }
}

