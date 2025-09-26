<?php
/**
 * Créer simplement la table maintenance_schedules
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔧 Création de la table maintenance_schedules\n";
echo "===========================================\n\n";

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
    
    echo "1. 📋 Création de la table maintenance_schedules...\n";
    
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS maintenance_schedules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vehicle_id INT NOT NULL,
        vehicle_name VARCHAR(255) NOT NULL,
        maintenance_type VARCHAR(255) NOT NULL,
        due_date DATE NOT NULL,
        description TEXT,
        priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
        assigned_technician VARCHAR(255),
        status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($createTableSQL);
    echo "   ✅ Table maintenance_schedules créée !\n";
    
    echo "\n2. 📋 Vérification de la table...\n";
    
    $stmt = $db->query("DESCRIBE maintenance_schedules");
    $columns = $stmt->fetchAll();
    
    echo "   Colonnes de la table maintenance_schedules :\n";
    foreach ($columns as $column) {
        echo "   - {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ Table maintenance_schedules créée avec succès !\n";
    echo str_repeat("=", 50) . "\n";
    
    echo "\n🚀 Maintenant vous pouvez relancer :\n";
    echo "   php activate_notifications_direct.php\n";
    
} catch (PDOException $e) {
    echo "❌ ERREUR DE BASE DE DONNÉES : " . $e->getMessage() . "\n";
    echo "Vérifiez que MAMP est démarré et que la base 'exemple' existe.\n";
} catch (Exception $e) {
    echo "❌ ERREUR : " . $e->getMessage() . "\n";
}
?>
