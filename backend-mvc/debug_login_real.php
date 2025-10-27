<?php
/**
 * Debug de la connexion réelle
 */

echo "🔍 DEBUG CONNEXION RÉELLE\n";
echo "=========================\n\n";

// Simuler exactement ce qui se passe dans le navigateur
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/login';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Test';

// Démarrer la session proprement
session_start();

// Générer un token CSRF valide
require_once 'vendor/autoload.php';

$csrf = new \App\Service\CsrfService();
$csrfToken = $csrf->generateToken('login');

echo "🔑 Token CSRF généré: " . substr($csrfToken, 0, 20) . "...\n";

// Simuler les données POST avec le bon token
$_POST = [
    'email' => 'momo@gmail.com',
    'password' => '123456789',
    'csrf_token' => $csrfToken
];

echo "📧 Email: " . $_POST['email'] . "\n";
echo "🔒 Password: " . str_repeat('*', strlen($_POST['password'])) . "\n\n";

try {
    // Charger le container
    $container = new \App\Container\Container(require 'config/services.php');
    
    // Créer le contrôleur
    $authController = $container->get(\App\Controller\AuthController::class);
    
    echo "🎯 Tentative de connexion...\n";
    
    // Capturer la sortie
    ob_start();
    $result = $authController->login();
    $output = ob_get_clean();
    
    // Vérifier si on a été redirigé
    $headers = headers_list();
    $redirected = false;
    foreach ($headers as $header) {
        if (strpos($header, 'Location:') === 0) {
            echo "✅ REDIRECTION DÉTECTÉE: " . $header . "\n";
            $redirected = true;
        }
    }
    
    if ($redirected) {
        echo "🎉 CONNEXION RÉUSSIE !\n";
        
        // Vérifier la session
        if (\App\Service\SessionManager::isAuthenticated()) {
            $user = \App\Service\SessionManager::getCurrentUser();
            echo "👤 Utilisateur connecté: " . $user['email'] . "\n";
            echo "🛡️ Rôle: " . $user['role'] . "\n";
        }
    } else {
        echo "❌ PAS DE REDIRECTION\n";
        echo "📄 Sortie (100 premiers caractères):\n";
        echo substr($output, 0, 100) . "...\n";
        
        // Chercher des messages d'erreur
        if (strpos($output, 'error') !== false) {
            preg_match('/class="error[^"]*"[^>]*>([^<]+)/', $output, $matches);
            if (isset($matches[1])) {
                echo "⚠️ Erreur détectée: " . trim($matches[1]) . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "📍 Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🔧 INSTRUCTIONS POUR TESTER MANUELLEMENT:\n";
echo "1. Ouvrez http://localhost:8888/login\n";
echo "2. Utilisez ces identifiants:\n";
echo "   Email: momo@gmail.com\n";
echo "   Mot de passe: 123456789\n";
echo "3. Si ça ne marche pas, vérifiez la console du navigateur\n";
?>


