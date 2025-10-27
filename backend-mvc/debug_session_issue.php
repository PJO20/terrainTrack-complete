<?php
/**
 * Debug approfondi du problème de session
 * Vérifie pourquoi les données de momo@gmail.com ne s'affichent pas
 */

require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/Database.php';
require_once __DIR__ . '/src/Service/SessionManager.php';

// Démarrer la session proprement
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: text/plain; charset=utf-8');

echo "🔍 DEBUG APPROFONDI SESSION UTILISATEUR\n";
echo "======================================\n\n";

try {
    echo "1️⃣ État de la session:\n";
    echo "   Session ID: " . session_id() . "\n";
    echo "   Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE') . "\n";
    echo "   Session name: " . session_name() . "\n";
    
    echo "\n2️⃣ Contenu de la session:\n";
    if (!empty($_SESSION)) {
        foreach ($_SESSION as $key => $value) {
            if (is_array($value)) {
                echo "   $key: " . json_encode($value) . "\n";
            } else {
                echo "   $key: $value\n";
            }
        }
    } else {
        echo "   ❌ Session vide\n";
    }
    
    echo "\n3️⃣ Test SessionManager:\n";
    $isAuthenticated = \App\Service\SessionManager::isAuthenticated();
    echo "   isAuthenticated(): " . ($isAuthenticated ? 'TRUE' : 'FALSE') . "\n";
    
    if ($isAuthenticated) {
        $currentUser = \App\Service\SessionManager::getCurrentUser();
        if ($currentUser) {
            echo "   getCurrentUser():\n";
            echo "     - ID: " . ($currentUser['id'] ?? 'NULL') . "\n";
            echo "     - Email: " . ($currentUser['email'] ?? 'NULL') . "\n";
            echo "     - Nom: " . ($currentUser['name'] ?? 'NULL') . "\n";
            echo "     - Rôle: " . ($currentUser['role'] ?? 'NULL') . "\n";
        } else {
            echo "   ❌ getCurrentUser() retourne NULL\n";
        }
    }
    
    echo "\n4️⃣ Vérification en base de données:\n";
    $pdo = \App\Service\Database::connect();
    
    // Vérifier tous les utilisateurs
    $stmt = $pdo->query("SELECT id, email, name, role FROM users ORDER BY id");
    $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Tous les utilisateurs:\n";
    foreach ($allUsers as $user) {
        echo "     - ID: {$user['id']}, Email: {$user['email']}, Nom: " . ($user['name'] ?? 'NULL') . ", Rôle: " . ($user['role'] ?? 'NULL') . "\n";
    }
    
    echo "\n5️⃣ Test de récupération des données momo@gmail.com:\n";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['momo@gmail.com']);
    $momoData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($momoData) {
        echo "   ✅ Données momo@gmail.com en base:\n";
        foreach ($momoData as $key => $value) {
            echo "     - $key: " . ($value ?? 'NULL') . "\n";
        }
    } else {
        echo "   ❌ momo@gmail.com non trouvé en base\n";
    }
    
    echo "\n6️⃣ Test de récupération des données pjorsini20@gmail.com:\n";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['pjorsini20@gmail.com']);
    $pjorsiniData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($pjorsiniData) {
        echo "   ✅ Données pjorsini20@gmail.com en base:\n";
        echo "     - ID: " . $pjorsiniData['id'] . "\n";
        echo "     - Email: " . $pjorsiniData['email'] . "\n";
        echo "     - Nom: " . ($pjorsiniData['name'] ?? 'NULL') . "\n";
        echo "     - Rôle: " . ($pjorsiniData['role'] ?? 'NULL') . "\n";
    } else {
        echo "   ❌ pjorsini20@gmail.com non trouvé en base\n";
    }
    
    echo "\n7️⃣ Simulation du SettingsController:\n";
    
    if ($isAuthenticated && isset($currentUser)) {
        $userId = $currentUser['id'];
        echo "   Utilisateur en session ID: $userId\n";
        
        // Simuler la requête du SettingsController
        $stmt = $pdo->prepare("SELECT id, email, name, phone, role, department, location, timezone, language, avatar FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            echo "   Données récupérées par SettingsController:\n";
            echo "     - ID: " . $userData['id'] . "\n";
            echo "     - Email: " . $userData['email'] . "\n";
            echo "     - Nom: " . ($userData['name'] ?? 'NULL') . "\n";
            echo "     - Téléphone: " . ($userData['phone'] ?? 'NULL') . "\n";
            echo "     - Rôle: " . ($userData['role'] ?? 'NULL') . "\n";
            echo "     - Département: " . ($userData['department'] ?? 'NULL') . "\n";
            echo "     - Localisation: " . ($userData['location'] ?? 'NULL') . "\n";
            echo "     - Fuseau horaire: " . ($userData['timezone'] ?? 'NULL') . "\n";
            echo "     - Langue: " . ($userData['language'] ?? 'NULL') . "\n";
        } else {
            echo "   ❌ Aucune donnée trouvée pour l'ID $userId\n";
        }
    } else {
        echo "   ❌ Pas d'utilisateur authentifié\n";
    }
    
    echo "\n8️⃣ Test de création de session momo:\n";
    
    // Simuler la création d'une session pour momo
    $momoId = 7; // ID de momo@gmail.com
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$momoId]);
    $momoUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($momoUser) {
        echo "   Simulation session momo (ID: $momoId):\n";
        echo "     - Email: " . $momoUser['email'] . "\n";
        echo "     - Nom: " . ($momoUser['name'] ?? 'NULL') . "\n";
        echo "     - Rôle: " . ($momoUser['role'] ?? 'NULL') . "\n";
        
        // Simuler la création de session
        $_SESSION['user'] = [
            'id' => $momoUser['id'],
            'email' => $momoUser['email'],
            'name' => $momoUser['name'],
            'role' => $momoUser['role']
        ];
        $_SESSION['authenticated'] = true;
        
        echo "   ✅ Session momo créée en simulation\n";
        echo "   Session après création:\n";
        foreach ($_SESSION as $key => $value) {
            if (is_array($value)) {
                echo "     $key: " . json_encode($value) . "\n";
            } else {
                echo "     $key: $value\n";
            }
        }
    }
    
    echo "\n9️⃣ Recommandations:\n";
    
    if (!$isAuthenticated) {
        echo "   ❌ PROBLÈME: Pas d'utilisateur authentifié\n";
        echo "   🔧 SOLUTION: Se reconnecter avec momo@gmail.com\n";
    } else if (isset($currentUser)) {
        if ($currentUser['email'] === 'momo@gmail.com') {
            echo "   ✅ Utilisateur momo authentifié\n";
            echo "   🔧 VÉRIFIER: Les données devraient s'afficher correctement\n";
        } else {
            echo "   ❌ PROBLÈME: Utilisateur authentifié mais pas momo\n";
            echo "   🔧 SOLUTION: Se déconnecter et se reconnecter avec momo@gmail.com\n";
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "RÉSUMÉ DU DIAGNOSTIC\n";
    echo str_repeat("=", 50) . "\n";
    
    if (!$isAuthenticated) {
        echo "❌ PROBLÈME: Aucune session active\n";
        echo "🔧 SOLUTION: Se connecter avec momo@gmail.com\n";
    } else if (isset($currentUser) && $currentUser['email'] === 'momo@gmail.com') {
        echo "✅ Utilisateur momo authentifié - Problème côté affichage\n";
        echo "🔧 SOLUTION: Vider le cache navigateur (Ctrl+Shift+R)\n";
    } else if (isset($currentUser)) {
        echo "❌ PROBLÈME: Mauvais utilisateur en session (" . $currentUser['email'] . ")\n";
        echo "🔧 SOLUTION: Se déconnecter et se reconnecter avec momo@gmail.com\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>

