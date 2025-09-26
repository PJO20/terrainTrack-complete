<?php
/**
 * Script pour diagnostiquer le problème de connexion
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔍 Diagnostic du problème de connexion\n";
echo "====================================\n\n";

// Configuration directe de la base de données MAMP
$dbHost = 'localhost';
$dbName = 'exemple';
$dbUser = 'root';
$dbPass = 'root';
$dbPort = 8889;

try {
    // Connexion à la base de données
    $db = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    echo "✅ Connexion à la base de données réussie !\n\n";
    
    echo "1. 👥 Vérification des utilisateurs...\n";
    
    $stmt = $db->query("SELECT id, name, email, role, is_active FROM users ORDER BY id");
    $users = $stmt->fetchAll();
    
    echo "   Utilisateurs trouvés : " . count($users) . "\n";
    foreach ($users as $user) {
        $status = $user['is_active'] ? '✅ Actif' : '❌ Inactif';
        echo "   - ID: {$user['id']}, Nom: {$user['name']}, Email: {$user['email']}, Rôle: {$user['role']}, Statut: {$status}\n";
    }
    
    echo "\n2. 🔐 Vérification des mots de passe...\n";
    
    // Vérifier si les mots de passe sont hashés
    $stmt = $db->query("SELECT id, name, email, password FROM users LIMIT 3");
    $sampleUsers = $stmt->fetchAll();
    
    foreach ($sampleUsers as $user) {
        $password = $user['password'];
        $isHashed = password_get_info($password)['algo'] !== null;
        $hashType = $isHashed ? 'Hashé' : 'Non hashé';
        echo "   - {$user['name']} ({$user['email']}) : {$hashType}\n";
    }
    
    echo "\n3. 🧪 Test de connexion avec un utilisateur...\n";
    
    if (!empty($users)) {
        $testUser = $users[0];
        echo "   Test avec : {$testUser['name']} ({$testUser['email']})\n";
        
        // Vérifier le mot de passe
        $stmt = $db->prepare("SELECT password FROM users WHERE email = ?");
        $stmt->execute([$testUser['email']]);
        $userData = $stmt->fetch();
        
        if ($userData) {
            $password = $userData['password'];
            $isHashed = password_get_info($password)['algo'] !== null;
            
            if ($isHashed) {
                echo "   ✅ Mot de passe hashé correctement\n";
            } else {
                echo "   ⚠️ Mot de passe non hashé - cela peut causer des problèmes de connexion\n";
            }
        }
    }
    
    echo "\n4. 🔧 Vérification de la structure de la table users...\n";
    
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    
    echo "   Colonnes de la table users :\n";
    foreach ($columns as $column) {
        echo "   - {$column['Field']} ({$column['Type']}) - {$column['Null']} - {$column['Key']}\n";
    }
    
    echo "\n5. 🛠️ Correction des mots de passe si nécessaire...\n";
    
    // Vérifier si des mots de passe ne sont pas hashés
    $stmt = $db->query("SELECT id, email, password FROM users WHERE password NOT LIKE '$2y$%'");
    $unhashedUsers = $stmt->fetchAll();
    
    if (!empty($unhashedUsers)) {
        echo "   ⚠️ Utilisateurs avec mots de passe non hashés : " . count($unhashedUsers) . "\n";
        
        foreach ($unhashedUsers as $user) {
            // Hasher le mot de passe
            $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $result = $stmt->execute([$hashedPassword, $user['id']]);
            
            if ($result) {
                echo "   ✅ Mot de passe hashé pour {$user['email']}\n";
            } else {
                echo "   ❌ Erreur lors du hashage pour {$user['email']}\n";
            }
        }
    } else {
        echo "   ✅ Tous les mots de passe sont correctement hashés\n";
    }
    
    echo "\n6. 🔑 Création d'un utilisateur de test...\n";
    
    // Vérifier si un utilisateur admin existe
    $stmt = $db->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $adminExists = $stmt->fetch();
    
    if (!$adminExists) {
        echo "   ⚠️ Aucun administrateur trouvé, création d'un admin de test...\n";
        
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, role, is_active, created_at, updated_at)
            VALUES (?, ?, ?, ?, 1, NOW(), NOW())
        ");
        $result = $stmt->execute(['Administrateur', 'admin@terraintrack.com', $adminPassword, 'admin']);
        
        if ($result) {
            echo "   ✅ Administrateur créé : admin@terraintrack.com / admin123\n";
        } else {
            echo "   ❌ Erreur lors de la création de l'administrateur\n";
        }
    } else {
        echo "   ✅ Administrateur existant trouvé\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ DIAGNOSTIC TERMINÉ !\n";
    echo str_repeat("=", 50) . "\n";
    
    echo "\n📋 Résumé :\n";
    echo "   - Utilisateurs : " . count($users) . "\n";
    echo "   - Mots de passe hashés : " . (count($users) - count($unhashedUsers)) . "/" . count($users) . "\n";
    echo "   - Administrateurs : " . ($adminExists ? "1" : "0") . "\n";
    
    echo "\n🔑 Identifiants de test :\n";
    echo "   - Email : admin@terraintrack.com\n";
    echo "   - Mot de passe : admin123\n";
    
    echo "\n🚀 Essayez de vous connecter avec ces identifiants !\n";
    
} catch (PDOException $e) {
    echo "❌ ERREUR DE BASE DE DONNÉES : " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ ERREUR : " . $e->getMessage() . "\n";
}
?>
