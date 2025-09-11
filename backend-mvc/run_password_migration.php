<?php
/**
 * Script pour exécuter la migration de la colonne password_updated_at
 */

try {
    // Connexion directe à la base de données
    $pdo = new PDO('mysql:host=localhost;port=8889;dbname=exemple', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔄 Exécution de la migration password_updated_at...\n";
    
    // Vérifier si la colonne existe déjà
    $checkColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'password_updated_at'");
    if ($checkColumn->rowCount() > 0) {
        echo "ℹ️  La colonne password_updated_at existe déjà\n";
    } else {
        // Ajouter la colonne password_updated_at
        $sql1 = "ALTER TABLE users ADD COLUMN password_updated_at TIMESTAMP NULL DEFAULT NULL";
        $pdo->exec($sql1);
        echo "✅ Colonne password_updated_at ajoutée\n";
    }
    
    // Mettre à jour les utilisateurs existants
    $sql2 = "UPDATE users SET password_updated_at = created_at WHERE password_updated_at IS NULL";
    $result = $pdo->exec($sql2);
    echo "✅ $result utilisateur(s) mis à jour avec la date de création\n";
    
    echo "🎉 Migration terminée avec succès !\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la migration: " . $e->getMessage() . "\n";
}
?>