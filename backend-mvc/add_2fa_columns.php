<?php
/**
 * Ajouter les colonnes 2FA à la table users
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔐 Ajout des colonnes 2FA à la table users\n";
echo "==========================================\n\n";

// Configuration de la base de données
$host = "localhost";
$dbname = "exemple";
$username = "root";
$password = EnvService::get('DB_PASS', 'root');
$port = 8889;

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "✅ Connexion à la base de données réussie !\n\n";
    
    // Vérifier les colonnes existantes
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📋 Colonnes existantes dans users :\n";
    foreach ($columns as $column) {
        echo "   - $column\n";
    }
    echo "\n";
    
    // Ajouter les colonnes 2FA si elles n'existent pas
    $newColumns = [
        'two_factor_enabled' => "ALTER TABLE users ADD COLUMN two_factor_enabled BOOLEAN DEFAULT FALSE",
        'two_factor_required' => "ALTER TABLE users ADD COLUMN two_factor_required BOOLEAN DEFAULT FALSE",
        'two_factor_secret' => "ALTER TABLE users ADD COLUMN two_factor_secret VARCHAR(32) DEFAULT NULL",
        'two_factor_backup_codes' => "ALTER TABLE users ADD COLUMN two_factor_backup_codes TEXT DEFAULT NULL"
    ];
    
    foreach ($newColumns as $columnName => $sql) {
        if (!in_array($columnName, $columns)) {
            echo "🔧 Ajout de la colonne $columnName...\n";
            $pdo->exec($sql);
            echo "   ✅ Colonne $columnName ajoutée !\n";
        } else {
            echo "ℹ️ Colonne $columnName existe déjà - ignorée\n";
        }
    }
    
    echo "\n🎯 Configuration des admins avec 2FA obligatoire...\n";
    
    // Marquer les admins comme ayant la 2FA obligatoire
    $stmt = $pdo->prepare("UPDATE users SET two_factor_required = TRUE WHERE role = 'admin'");
    $result = $stmt->execute();
    
    if ($result) {
        $affectedRows = $stmt->rowCount();
        echo "✅ $affectedRows administrateurs marqués avec 2FA obligatoire\n";
    }
    
    echo "\n📊 Vérification finale :\n";
    $stmt = $pdo->query("SELECT id, name, role, two_factor_enabled, two_factor_required FROM users");
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        $status = $user['two_factor_enabled'] ? '✅ Activé' : '❌ Désactivé';
        $required = $user['two_factor_required'] ? '🔒 Obligatoire' : '🔓 Optionnel';
        echo "   - {$user['name']} ({$user['role']}): $status, $required\n";
    }
    
    echo "\n🎉 Configuration 2FA terminée !\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
?>
