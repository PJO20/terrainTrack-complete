<?php
/**
 * Créer la table maintenance_schedules
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔧 Création de la table maintenance_schedules\n";
echo "============================================\n\n";

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
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_vehicle_id (vehicle_id),
        INDEX idx_due_date (due_date),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($createTableSQL);
    echo "   ✅ Table maintenance_schedules créée !\n";
    
    echo "\n2. 📋 Création de la table notification_preferences...\n";
    
    $createPreferencesSQL = "
    CREATE TABLE IF NOT EXISTS notification_preferences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        email_notifications TINYINT(1) DEFAULT 1,
        sms_notifications TINYINT(1) DEFAULT 0,
        intervention_assignments TINYINT(1) DEFAULT 1,
        maintenance_reminders TINYINT(1) DEFAULT 1,
        critical_alerts TINYINT(1) DEFAULT 1,
        reminder_frequency_days INT DEFAULT 7,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_preferences (user_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($createPreferencesSQL);
    echo "   ✅ Table notification_preferences créée !\n";
    
    echo "\n3. 📋 Création de la table notification_logs...\n";
    
    $createLogsSQL = "
    CREATE TABLE IF NOT EXISTS notification_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        notification_type VARCHAR(100) NOT NULL,
        subject VARCHAR(255),
        recipient_email VARCHAR(255),
        status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
        sent_at TIMESTAMP NULL,
        error_message TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_notification_type (notification_type),
        INDEX idx_status (status),
        INDEX idx_sent_at (sent_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($createLogsSQL);
    echo "   ✅ Table notification_logs créée !\n";
    
    echo "\n4. 📋 Création de la table notification_queue...\n";
    
    $createQueueSQL = "
    CREATE TABLE IF NOT EXISTS notification_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        notification_type VARCHAR(100) NOT NULL,
        subject VARCHAR(255),
        body TEXT,
        recipient_email VARCHAR(255),
        priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
        scheduled_at TIMESTAMP NULL,
        attempts INT DEFAULT 0,
        max_attempts INT DEFAULT 3,
        status ENUM('pending', 'processing', 'sent', 'failed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_notification_type (notification_type),
        INDEX idx_status (status),
        INDEX idx_scheduled_at (scheduled_at),
        INDEX idx_priority (priority)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($createQueueSQL);
    echo "   ✅ Table notification_queue créée !\n";
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ Toutes les tables de notifications créées !\n";
    echo str_repeat("=", 50) . "\n";
    
    echo "\n📋 Tables créées :\n";
    echo "   ✅ maintenance_schedules - Planification des entretiens\n";
    echo "   ✅ notification_preferences - Préférences des utilisateurs\n";
    echo "   ✅ notification_logs - Historique des notifications\n";
    echo "   ✅ notification_queue - File d'attente des notifications\n";
    
    echo "\n🚀 Maintenant vous pouvez relancer :\n";
    echo "   php activate_notifications_direct.php\n";
    
} catch (PDOException $e) {
    echo "❌ ERREUR DE BASE DE DONNÉES : " . $e->getMessage() . "\n";
    echo "Vérifiez que MAMP est démarré et que la base 'exemple' existe.\n";
} catch (Exception $e) {
    echo "❌ ERREUR : " . $e->getMessage() . "\n";
}
?>
