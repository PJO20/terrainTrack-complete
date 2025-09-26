<?php
/**
 * Script de migration pour créer les tables de notifications automatiques
 * Base de données : exemple
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Migration - Tables de Notifications</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #2196f3; }
        .warning { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107; }
        .step { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #6c757d; }
        h1 { color: #333; text-align: center; }
        h2 { color: #495057; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 3px; font-family: monospace; margin: 10px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔔 Migration - Tables de Notifications Automatiques</h1>
        <p><strong>Base de données :</strong> exemple</p>";

try {
    // Connexion à la base de données
    echo "<div class='step'><h2>🔌 Étape 1 : Connexion à la base de données</h2>";
    
    $pdo = new PDO(
        'mysql:host=localhost;dbname=exemple;charset=utf8mb4',
        'root',
        'root',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "<p class='success'>✅ Connexion à la base 'exemple' réussie</p></div>";
    
    // Vérifier l'existence des tables
    echo "<div class='step'><h2>🔍 Étape 2 : Vérification des tables existantes</h2>";
    
    $tables = ['notification_preferences', 'maintenance_schedules', 'notification_queue', 'notification_logs'];
    $existingTables = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $existingTables[] = $table;
            echo "<p class='warning'>⚠️ Table '$table' existe déjà</p>";
        } else {
            echo "<p class='info'>ℹ️ Table '$table' n'existe pas - sera créée</p>";
        }
    }
    echo "</div>";
    
    // Créer la table notification_preferences
    echo "<div class='step'><h2>📋 Étape 3 : Création de la table notification_preferences</h2>";
    
    if (!in_array('notification_preferences', $existingTables)) {
        $sql = "CREATE TABLE notification_preferences (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            email_notifications BOOLEAN DEFAULT TRUE,
            sms_notifications BOOLEAN DEFAULT FALSE,
            intervention_assignments BOOLEAN DEFAULT TRUE,
            maintenance_reminders BOOLEAN DEFAULT TRUE,
            critical_alerts BOOLEAN DEFAULT TRUE,
            reminder_frequency_days INT DEFAULT 7,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        
        $pdo->exec($sql);
        echo "<p class='success'>✅ Table 'notification_preferences' créée avec succès</p>";
    } else {
        echo "<p class='info'>ℹ️ Table 'notification_preferences' existe déjà - ignorée</p>";
    }
    echo "</div>";
    
    // Créer la table maintenance_schedules
    echo "<div class='step'><h2>🔧 Étape 4 : Création de la table maintenance_schedules</h2>";
    
    if (!in_array('maintenance_schedules', $existingTables)) {
        $sql = "CREATE TABLE maintenance_schedules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vehicle_id INT NOT NULL,
            maintenance_type VARCHAR(100) NOT NULL,
            scheduled_date DATE NOT NULL,
            due_date DATE NOT NULL,
            priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            description TEXT,
            assigned_technician_id INT,
            status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
            FOREIGN KEY (assigned_technician_id) REFERENCES users(id) ON DELETE SET NULL
        )";
        
        $pdo->exec($sql);
        echo "<p class='success'>✅ Table 'maintenance_schedules' créée avec succès</p>";
    } else {
        echo "<p class='info'>ℹ️ Table 'maintenance_schedules' existe déjà - ignorée</p>";
    }
    echo "</div>";
    
    // Créer la table notification_queue
    echo "<div class='step'><h2>📬 Étape 5 : Création de la table notification_queue</h2>";
    
    if (!in_array('notification_queue', $existingTables)) {
        $sql = "CREATE TABLE notification_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            notification_type ENUM('email', 'sms', 'push') NOT NULL,
            subject VARCHAR(255),
            message TEXT NOT NULL,
            template_name VARCHAR(100),
            template_data JSON,
            priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            status ENUM('pending', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
            attempts INT DEFAULT 0,
            max_attempts INT DEFAULT 3,
            scheduled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            sent_at TIMESTAMP NULL,
            error_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        
        $pdo->exec($sql);
        echo "<p class='success'>✅ Table 'notification_queue' créée avec succès</p>";
    } else {
        echo "<p class='info'>ℹ️ Table 'notification_queue' existe déjà - ignorée</p>";
    }
    echo "</div>";
    
    // Créer la table notification_logs
    echo "<div class='step'><h2>📊 Étape 6 : Création de la table notification_logs</h2>";
    
    if (!in_array('notification_logs', $existingTables)) {
        $sql = "CREATE TABLE notification_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            notification_type ENUM('email', 'sms', 'push') NOT NULL,
            subject VARCHAR(255),
            message TEXT,
            status ENUM('sent', 'failed', 'bounced') NOT NULL,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            error_message TEXT,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        
        $pdo->exec($sql);
        echo "<p class='success'>✅ Table 'notification_logs' créée avec succès</p>";
    } else {
        echo "<p class='info'>ℹ️ Table 'notification_logs' existe déjà - ignorée</p>";
    }
    echo "</div>";
    
    // Ajouter des colonnes à la table users
    echo "<div class='step'><h2>👤 Étape 7 : Ajout de colonnes à la table users</h2>";
    
    // Vérifier si les colonnes existent déjà
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $newColumns = [
        'phone' => "ADD COLUMN phone VARCHAR(20) DEFAULT NULL",
        'notification_email' => "ADD COLUMN notification_email VARCHAR(255) DEFAULT NULL",
        'notification_sms' => "ADD COLUMN notification_sms BOOLEAN DEFAULT FALSE"
    ];
    
    foreach ($newColumns as $column => $sql) {
        if (!in_array($column, $columns)) {
            $pdo->exec("ALTER TABLE users $sql");
            echo "<p class='success'>✅ Colonne '$column' ajoutée à la table users</p>";
        } else {
            echo "<p class='info'>ℹ️ Colonne '$column' existe déjà - ignorée</p>";
        }
    }
    echo "</div>";
    
    // Insérer des données de test
    echo "<div class='step'><h2>🧪 Étape 8 : Insertion de données de test</h2>";
    
    // Récupérer les utilisateurs existants
    $stmt = $pdo->query("SELECT id FROM users LIMIT 3");
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($users)) {
        // Insérer des préférences de notification pour les utilisateurs existants
        foreach ($users as $userId) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO notification_preferences 
                (user_id, email_notifications, sms_notifications, intervention_assignments, maintenance_reminders, critical_alerts) 
                VALUES (?, TRUE, FALSE, TRUE, TRUE, TRUE)");
            $stmt->execute([$userId]);
        }
        echo "<p class='success'>✅ Préférences de notification créées pour " . count($users) . " utilisateurs</p>";
        
        // Insérer quelques exemples de maintenance
        $maintenanceExamples = [
            ['Vidange moteur', '2024-01-15', '2024-01-20', 'medium'],
            ['Contrôle technique', '2024-01-20', '2024-01-25', 'high'],
            ['Révision générale', '2024-01-25', '2024-01-30', 'medium']
        ];
        
        foreach ($maintenanceExamples as $index => $maintenance) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO maintenance_schedules 
                (vehicle_id, maintenance_type, scheduled_date, due_date, priority, description, assigned_technician_id) 
                VALUES (1, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $maintenance[0],
                $maintenance[1],
                $maintenance[2],
                $maintenance[3],
                "Maintenance programmée - " . $maintenance[0],
                $users[0] ?? null
            ]);
        }
        echo "<p class='success'>✅ " . count($maintenanceExamples) . " exemples de maintenance créés</p>";
    } else {
        echo "<p class='warning'>⚠️ Aucun utilisateur trouvé - données de test non créées</p>";
    }
    echo "</div>";
    
    // Vérification finale
    echo "<div class='step'><h2>✅ Étape 9 : Vérification finale</h2>";
    
    $finalTables = ['notification_preferences', 'maintenance_schedules', 'notification_queue', 'notification_logs'];
    $allCreated = true;
    
    foreach ($finalTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "<p class='success'>✅ Table '$table' : $count enregistrements</p>";
        } else {
            echo "<p class='error'>❌ Table '$table' : ERREUR</p>";
            $allCreated = false;
        }
    }
    
    if ($allCreated) {
        echo "<div class='info'>
            <h3>🎉 Migration terminée avec succès !</h3>
            <p>Toutes les tables de notifications automatiques ont été créées.</p>
            <p><strong>Prochaines étapes :</strong></p>
            <ul>
                <li>Créer les services d'email et SMS</li>
                <li>Intégrer les notifications dans les contrôleurs</li>
                <li>Configurer les préférences utilisateur</li>
                <li>Mettre en place les rappels automatiques</li>
            </ul>
        </div>";
    } else {
        echo "<p class='error'>❌ Certaines tables n'ont pas été créées correctement</p>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>
        <h3>❌ Erreur de base de données</h3>
        <p><strong>Message :</strong> " . $e->getMessage() . "</p>
        <p><strong>Code :</strong> " . $e->getCode() . "</p>
    </div>";
} catch (Exception $e) {
    echo "<div class='error'>
        <h3>❌ Erreur générale</h3>
        <p><strong>Message :</strong> " . $e->getMessage() . "</p>
    </div>";
}

echo "</div></body></html>";
?>



