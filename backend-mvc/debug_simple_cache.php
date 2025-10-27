<?php
/**
 * Debug simple du problème de cache
 */

require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/Database.php';
require_once __DIR__ . '/src/Service/SessionManager.php';

header('Content-Type: text/plain; charset=utf-8');

echo "🔍 DEBUG SIMPLE CACHE NAVIGATEUR\n";
echo "================================\n\n";

try {
    echo "1️⃣ Vérification de l'état actuel:\n";
    
    // Démarrer la session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    echo "   Session ID: " . session_id() . "\n";
    echo "   Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE') . "\n";
    
    // Vérifier l'authentification
    $isAuthenticated = \App\Service\SessionManager::isAuthenticated();
    echo "   Authentifié: " . ($isAuthenticated ? 'OUI' : 'NON') . "\n";
    
    if ($isAuthenticated) {
        $currentUser = \App\Service\SessionManager::getCurrentUser();
        if ($currentUser) {
            echo "   Utilisateur actuel: " . $currentUser['email'] . " (ID: " . $currentUser['id'] . ")\n";
        }
    }
    
    echo "\n2️⃣ Test de création de session momo:\n";
    
    // Forcer la création d'une session pour momo
    $_SESSION['user'] = [
        'id' => 7,
        'email' => 'momo@gmail.com',
        'name' => 'PJ',
        'role' => 'admin'
    ];
    $_SESSION['authenticated'] = true;
    
    echo "   ✅ Session momo créée\n";
    echo "   Session ID: " . session_id() . "\n";
    echo "   Utilisateur en session: " . $_SESSION['user']['email'] . "\n";
    
    echo "\n3️⃣ Test de l'authentification avec session momo:\n";
    
    $isAuthenticatedNow = \App\Service\SessionManager::isAuthenticated();
    echo "   Authentifié après création session: " . ($isAuthenticatedNow ? 'OUI' : 'NON') . "\n";
    
    if ($isAuthenticatedNow) {
        $currentUserNow = \App\Service\SessionManager::getCurrentUser();
        if ($currentUserNow) {
            echo "   Utilisateur actuel: " . $currentUserNow['email'] . "\n";
        }
    }
    
    echo "\n4️⃣ Test de récupération des données avec session momo:\n";
    
    $pdo = \App\Service\Database::connect();
    $stmt = $pdo->prepare("SELECT id, email, name, phone, role, department, location, timezone, language, avatar FROM users WHERE id = ?");
    $stmt->execute([7]); // ID de momo
    $momoData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($momoData) {
        echo "   ✅ Données momo récupérées:\n";
        echo "     - Email: " . $momoData['email'] . "\n";
        echo "     - Nom: " . ($momoData['name'] ?? 'NULL') . "\n";
        echo "     - Téléphone: " . ($momoData['phone'] ?? 'NULL') . "\n";
        echo "     - Rôle: " . ($momoData['role'] ?? 'NULL') . "\n";
        echo "     - Département: " . ($momoData['department'] ?? 'NULL') . "\n";
        echo "     - Localisation: " . ($momoData['location'] ?? 'NULL') . "\n";
        echo "     - Fuseau horaire: " . ($momoData['timezone'] ?? 'NULL') . "\n";
        echo "     - Langue: " . ($momoData['language'] ?? 'NULL') . "\n";
    } else {
        echo "   ❌ Impossible de récupérer les données momo\n";
    }
    
    echo "\n5️⃣ Test de l'endpoint settings avec session momo:\n";
    
    // Simuler l'appel à SettingsController
    echo "   Simulation de l'appel SettingsController...\n";
    
    // Vérifier si l'utilisateur est authentifié
    if (!\App\Service\SessionManager::isAuthenticated()) {
        echo "   ❌ Utilisateur non authentifié\n";
    } else {
        $user = \App\Service\SessionManager::getCurrentUser();
        if (!$user) {
            echo "   ❌ Impossible de récupérer l'utilisateur\n";
        } else {
            echo "   ✅ Utilisateur récupéré: " . $user['email'] . "\n";
            
            // Récupérer les données utilisateur
            $stmt = $pdo->prepare("SELECT id, email, name, phone, role, department, location, timezone, language, avatar FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userData) {
                echo "   ✅ Données utilisateur récupérées:\n";
                echo "     - Email: " . $userData['email'] . "\n";
                echo "     - Nom: " . ($userData['name'] ?? 'NULL') . "\n";
                echo "     - Téléphone: " . ($userData['phone'] ?? 'NULL') . "\n";
                echo "     - Rôle: " . ($userData['role'] ?? 'NULL') . "\n";
                echo "     - Département: " . ($userData['department'] ?? 'NULL') . "\n";
                echo "     - Localisation: " . ($userData['location'] ?? 'NULL') . "\n";
                echo "     - Fuseau horaire: " . ($userData['timezone'] ?? 'NULL') . "\n";
                echo "     - Langue: " . ($userData['language'] ?? 'NULL') . "\n";
                
                // Vérifier si c'est bien momo
                if ($userData['email'] === 'momo@gmail.com') {
                    echo "   ✅ C'est bien momo@gmail.com\n";
                } else {
                    echo "   ❌ PROBLÈME: Ce n'est pas momo@gmail.com mais " . $userData['email'] . "\n";
                }
            } else {
                echo "   ❌ Impossible de récupérer les données utilisateur\n";
            }
        }
    }
    
    echo "\n6️⃣ Vérification des logs:\n";
    
    $logFile = __DIR__ . '/logs/app.log';
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        $logLines = explode("\n", $logContent);
        $recentLines = array_slice($logLines, -10); // 10 dernières lignes
        
        echo "   Dernières lignes du log:\n";
        foreach ($recentLines as $line) {
            if (!empty(trim($line))) {
                echo "     " . $line . "\n";
            }
        }
    } else {
        echo "   ❌ Fichier de log non trouvé: $logFile\n";
    }
    
    echo "\n7️⃣ Recommandations finales:\n";
    
    if (!$isAuthenticatedNow) {
        echo "   ❌ PROBLÈME: SessionManager ne reconnaît pas la session\n";
        echo "   🔧 SOLUTION: Vérifier la configuration de SessionManager\n";
    } else if (isset($currentUserNow) && $currentUserNow['email'] === 'momo@gmail.com') {
        echo "   ✅ Session momo fonctionne\n";
        echo "   🔧 PROBLÈME: Cache navigateur persistant\n";
        echo "   🔧 SOLUTION: Utiliser une fenêtre de navigation privée\n";
    } else {
        echo "   ❌ PROBLÈME: Session créée mais pas reconnue\n";
        echo "   🔧 SOLUTION: Vérifier la logique de SessionManager\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "RÉSUMÉ FINAL\n";
    echo str_repeat("=", 50) . "\n";
    
    if ($isAuthenticatedNow && isset($currentUserNow) && $currentUserNow['email'] === 'momo@gmail.com') {
        echo "✅ SESSION MOMO FONCTIONNE\n";
        echo "🔧 PROBLÈME: Cache navigateur persistant\n";
        echo "🔧 SOLUTION: Utiliser une fenêtre de navigation privée\n";
    } else {
        echo "❌ PROBLÈME: SessionManager ne fonctionne pas correctement\n";
        echo "🔧 SOLUTION: Vérifier la configuration de SessionManager\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>

