<?php
/**
 * Diagnostic complet de l'erreur 2FA "Unexpected token '<'"
 */

require_once __DIR__ . '/vendor/autoload.php';

header('Content-Type: text/plain; charset=utf-8');

echo "🔍 DIAGNOSTIC COMPLET ERREUR 2FA\n";
echo "=================================\n\n";

try {
    echo "1️⃣ Vérification de l'état de la session actuelle:\n";
    
    // Démarrer la session
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    echo "   Session ID: " . session_id() . "\n";
    echo "   Session status: " . (session_status() == PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE') . "\n";
    echo "   Contenu de la session:\n";
    if (!empty($_SESSION)) {
        foreach ($_SESSION as $key => $value) {
            if (is_array($value)) {
                echo "     $key: " . json_encode($value) . "\n";
            } else {
                echo "     $key: $value\n";
            }
        }
    } else {
        echo "     ❌ Session vide\n";
    }
    
    echo "\n2️⃣ Test de SessionManager:\n";
    
    $isAuthenticated = \App\Service\SessionManager::isAuthenticated();
    echo "   SessionManager::isAuthenticated(): " . ($isAuthenticated ? 'TRUE' : 'FALSE') . "\n";
    
    if ($isAuthenticated) {
        $user = \App\Service\SessionManager::getUser();
        echo "   Utilisateur en session: " . json_encode($user) . "\n";
    } else {
        echo "   ❌ Aucun utilisateur authentifié\n";
    }
    
    echo "\n3️⃣ Simulation d'une connexion technicien:\n";
    
    $pdo = \App\Service\Database::connect();
    $stmt = $pdo->query("SELECT id, email, name, role FROM users WHERE role = 'technician' LIMIT 1");
    $technician = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($technician) {
        echo "   Technicien trouvé: {$technician['email']} (ID: {$technician['id']})\n";
        
        // Créer une session pour le technicien
        $_SESSION['user'] = [
            'id' => $technician['id'],
            'email' => $technician['email'],
            'name' => $technician['name'],
            'role' => $technician['role']
        ];
        $_SESSION['authenticated'] = true;
        
        echo "   ✅ Session technicien simulée\n";
        
        // Vérifier à nouveau l'authentification
        $isAuthenticated = \App\Service\SessionManager::isAuthenticated();
        echo "   SessionManager::isAuthenticated() après simulation: " . ($isAuthenticated ? 'TRUE' : 'FALSE') . "\n";
        
        if ($isAuthenticated) {
            $user = \App\Service\SessionManager::getUser();
            echo "   Utilisateur après simulation: " . json_encode($user) . "\n";
        }
    } else {
        echo "   ❌ Aucun technicien trouvé\n";
    }
    
    echo "\n4️⃣ Test direct de l'endpoint 2FA:\n";
    
    if ($isAuthenticated) {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['action' => 'status'];
        
        // Capturer toute sortie avant l'appel
        ob_start();
        
        try {
            $securityController = new \App\Controller\SecurityController();
            $securityController->handle2FA();
        } catch (Exception $e) {
            echo "EXCEPTION: " . $e->getMessage() . "\n";
            echo "STACK TRACE: " . $e->getTraceAsString() . "\n";
        }
        
        $response = ob_get_clean();
        
        echo "   Réponse brute de l'endpoint:\n";
        echo "   Longueur: " . strlen($response) . " caractères\n";
        echo "   Contenu: '$response'\n";
        
        // Analyser la réponse
        if (empty($response)) {
            echo "   ❌ Réponse vide\n";
        } elseif (strpos($response, '<') === 0) {
            echo "   ❌ La réponse commence par '<' (HTML)\n";
            echo "   Extrait HTML: " . substr($response, 0, 200) . "...\n";
        } elseif (strpos($response, '{') === 0) {
            echo "   ✅ La réponse commence par '{' (JSON)\n";
            $jsonData = json_decode($response, true);
            if ($jsonData) {
                echo "   ✅ JSON valide: " . json_encode($jsonData, JSON_PRETTY_PRINT) . "\n";
            } else {
                echo "   ❌ JSON invalide\n";
            }
        } else {
            echo "   ⚠️ Format de réponse inconnu\n";
            echo "   Premier caractère: '" . substr($response, 0, 1) . "'\n";
            echo "   Premiers 100 caractères: " . substr($response, 0, 100) . "\n";
        }
    } else {
        echo "   ❌ Impossible de tester l'endpoint sans authentification\n";
    }
    
    echo "\n5️⃣ Test de l'endpoint via HTTP (avec cookies):\n";
    
    // Créer un cookie de session pour le test HTTP
    $sessionName = session_name();
    $sessionId = session_id();
    
    echo "   Session name: $sessionName\n";
    echo "   Session ID: $sessionId\n";
    
    $url = 'http://localhost:8888/settings/security/2fa';
    $postData = ['action' => 'status'];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, "$sessionName=$sessionId");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Ne pas suivre les redirections
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "   Code HTTP: $httpCode\n";
    echo "   Content-Type: $contentType\n";
    
    if ($error) {
        echo "   ❌ Erreur cURL: $error\n";
    } else {
        echo "   Réponse HTTP:\n";
        echo "   Longueur: " . strlen($response) . " caractères\n";
        echo "   Contenu: '$response'\n";
        
        // Analyser la réponse HTTP
        if (strpos($response, '<') === 0) {
            echo "   ❌ La réponse HTTP commence par '<' (HTML)\n";
            echo "   Cela explique l'erreur 'Unexpected token '<''\n";
            
            // Extraire le titre de la page HTML si possible
            if (preg_match('/<title>(.*?)<\/title>/i', $response, $matches)) {
                echo "   Titre de la page: " . $matches[1] . "\n";
            }
            
            // Vérifier si c'est une page d'erreur
            if (strpos($response, 'error') !== false || strpos($response, 'Error') !== false) {
                echo "   ⚠️ Semble être une page d'erreur\n";
            }
            
            // Vérifier si c'est une redirection vers login
            if (strpos($response, 'login') !== false || strpos($response, 'Login') !== false) {
                echo "   ⚠️ Semble être une redirection vers la page de login\n";
            }
        } elseif (strpos($response, '{') === 0) {
            echo "   ✅ La réponse HTTP commence par '{' (JSON)\n";
            $jsonData = json_decode($response, true);
            if ($jsonData) {
                echo "   ✅ JSON valide: " . json_encode($jsonData, JSON_PRETTY_PRINT) . "\n";
            } else {
                echo "   ❌ JSON invalide\n";
            }
        } else {
            echo "   ⚠️ Format de réponse HTTP inconnu\n";
            echo "   Premier caractère: '" . substr($response, 0, 1) . "'\n";
        }
    }
    
    echo "\n6️⃣ Vérification des routes:\n";
    
    // Vérifier que la route existe
    echo "   Vérification de la route /settings/security/2fa...\n";
    
    $routerFile = __DIR__ . '/src/Router/Router.php';
    if (file_exists($routerFile)) {
        $routerContent = file_get_contents($routerFile);
        if (strpos($routerContent, '/settings/security/2fa') !== false) {
            echo "   ✅ Route trouvée dans Router.php\n";
        } else {
            echo "   ❌ Route non trouvée dans Router.php\n";
        }
    } else {
        echo "   ❌ Fichier Router.php non trouvé\n";
    }
    
    echo "\n7️⃣ Recommandations:\n";
    
    if (strpos($response, '<') === 0) {
        echo "   🔧 PROBLÈME IDENTIFIÉ:\n";
        echo "   L'endpoint retourne du HTML au lieu de JSON\n";
        echo "   Cela cause l'erreur 'Unexpected token '<''\n";
        echo "   \n";
        echo "   🔧 CAUSES POSSIBLES:\n";
        echo "   1. Redirection vers la page de login (session expirée)\n";
        echo "   2. Erreur PHP qui affiche une page d'erreur HTML\n";
        echo "   3. Route non trouvée (404 avec page HTML)\n";
        echo "   4. Exception non gérée qui affiche du HTML\n";
        echo "   \n";
        echo "   🔧 SOLUTIONS:\n";
        echo "   1. Vérifier que l'utilisateur est bien connecté\n";
        echo "   2. Vérifier que la session est active\n";
        echo "   3. Vérifier les logs d'erreur PHP\n";
        echo "   4. Tester l'endpoint avec une session valide\n";
    } else {
        echo "   ✅ ENDPOINT FONCTIONNE CORRECTEMENT\n";
        echo "   Le problème vient probablement du côté client\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "RÉSUMÉ FINAL\n";
    echo str_repeat("=", 50) . "\n";
    
    if (strpos($response, '<') === 0) {
        echo "❌ PROBLÈME: Endpoint retourne HTML au lieu de JSON\n";
        echo "🔧 CAUSE: Session expirée ou erreur PHP\n";
        echo "🎯 SOLUTION: Vérifier la session et les logs d'erreur\n";
    } else {
        echo "✅ ENDPOINT FONCTIONNE\n";
        echo "🔧 PROBLÈME: Côté client JavaScript\n";
        echo "🎯 SOLUTION: Vérifier le code JavaScript\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
