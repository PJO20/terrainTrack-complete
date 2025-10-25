<?php
/**
 * Debug de la configuration 2FA
 */

require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/Database.php';
require_once __DIR__ . '/src/Service/SessionManager.php';

header('Content-Type: text/plain; charset=utf-8');

echo "🔍 DEBUG CONFIGURATION 2FA\n";
echo "==========================\n\n";

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
    
    echo "\n2️⃣ Vérification de la configuration 2FA en base:\n";
    
    $pdo = \App\Service\Database::connect();
    $stmt = $pdo->prepare("SELECT id, email, name, role, two_factor_enabled, two_factor_required, two_factor_secret FROM users WHERE email = ?");
    $stmt->execute(['momo@gmail.com']);
    $momoData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($momoData) {
        echo "   ✅ Données momo récupérées:\n";
        echo "     - ID: " . $momoData['id'] . "\n";
        echo "     - Email: " . $momoData['email'] . "\n";
        echo "     - Nom: " . ($momoData['name'] ?? 'NULL') . "\n";
        echo "     - Rôle: " . ($momoData['role'] ?? 'NULL') . "\n";
        echo "     - 2FA activé: " . ($momoData['two_factor_enabled'] ? 'OUI' : 'NON') . "\n";
        echo "     - 2FA requis: " . ($momoData['two_factor_required'] ? 'OUI' : 'NON') . "\n";
        echo "     - Secret 2FA: " . ($momoData['two_factor_secret'] ? 'PRÉSENT' : 'ABSENT') . "\n";
        
        if ($momoData['two_factor_required'] && !$momoData['two_factor_enabled']) {
            echo "   ⚠️ PROBLÈME: 2FA requis mais pas activé\n";
        } else if ($momoData['two_factor_required'] && $momoData['two_factor_enabled']) {
            echo "   ✅ 2FA requis et activé\n";
        } else {
            echo "   ℹ️ 2FA non requis\n";
        }
    } else {
        echo "   ❌ Impossible de récupérer les données momo\n";
    }
    
    echo "\n3️⃣ Vérification de tous les utilisateurs avec 2FA:\n";
    
    $stmt = $pdo->query("SELECT id, email, name, role, two_factor_enabled, two_factor_required FROM users ORDER BY id");
    $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Tous les utilisateurs:\n";
    foreach ($allUsers as $user) {
        $status2FA = '';
        if ($user['two_factor_required'] && $user['two_factor_enabled']) {
            $status2FA = ' ✅ 2FA ACTIF';
        } else if ($user['two_factor_required'] && !$user['two_factor_enabled']) {
            $status2FA = ' ⚠️ 2FA REQUIS MAIS NON ACTIVÉ';
        } else {
            $status2FA = ' ℹ️ 2FA NON REQUIS';
        }
        
        echo "     - ID: {$user['id']}, Email: {$user['email']}, Nom: " . ($user['name'] ?? 'NULL') . ", Rôle: " . ($user['role'] ?? 'NULL') . "$status2FA\n";
    }
    
    echo "\n4️⃣ Test de la logique 2FA dans SessionManager:\n";
    
    // Vérifier si SessionManager vérifie la 2FA
    $sessionManagerFile = __DIR__ . '/src/Service/SessionManager.php';
    if (file_exists($sessionManagerFile)) {
        $content = file_get_contents($sessionManagerFile);
        
        if (strpos($content, 'two_factor') !== false) {
            echo "   ✅ SessionManager contient des références à 2FA\n";
        } else {
            echo "   ❌ SessionManager ne contient pas de références à 2FA\n";
        }
        
        if (strpos($content, 'requireLogin') !== false) {
            echo "   ✅ SessionManager contient la méthode requireLogin\n";
        } else {
            echo "   ❌ SessionManager ne contient pas la méthode requireLogin\n";
        }
    } else {
        echo "   ❌ Fichier SessionManager non trouvé\n";
    }
    
    echo "\n5️⃣ Vérification de la configuration système:\n";
    
    // Vérifier les paramètres système
    $stmt = $pdo->query("SELECT * FROM system_settings WHERE setting_key LIKE '%2fa%' OR setting_key LIKE '%two_factor%'");
    $systemSettings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($systemSettings)) {
        echo "   Paramètres système 2FA:\n";
        foreach ($systemSettings as $setting) {
            echo "     - {$setting['setting_key']}: {$setting['setting_value']}\n";
        }
    } else {
        echo "   ℹ️ Aucun paramètre système 2FA trouvé\n";
    }
    
    echo "\n6️⃣ Test de la logique de connexion:\n";
    
    // Simuler la logique de connexion
    echo "   Simulation de la logique de connexion...\n";
    
    if ($momoData && $momoData['two_factor_required']) {
        echo "   ✅ 2FA requis pour momo@gmail.com\n";
        
        if ($momoData['two_factor_enabled']) {
            echo "   ✅ 2FA activé - Code requis\n";
            echo "   🔧 SOLUTION: Vérifier que le code 2FA est demandé lors de la connexion\n";
        } else {
            echo "   ❌ 2FA requis mais pas activé\n";
            echo "   🔧 SOLUTION: Activer la 2FA pour momo@gmail.com\n";
        }
    } else {
        echo "   ℹ️ 2FA non requis pour momo@gmail.com\n";
        echo "   🔧 SOLUTION: Activer la 2FA requise pour les admins\n";
    }
    
    echo "\n7️⃣ Recommandations:\n";
    
    if ($momoData && $momoData['two_factor_required'] && !$momoData['two_factor_enabled']) {
        echo "   ❌ PROBLÈME: 2FA requis mais pas activé\n";
        echo "   🔧 SOLUTION: Activer la 2FA pour momo@gmail.com\n";
    } else if ($momoData && !$momoData['two_factor_required']) {
        echo "   ❌ PROBLÈME: 2FA non requis pour les admins\n";
        echo "   🔧 SOLUTION: Activer la 2FA requise pour les admins\n";
    } else if ($momoData && $momoData['two_factor_required'] && $momoData['two_factor_enabled']) {
        echo "   ✅ 2FA configuré correctement\n";
        echo "   🔧 PROBLÈME: La logique de vérification 2FA ne fonctionne pas\n";
        echo "   🔧 SOLUTION: Vérifier la logique de connexion\n";
    } else {
        echo "   ❌ PROBLÈME: Configuration 2FA inconnue\n";
        echo "   🔧 SOLUTION: Vérifier la configuration\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "RÉSUMÉ FINAL\n";
    echo str_repeat("=", 50) . "\n";
    
    if ($momoData && $momoData['two_factor_required'] && $momoData['two_factor_enabled']) {
        echo "✅ 2FA CONFIGURÉ CORRECTEMENT\n";
        echo "🔧 PROBLÈME: Logique de vérification 2FA\n";
        echo "🔧 SOLUTION: Vérifier la logique de connexion\n";
    } else if ($momoData && $momoData['two_factor_required'] && !$momoData['two_factor_enabled']) {
        echo "❌ PROBLÈME: 2FA REQUIS MAIS PAS ACTIVÉ\n";
        echo "🔧 SOLUTION: Activer la 2FA pour momo@gmail.com\n";
    } else {
        echo "❌ PROBLÈME: 2FA NON REQUIS POUR LES ADMINS\n";
        echo "🔧 SOLUTION: Activer la 2FA requise pour les admins\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
