<?php

/**
 * Script de diagnostic pour comprendre le problème d'affichage du nom
 */

// Charger l'autoloader et les services
require_once '../vendor/autoload.php';
require_once '../config/database.php';

use App\Service\SessionManager;

session_start();

// Se connecter automatiquement avec l'utilisateur admin
$_SESSION['user'] = [
    'id' => 7,
    'email' => 'admin@terraintrack.com',
    'name' => 'Super Administrateur',
    'role' => 'admin',
    'is_admin' => true
];
$_SESSION['authenticated'] = true;
$_SESSION['last_activity'] = time();

echo "<h1>🔍 Diagnostic des données utilisateur</h1>";

// 1. Vérifier la session
echo "<h2>📋 Session actuelle :</h2>";
if (isset($_SESSION['user'])) {
    echo "<pre>";
    print_r($_SESSION['user']);
    echo "</pre>";
    $userId = $_SESSION['user']['id'];
} else {
    echo "❌ Aucune session utilisateur trouvée<br>";
    exit;
}

// 2. Vérifier SessionManager
echo "<h2>🔧 SessionManager::getCurrentUser() :</h2>";
$sessionUser = SessionManager::getCurrentUser();
if ($sessionUser) {
    echo "<pre>";
    print_r($sessionUser);
    echo "</pre>";
} else {
    echo "❌ SessionManager ne retourne pas d'utilisateur<br>";
}

// 3. Vérifier directement en base de données
echo "<h2>🗄️ Données en base de données (requête directe) :</h2>";
try {
    // Utiliser le fichier de config database.php qui retourne $db
    $pdo = include '../config/database.php';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userFromDb = $stmt->fetch();
    
    if ($userFromDb) {
        echo "<pre>";
        print_r($userFromDb);
        echo "</pre>";
    } else {
        echo "❌ Utilisateur non trouvé en base avec ID: $userId<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur base de données: " . $e->getMessage() . "<br>";
}

// 5. Tester la méthode getName()
echo "<h2>🎯 Test de la logique getName() :</h2>";
if (isset($userFromDb)) {
    $name = $userFromDb['name'] ?? null;
    $email = $userFromDb['email'] ?? null;
    
    echo "- Colonne 'name' en base: " . ($name ? "'{$name}'" : "NULL/EMPTY") . "<br>";
    echo "- Colonne 'email' en base: " . ($email ? "'{$email}'" : "NULL/EMPTY") . "<br>";
    
    // Reproduire la logique de User::getName()
    $finalName = $name ?: $email;
    echo "- Résultat final getName(): '{$finalName}'<br>";
    
    if ($name === $email || empty($name)) {
        echo "⚠️ <strong>PROBLÈME IDENTIFIÉ:</strong> La colonne 'name' est vide ou identique à l'email !<br>";
    }
}

echo "<hr><h2>💡 Diagnostic terminé</h2>";

?>

<style>
body { font-family: Arial, sans-serif; margin: 2rem; }
h1, h2 { color: #333; }
pre { background: #f5f5f5; padding: 1rem; border-radius: 4px; overflow-x: auto; }
</style>
