<?php
/**
 * Debug du problème de cache des données de profil
 * Vérifie les données de session, base de données et cache
 */

require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/Database.php';
require_once __DIR__ . '/src/Service/SessionManager.php';

header('Content-Type: text/plain; charset=utf-8');

echo "🔍 DEBUG CACHE PROFIL UTILISATEUR\n";
echo "=================================\n\n";

try {
    // Démarrer la session si elle n'est pas déjà démarrée
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    echo "1️⃣ Vérification de la session:\n";
    echo "   Session ID: " . session_id() . "\n";
    echo "   Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE') . "\n";
    
    if (isset($_SESSION['user'])) {
        echo "   Utilisateur en session:\n";
        echo "     - ID: " . ($_SESSION['user']['id'] ?? 'Non défini') . "\n";
        echo "     - Email: " . ($_SESSION['user']['email'] ?? 'Non défini') . "\n";
        echo "     - Nom: " . ($_SESSION['user']['name'] ?? 'Non défini') . "\n";
        echo "     - Rôle: " . ($_SESSION['user']['role'] ?? 'Non défini') . "\n";
    } else {
        echo "   ❌ Aucun utilisateur en session\n";
    }
    
    echo "\n2️⃣ Vérification de l'authentification:\n";
    $isAuthenticated = \App\Service\SessionManager::isAuthenticated();
    echo "   Authentifié: " . ($isAuthenticated ? 'OUI' : 'NON') . "\n";
    
    if ($isAuthenticated) {
        $currentUser = \App\Service\SessionManager::getCurrentUser();
        if ($currentUser) {
            echo "   Utilisateur actuel:\n";
            echo "     - ID: " . $currentUser['id'] . "\n";
            echo "     - Email: " . $currentUser['email'] . "\n";
            echo "     - Nom: " . ($currentUser['name'] ?? 'Non défini') . "\n";
            echo "     - Rôle: " . ($currentUser['role'] ?? 'Non défini') . "\n";
        } else {
            echo "   ❌ Impossible de récupérer l'utilisateur actuel\n";
        }
    }
    
    echo "\n3️⃣ Vérification en base de données:\n";
    $pdo = \App\Service\Database::connect();
    
    if ($isAuthenticated && isset($currentUser)) {
        $userId = $currentUser['id'];
        
        // Récupérer les données utilisateur depuis la base
        $stmt = $pdo->prepare("SELECT id, email, name, phone, role, department, location, timezone, language, avatar FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            echo "   Données en base pour l'utilisateur ID $userId:\n";
            echo "     - ID: " . $userData['id'] . "\n";
            echo "     - Email: " . $userData['email'] . "\n";
            echo "     - Nom: " . ($userData['name'] ?? 'NULL') . "\n";
            echo "     - Téléphone: " . ($userData['phone'] ?? 'NULL') . "\n";
            echo "     - Rôle: " . ($userData['role'] ?? 'NULL') . "\n";
            echo "     - Département: " . ($userData['department'] ?? 'NULL') . "\n";
            echo "     - Localisation: " . ($userData['location'] ?? 'NULL') . "\n";
            echo "     - Fuseau horaire: " . ($userData['timezone'] ?? 'NULL') . "\n";
            echo "     - Langue: " . ($userData['language'] ?? 'NULL') . "\n";
            echo "     - Avatar: " . ($userData['avatar'] ?? 'NULL') . "\n";
        } else {
            echo "   ❌ Aucune donnée trouvée en base pour l'utilisateur ID $userId\n";
        }
    }
    
    echo "\n4️⃣ Vérification de tous les utilisateurs:\n";
    $stmt = $pdo->query("SELECT id, email, name, role FROM users ORDER BY id");
    $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Tous les utilisateurs en base:\n";
    foreach ($allUsers as $user) {
        echo "     - ID: {$user['id']}, Email: {$user['email']}, Nom: " . ($user['name'] ?? 'NULL') . ", Rôle: " . ($user['role'] ?? 'NULL') . "\n";
    }
    
    echo "\n5️⃣ Test de nettoyage du cache:\n";
    
    // Nettoyer les variables de session liées au cache
    if (isset($_SESSION['user_data_cache'])) {
        unset($_SESSION['user_data_cache']);
        echo "   ✅ Cache utilisateur supprimé de la session\n";
    } else {
        echo "   ℹ️ Aucun cache utilisateur trouvé en session\n";
    }
    
    // Nettoyer les variables de session liées aux paramètres
    if (isset($_SESSION['settings_cache'])) {
        unset($_SESSION['settings_cache']);
        echo "   ✅ Cache paramètres supprimé de la session\n";
    } else {
        echo "   ℹ️ Aucun cache paramètres trouvé en session\n";
    }
    
    echo "\n6️⃣ Test de rechargement des données:\n";
    
    if ($isAuthenticated && isset($currentUser)) {
        $userId = $currentUser['id'];
        
        // Simuler le rechargement comme dans SettingsController
        $stmt = $pdo->prepare("SELECT id, email, name, phone, role, department, location, timezone, language, avatar FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $reloadedUserData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reloadedUserData) {
            echo "   Données rechargées:\n";
            echo "     - Email: " . $reloadedUserData['email'] . "\n";
            echo "     - Nom: " . ($reloadedUserData['name'] ?? 'NULL') . "\n";
            echo "     - Rôle: " . ($reloadedUserData['role'] ?? 'NULL') . "\n";
            
            // Vérifier si les données correspondent à la session
            if ($reloadedUserData['email'] === $currentUser['email']) {
                echo "   ✅ Données cohérentes entre session et base\n";
            } else {
                echo "   ❌ Incohérence entre session et base\n";
                echo "     Session: " . $currentUser['email'] . "\n";
                echo "     Base: " . $reloadedUserData['email'] . "\n";
            }
        }
    }
    
    echo "\n7️⃣ Recommandations:\n";
    
    if (!$isAuthenticated) {
        echo "   ❌ Utilisateur non authentifié - Reconnectez-vous\n";
    } else if (isset($currentUser) && isset($userData)) {
        if ($currentUser['email'] !== $userData['email']) {
            echo "   ❌ Incohérence détectée - Nettoyage de session recommandé\n";
            echo "   🔧 Solution: Déconnexion et reconnexion\n";
        } else {
            echo "   ✅ Données cohérentes - Problème probablement côté cache client\n";
            echo "   🔧 Solution: Vider le cache du navigateur (Ctrl+F5)\n";
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "RÉSUMÉ DU DIAGNOSTIC\n";
    echo str_repeat("=", 50) . "\n";
    
    if (!$isAuthenticated) {
        echo "❌ PROBLÈME: Utilisateur non authentifié\n";
        echo "🔧 SOLUTION: Reconnectez-vous avec momo@gmail.com\n";
    } else if (isset($currentUser)) {
        echo "✅ Utilisateur authentifié: " . $currentUser['email'] . "\n";
        echo "🔧 SOLUTION: Vider le cache du navigateur (Ctrl+F5)\n";
        echo "🔧 SOLUTION ALTERNATIVE: Déconnexion et reconnexion\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
