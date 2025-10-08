<?php
/**
 * Debug direct de la 2FA
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔍 Debug 2FA\n";
echo "============\n\n";

try {
    require_once 'vendor/autoload.php';
    
    // Simuler une session
    session_start();
    $_SESSION['authenticated'] = true;
    $_SESSION['user'] = [
        'id' => 7,
        'email' => 'momo@gmail.com',
        'name' => 'Momo',
        'role' => 'admin'
    ];
    
    echo "1. Session créée\n";
    
    // Test direct du service
    echo "2. Test TwoFactorService...\n";
    $twoFactorService = new \App\Service\TwoFactorService();
    echo "   ✅ Service créé\n";
    
    $userId = 7;
    $enabled = $twoFactorService->isTwoFactorEnabled($userId);
    $required = $twoFactorService->isTwoFactorRequired($userId);
    
    echo "   - 2FA activé: " . ($enabled ? 'Oui' : 'Non') . "\n";
    echo "   - 2FA obligatoire: " . ($required ? 'Oui' : 'Non') . "\n";
    
    // Test du contrôleur
    echo "3. Test contrôleur...\n";
    $controller = new \App\Controller\TwoFactorController();
    echo "   ✅ Contrôleur créé\n";
    
    // Test TwigService
    echo "4. Test TwigService...\n";
    $sessionManager = new \App\Service\SessionManager();
    $permissionService = new \App\Service\PermissionService();
    $twigService = new \App\Service\TwigService($sessionManager, $permissionService);
    echo "   ✅ TwigService créé\n";
    
    $controller->setTwig($twigService);
    $controller->setSessionManager($sessionManager);
    $controller->setTwoFactorService($twoFactorService);
    
    echo "5. Test rendu template...\n";
    ob_start();
    $controller->index();
    $output = ob_get_clean();
    
    if (empty($output)) {
        echo "   ❌ Aucune sortie\n";
    } else {
        echo "   ✅ Sortie générée (" . strlen($output) . " caractères)\n";
        echo "   📄 Aperçu: " . substr($output, 0, 200) . "...\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
