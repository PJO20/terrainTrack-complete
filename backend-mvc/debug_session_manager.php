<?php
/**
 * Debug de SessionManager
 */

require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/SessionManager.php';

try {
    echo "🔍 DEBUG SESSIONMANAGER\n";
    echo "======================\n\n";
    
    // Simuler un utilisateur connecté avec SessionManager
    $user = [
        'id' => 7,
        'email' => 'momo@gmail.com',
        'name' => 'Admin User',
        'role' => 'admin',
        'session_timeout' => 30
    ];
    
    \App\Service\SessionManager::setUser($user);
    
    echo "1️⃣ Session simulée:\n";
    echo "   Session ID: " . session_id() . "\n";
    echo "   User ID: " . $_SESSION['user']['id'] . "\n";
    echo "   Email: " . $_SESSION['user']['email'] . "\n\n";
    
    echo "2️⃣ Test SessionManager::isAuthenticated():\n";
    $isAuthenticated = \App\Service\SessionManager::isAuthenticated();
    echo "   Résultat: " . ($isAuthenticated ? 'AUTHENTIFIÉ' : 'NON AUTHENTIFIÉ') . "\n\n";
    
    echo "3️⃣ Test SessionManager::getUser():\n";
    $user = \App\Service\SessionManager::getUser();
    if ($user) {
        echo "   Utilisateur récupéré: OUI\n";
        echo "   ID: " . ($user['id'] ?? 'N/A') . "\n";
        echo "   Email: " . ($user['email'] ?? 'N/A') . "\n";
    } else {
        echo "   Utilisateur récupéré: NON\n";
    }
    
    echo "\n4️⃣ Test SessionManager::updateUserSessionTimeout():\n";
    $result = \App\Service\SessionManager::updateUserSessionTimeout(90);
    echo "   Résultat: " . ($result ? 'SUCCÈS' : 'ÉCHEC') . "\n";
    
    if ($result) {
        echo "   ✅ La méthode fonctionne correctement\n";
    } else {
        echo "   ❌ La méthode échoue\n";
        
        // Debug plus détaillé
        echo "\n5️⃣ Debug détaillé:\n";
        
        // Vérifier isAuthenticated
        $auth = \App\Service\SessionManager::isAuthenticated();
        echo "   isAuthenticated(): " . ($auth ? 'true' : 'false') . "\n";
        
        // Vérifier getUser
        $user = \App\Service\SessionManager::getUser();
        echo "   getUser(): " . ($user ? 'OK' : 'NULL') . "\n";
        
        if ($user) {
            echo "   User ID: " . ($user['id'] ?? 'N/A') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
