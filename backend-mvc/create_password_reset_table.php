<?php
/**
 * Script pour créer la table password_reset_tokens
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Service\Database;

try {
    $pdo = Database::connect();
    
    echo "🔧 Création de la table password_reset_tokens...\n";
    
    $sql = "
    CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(255) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_token (token),
        INDEX idx_user_id (user_id),
        INDEX idx_expires_at (expires_at)
    )";
    
    $pdo->exec($sql);
    
    echo "✅ Table password_reset_tokens créée avec succès !\n";
    
    // Vérifier la structure de la table
    $stmt = $pdo->query("DESCRIBE password_reset_tokens");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📋 Structure de la table :\n";
    foreach ($columns as $column) {
        echo "  - {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\n🎉 Migration terminée avec succès !\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la création de la table : " . $e->getMessage() . "\n";
    exit(1);
}
?>
