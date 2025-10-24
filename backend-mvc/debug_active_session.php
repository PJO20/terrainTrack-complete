<?php
/**
 * Debug de la session active de l'utilisateur connecté
 */

// Démarrer la session avec le même nom que l'application
session_name('PHPSESSID');
session_start();

require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/SessionManager.php';

echo "🔍 DEBUG SESSION ACTIVE UTILISATEUR\n";
echo "===================================\n\n";

echo "1️⃣ Configuration Session:\n";
echo "   Session Name: " . session_name() . "\n";
echo "   Session ID: " . session_id() . "\n";
echo "   Session Status: " . session_status() . "\n";
echo "   Session Save Path: " . session_save_path() . "\n";
echo "   Cookie Params: " . json_encode(session_get_cookie_params()) . "\n\n";

echo "2️⃣ Variables de Session Brutes:\n";
if (empty($_SESSION)) {
    echo "   ❌ SESSION VIDE\n";
} else {
    echo "   ✅ Session contient " . count($_SESSION) . " variables:\n";
    foreach ($_SESSION as $key => $value) {
        if (is_array($value)) {
            echo "   - $key: [array avec " . count($value) . " éléments]\n";
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
    echo "   - Session Timeout: " . ($user['session_timeout'] ?? 'N/A') . "\n";
} else {
    echo "   getUser(): ❌ NULL\n";
}

echo "\n4️⃣ Test Mise à Jour Session Timeout:\n";
if ($isAuth && $user) {
    echo "   Tentative de mise à jour...\n";
    $result = \App\Service\SessionManager::updateUserSessionTimeout(45);
    echo "   Résultat: " . ($result ? '✅ SUCCÈS' : '❌ ÉCHEC') . "\n";
    
    if (!$result) {
        echo "   🔍 Debug détaillé de l'échec...\n";
        
        // Test direct de la base de données
        try {
            require_once __DIR__ . '/src/Service/Database.php';
            $pdo = \App\Service\Database::connect();
            
            $stmt = $pdo->prepare("SELECT id, email, session_timeout FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $dbUser = $stmt->fetch();
            
            if ($dbUser) {
                echo "   - Utilisateur en base: ✅ EXISTE\n";
                echo "   - ID: " . $dbUser['id'] . "\n";
                echo "   - Email: " . $dbUser['email'] . "\n";
                echo "   - Session Timeout actuel: " . $dbUser['session_timeout'] . "\n";
            } else {
                echo "   - Utilisateur en base: ❌ INTROUVABLE\n";
            }
        } catch (Exception $e) {
            echo "   - Erreur base de données: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "   ❌ Impossible - utilisateur non authentifié\n";
}

echo "\n5️⃣ Test Endpoint Direct:\n";
if ($isAuth) {
    echo "   Simulation requête POST...\n";
    
    // Sauvegarder les variables globales
    $originalPost = $_POST;
    $originalServer = $_SERVER;
    
    // Simuler la requête
    $_POST['session_timeout'] = '75';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    try {
        require_once __DIR__ . '/src/Controller/SecurityController.php';
        
        ob_start();
        $controller = new \App\Controller\SecurityController();
        $controller->updateSessionTimeout();
        $output = ob_get_clean();
        
        echo "   Réponse: $output\n";
        
        $response = json_decode($output, true);
        if ($response && isset($response['success'])) {
            echo "   Status: " . ($response['success'] ? '✅ SUCCÈS' : '❌ ÉCHEC') . "\n";
            if (isset($response['error'])) {
                echo "   Erreur: " . $response['error'] . "\n";
            }
        }
        
    } catch (Exception $e) {
        echo "   ❌ Exception: " . $e->getMessage() . "\n";
    }
    
    // Restaurer les variables globales
    $_POST = $originalPost;
    $_SERVER = $originalServer;
    
} else {
    echo "   ❌ Impossible - utilisateur non authentifié\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "RÉSUMÉ DU DIAGNOSTIC\n";
echo str_repeat("=", 50) . "\n";

if (empty($_SESSION)) {
    echo "❌ PROBLÈME: Session complètement vide\n";
    echo "🔧 SOLUTION: Problème de configuration session\n";
} elseif (!$isAuth) {
    echo "❌ PROBLÈME: Session existe mais utilisateur non authentifié\n";
    echo "🔧 SOLUTION: Problème dans SessionManager::isAuthenticated()\n";
} elseif (!$user) {
    echo "❌ PROBLÈME: Authentifié mais pas d'utilisateur\n";
    echo "🔧 SOLUTION: Problème dans SessionManager::getUser()\n";
} else {
    echo "✅ Session semble correcte - problème ailleurs\n";
}
?>
