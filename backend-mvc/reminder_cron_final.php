<?php
/**
 * Script de rappels automatiques - Version finale sans dépendances
 * TerrainTrack - Système de notifications
 */

// Configuration directe
$config = [
    'host' => 'localhost',
    'port' => '8889',
    'dbname' => 'exemple',
    'username' => 'root',
    'password' => EnvService::get('DB_PASS', 'root')
];

// Fonction pour envoyer un email simple
function sendSimpleEmail($to, $subject, $message) {
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/plain; charset=UTF-8',
        'From: TerrainTrack <noreply@terraintrack.com>',
        'Reply-To: noreply@terraintrack.com'
    ];
    
    return mail($to, $subject, $message, implode("\r\n", $headers));
}

// Fonction pour logger dans un fichier simple
function logToFile($message) {
    $logFile = __DIR__ . '/logs/cron_simple.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

try {
    echo "🔄 Démarrage du script de rappels automatiques...\n";
    logToFile("Démarrage du script de rappels");
    
    // Connexion à la base de données
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Connexion à la base de données réussie !\n";
    logToFile("Connexion à la base de données réussie");
    
    // 1. Vérifier les entretiens à venir (7 jours)
    echo "\n📅 Vérification des entretiens à venir...\n";
    $sql = "SELECT * FROM maintenance_schedules 
            WHERE due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
            AND status = 'scheduled'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $upcoming = $stmt->fetchAll();
    
    echo "   - Entretiens à venir : " . count($upcoming) . "\n";
    logToFile("Entretiens à venir : " . count($upcoming));
    
    // 2. Vérifier les entretiens en retard
    echo "\n⚠️ Vérification des entretiens en retard...\n";
    $sql = "SELECT * FROM maintenance_schedules 
            WHERE due_date < NOW() AND status = 'scheduled'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $overdue = $stmt->fetchAll();
    
    echo "   - Entretiens en retard : " . count($overdue) . "\n";
    logToFile("Entretiens en retard : " . count($overdue));
    
    // 3. Récupérer les utilisateurs avec notifications activées
    echo "\n👥 Récupération des utilisateurs...\n";
    $sql = "SELECT id, name, email FROM users 
            WHERE is_active = 1 AND notification_email = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "   - Utilisateurs avec notifications : " . count($users) . "\n";
    logToFile("Utilisateurs avec notifications : " . count($users));
    
    $emailsSent = 0;
    
    // 4. Envoyer les rappels d'entretien à venir
    if (!empty($upcoming) && !empty($users)) {
        echo "\n📧 Envoi des rappels d'entretien à venir...\n";
        
        foreach ($upcoming as $maintenance) {
            $vehicleName = $maintenance['vehicle_name'] ?? 'Véhicule';
            $dueDate = $maintenance['due_date'];
            $maintenanceType = $maintenance['maintenance_type'] ?? 'Entretien';
            
            foreach ($users as $user) {
                $subject = "🔔 Rappel d'entretien - " . $vehicleName;
                $message = "Bonjour " . ($user['name'] ?: 'Utilisateur') . ",\n\n";
                $message .= "Un entretien est programmé pour le véhicule : " . $vehicleName . "\n";
                $message .= "Date prévue : " . $dueDate . "\n";
                $message .= "Type : " . $maintenanceType . "\n\n";
                $message .= "Cordialement,\nL'équipe TerrainTrack";
                
                $result = sendSimpleEmail($user['email'], $subject, $message);
                
                if ($result) {
                    echo "   ✅ Rappel envoyé à " . $user['email'] . " pour " . $vehicleName . "\n";
                    logToFile("Rappel envoyé à " . $user['email'] . " pour " . $vehicleName);
                    $emailsSent++;
                } else {
                    echo "   ❌ Échec envoi à " . $user['email'] . "\n";
                    logToFile("Échec envoi à " . $user['email']);
                }
            }
        }
    }
    
    // 5. Envoyer les alertes d'entretiens en retard
    if (!empty($overdue) && !empty($users)) {
        echo "\n🚨 Envoi des alertes d'entretiens en retard...\n";
        
        foreach ($overdue as $maintenance) {
            $vehicleName = $maintenance['vehicle_name'] ?? 'Véhicule';
            $dueDate = $maintenance['due_date'];
            $maintenanceType = $maintenance['maintenance_type'] ?? 'Entretien';
            
            foreach ($users as $user) {
                $subject = "🚨 ALERTE - Entretien en retard - " . $vehicleName;
                $message = "ALERTE " . ($user['name'] ?: 'Utilisateur') . ",\n\n";
                $message .= "L'entretien du véhicule " . $vehicleName . " est EN RETARD !\n";
                $message .= "Date prévue : " . $dueDate . "\n";
                $message .= "Type : " . $maintenanceType . "\n";
                $message .= "Action requise immédiatement !\n\n";
                $message .= "Cordialement,\nL'équipe TerrainTrack";
                
                $result = sendSimpleEmail($user['email'], $subject, $message);
                
                if ($result) {
                    echo "   ✅ Alerte envoyée à " . $user['email'] . " pour " . $vehicleName . "\n";
                    logToFile("Alerte envoyée à " . $user['email'] . " pour " . $vehicleName);
                    $emailsSent++;
                } else {
                    echo "   ❌ Échec envoi à " . $user['email'] . "\n";
                    logToFile("Échec envoi à " . $user['email']);
                }
            }
        }
    }
    
    echo "\n🎉 Script de rappels terminé avec succès !\n";
    echo "📊 Résumé :\n";
    echo "   - Entretiens à venir : " . count($upcoming) . "\n";
    echo "   - Entretiens en retard : " . count($overdue) . "\n";
    echo "   - Utilisateurs notifiés : " . count($users) . "\n";
    echo "   - Emails envoyés : " . $emailsSent . "\n";
    
    logToFile("Script terminé - Emails envoyés : $emailsSent");
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    logToFile("ERREUR : " . $e->getMessage());
    exit(1);
}

