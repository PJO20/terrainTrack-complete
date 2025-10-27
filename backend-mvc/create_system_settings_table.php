<?php
/**
 * Script de création de la table system_settings
 */

require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/Database.php';

try {
    echo "🔧 Création de la table system_settings...\n";
    
    // Connexion à la base de données
    $pdo = \App\Service\Database::connect();
    
    // Lire le fichier SQL
    $sql = file_get_contents(__DIR__ . '/migrations/create_system_settings_table.sql');
    
    if (!$sql) {
        throw new Exception("Impossible de lire le fichier SQL");
    }
    
    // Exécuter les requêtes
    $pdo->exec($sql);
    
    echo "✅ Table system_settings créée avec succès\n";
    echo "✅ Paramètres par défaut insérés pour tous les utilisateurs\n";
    
    // Vérifier les données
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM system_settings");
    $count = $stmt->fetch()['count'];
    echo "📊 Nombre de paramètres créés: $count\n";
    
    // Afficher les paramètres par utilisateur
    $stmt = $pdo->query("
        SELECT 
            u.email,
            COUNT(ss.id) as settings_count
        FROM users u
        LEFT JOIN system_settings ss ON u.id = ss.user_id
        GROUP BY u.id, u.email
        ORDER BY u.email
    ");
    
    echo "\n📋 Paramètres par utilisateur:\n";
    while ($row = $stmt->fetch()) {
        echo "   {$row['email']}: {$row['settings_count']} paramètres\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
?>

