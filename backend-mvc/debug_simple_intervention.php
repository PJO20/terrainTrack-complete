<?php
/**
 * Debug simple du problème d'accès aux interventions
 */

require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/Database.php';
require_once __DIR__ . '/src/Service/SessionManager.php';

header('Content-Type: text/plain; charset=utf-8');

echo "🔍 DEBUG SIMPLE ACCÈS INTERVENTIONS\n";
echo "===================================\n\n";

try {
    echo "1️⃣ Vérification de l'état de la session:\n";
    
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
            echo "   Rôle: " . ($currentUser['role'] ?? 'NULL') . "\n";
        }
    }
    
    echo "\n2️⃣ Test de création de session admin:\n";
    
    // Forcer la création d'une session admin
    $_SESSION['user'] = [
        'id' => 7,
        'email' => 'momo@gmail.com',
        'name' => 'PJ',
        'role' => 'admin'
    ];
    $_SESSION['authenticated'] = true;
    
    echo "   ✅ Session admin créée\n";
    echo "   Session ID: " . session_id() . "\n";
    echo "   Utilisateur en session: " . $_SESSION['user']['email'] . "\n";
    echo "   Rôle: " . $_SESSION['user']['role'] . "\n";
    
    echo "\n3️⃣ Test de l'authentification avec session admin:\n";
    
    $isAuthenticatedNow = \App\Service\SessionManager::isAuthenticated();
    echo "   Authentifié après création session: " . ($isAuthenticatedNow ? 'OUI' : 'NON') . "\n";
    
    if ($isAuthenticatedNow) {
        $currentUserNow = \App\Service\SessionManager::getCurrentUser();
        if ($currentUserNow) {
            echo "   Utilisateur actuel: " . $currentUserNow['email'] . "\n";
            echo "   Rôle: " . $currentUserNow['role'] . "\n";
            
            if (in_array($currentUserNow['role'], ['admin', 'super_admin'])) {
                echo "   ✅ Rôle admin confirmé\n";
            } else {
                echo "   ❌ Rôle admin NON confirmé\n";
            }
        }
    }
    
    echo "\n4️⃣ Vérification des permissions en base:\n";
    
    $pdo = \App\Service\Database::connect();
    $stmt = $pdo->prepare("SELECT id, email, name, role FROM users WHERE id = ?");
    $stmt->execute([7]); // ID de momo
    $momoData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($momoData) {
        echo "   ✅ Données momo récupérées:\n";
        echo "     - ID: " . $momoData['id'] . "\n";
        echo "     - Email: " . $momoData['email'] . "\n";
        echo "     - Nom: " . ($momoData['name'] ?? 'NULL') . "\n";
        echo "     - Rôle: " . ($momoData['role'] ?? 'NULL') . "\n";
        
        if (in_array($momoData['role'], ['admin', 'super_admin'])) {
            echo "   ✅ Rôle admin confirmé en base\n";
        } else {
            echo "   ❌ Rôle admin NON confirmé en base\n";
        }
    } else {
        echo "   ❌ Impossible de récupérer les données momo\n";
    }
    
    echo "\n5️⃣ Test de l'endpoint intervention/create:\n";
    
    // Simuler l'appel à InterventionController
    echo "   Simulation de l'appel InterventionController...\n";
    
    // Vérifier si l'utilisateur est authentifié
    if (!\App\Service\SessionManager::isAuthenticated()) {
        echo "   ❌ Utilisateur non authentifié\n";
    } else {
        $user = \App\Service\SessionManager::getCurrentUser();
        if (!$user) {
            echo "   ❌ Impossible de récupérer l'utilisateur\n";
        } else {
            echo "   ✅ Utilisateur récupéré: " . $user['email'] . "\n";
            echo "   Rôle: " . $user['role'] . "\n";
            
            // Vérifier les permissions
            if (in_array($user['role'], ['admin', 'super_admin'])) {
                echo "   ✅ Utilisateur a les permissions admin\n";
                echo "   ✅ Accès aux interventions AUTORISÉ\n";
            } else {
                echo "   ❌ Utilisateur n'a PAS les permissions admin\n";
                echo "   ❌ Accès aux interventions REFUSÉ\n";
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
    
    echo "\n7️⃣ Recommandations:\n";
    
    if (!$isAuthenticatedNow) {
        echo "   ❌ PROBLÈME: SessionManager ne reconnaît pas la session\n";
        echo "   🔧 SOLUTION: Vérifier la configuration de SessionManager\n";
    } else if (isset($currentUserNow) && in_array($currentUserNow['role'], ['admin', 'super_admin'])) {
        echo "   ✅ Session admin fonctionne\n";
        echo "   🔧 PROBLÈME: InterventionController bloque l'accès\n";
        echo "   🔧 SOLUTION: Vérifier la logique d'autorisation dans InterventionController\n";
    } else {
        echo "   ❌ PROBLÈME: Session créée mais rôle non admin\n";
        echo "   🔧 SOLUTION: Vérifier la logique de SessionManager\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "RÉSUMÉ FINAL\n";
    echo str_repeat("=", 50) . "\n";
    
    if ($isAuthenticatedNow && isset($currentUserNow) && in_array($currentUserNow['role'], ['admin', 'super_admin'])) {
        echo "✅ SESSION ADMIN FONCTIONNE\n";
        echo "🔧 PROBLÈME: InterventionController bloque l'accès\n";
        echo "🔧 SOLUTION: Vérifier la logique d'autorisation\n";
    } else {
        echo "❌ PROBLÈME: SessionManager ou rôle admin\n";
        echo "🔧 SOLUTION: Vérifier la configuration\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>

