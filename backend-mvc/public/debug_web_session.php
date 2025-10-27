<?php
/**
 * Debug de la session web réelle
 * À accéder via http://localhost:8888/debug_web_session.php
 */

// Démarrer la session comme dans l'application
session_start();

require_once __DIR__ . '/../src/Service/EnvService.php';
require_once __DIR__ . '/../src/Service/SessionManager.php';

header('Content-Type: text/plain; charset=utf-8');

echo "🔍 DEBUG SESSION WEB RÉELLE\n";
echo "============================\n\n";

echo "1️⃣ Configuration Session:\n";
echo "   Session Name: " . session_name() . "\n";
echo "   Session ID: " . session_id() . "\n";
echo "   Session Status: " . session_status() . "\n\n";

echo "2️⃣ Variables de Session:\n";
if (empty($_SESSION)) {
    echo "   ❌ SESSION VIDE\n";
} else {
    echo "   ✅ Session contient " . count($_SESSION) . " variables:\n";
    foreach ($_SESSION as $key => $value) {
        if (is_array($value)) {
            echo "   - $key: [array]\n";
            foreach ($value as $subKey => $subValue) {
                if (is_string($subValue) || is_numeric($subValue)) {
                    echo "     * $subKey: $subValue\n";
                } else {
                    echo "     * $subKey: [" . gettype($subValue) . "]\n";
                }
            }
        } else {
            echo "   - $key: $value\n";
        }
    }
}

echo "\n3️⃣ Test SessionManager:\n";
$isAuth = \App\Service\SessionManager::isAuthenticated();
echo "   isAuthenticated(): " . ($isAuth ? '✅ OUI' : '❌ NON') . "\n";

$user = \App\Service\SessionManager::getUser();
if ($user) {
    echo "   getUser(): ✅ OUI\n";
    echo "   - ID: " . ($user['id'] ?? 'N/A') . "\n";
    echo "   - Email: " . ($user['email'] ?? 'N/A') . "\n";
    echo "   - Role: " . ($user['role'] ?? 'N/A') . "\n";
} else {
    echo "   getUser(): ❌ NULL\n";
}

echo "\n4️⃣ Test Endpoint Security:\n";
if ($isAuth && $user) {
    echo "   ✅ Utilisateur authentifié - Test possible\n";
    
    // Test de la méthode updateUserSessionTimeout
    echo "   Test updateUserSessionTimeout(60)...\n";
    $result = \App\Service\SessionManager::updateUserSessionTimeout(60);
    echo "   Résultat: " . ($result ? '✅ SUCCÈS' : '❌ ÉCHEC') . "\n";
    
    if ($result) {
        echo "   ✅ Le bouton Sauvegarder devrait fonctionner\n";
    } else {
        echo "   ❌ Le bouton Sauvegarder ne fonctionnera pas\n";
        
        // Debug plus poussé
        echo "\n   🔍 Debug de l'échec:\n";
        try {
            require_once __DIR__ . '/../src/Service/Database.php';
            $pdo = \App\Service\Database::connect();
            
            $stmt = $pdo->prepare("SELECT id, email, session_timeout FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $dbUser = $stmt->fetch();
            
            if ($dbUser) {
                echo "   - Utilisateur en base: ✅ EXISTE (ID: {$dbUser['id']}, Email: {$dbUser['email']})\n";
                echo "   - Session timeout actuel: {$dbUser['session_timeout']} minutes\n";
            } else {
                echo "   - Utilisateur en base: ❌ INTROUVABLE\n";
            }
        } catch (Exception $e) {
            echo "   - Erreur DB: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "   ❌ Utilisateur non authentifié - Bouton ne fonctionnera pas\n";
    
    if (empty($_SESSION)) {
        echo "   Cause: Session complètement vide\n";
    } elseif (!isset($_SESSION['authenticated'])) {
        echo "   Cause: Pas de flag 'authenticated' dans la session\n";
    } elseif (!$_SESSION['authenticated']) {
        echo "   Cause: Flag 'authenticated' = false\n";
    } elseif (!isset($_SESSION['user'])) {
        echo "   Cause: Pas d'utilisateur dans la session\n";
    } else {
        echo "   Cause: Autre problème dans SessionManager\n";
    }
}

echo "\n5️⃣ Test Direct Endpoint:\n";
if ($isAuth) {
    echo "   Test de l'endpoint /settings/security/update-session-timeout\n";
    echo "   URL à tester: http://localhost:8888/settings/security/update-session-timeout\n";
    echo "   Méthode: POST\n";
    echo "   Données: session_timeout=90\n";
} else {
    echo "   ❌ Impossible - utilisateur non authentifié\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "DIAGNOSTIC FINAL\n";
echo str_repeat("=", 50) . "\n";

if ($isAuth && $user) {
    echo "✅ SESSION CORRECTE - Le problème est ailleurs\n";
    echo "🔍 Vérifiez:\n";
    echo "   1. Le JavaScript du bouton\n";
    echo "   2. L'URL de l'endpoint\n";
    echo "   3. Les erreurs dans la console navigateur\n";
    echo "   4. Les logs du serveur\n";
} else {
    echo "❌ PROBLÈME DE SESSION\n";
    echo "🔧 SOLUTION: Reconnectez-vous sur l'application\n";
}
?>

