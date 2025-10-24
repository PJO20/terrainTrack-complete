<?php
/**
 * Script de rappels automatiques avec fréquence dynamique
 * TerrainTrack - Système de notifications personnalisées
 */

// Configuration directe
$config = [
    'host' => 'localhost',
    'port' => '8889',
    'dbname' => 'exemple',
    'username' => 'root',
    'password' => 'root',
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'pjorsini20@gmail.com',
    'smtp_password' => 'kfzz kpvg kzho qzkg'
];

// Fonction pour envoyer un email via SMTP
function sendEmailSMTP($to, $subject, $message, $config) {
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: TerrainTrack <' . $config['smtp_username'] . '>',
        'Reply-To: ' . $config['smtp_username']
    ];
    
    // Pour simplifier, on utilise la fonction mail() avec les headers appropriés
    // En production, il faudrait utiliser PHPMailer ou SwiftMailer
    return mail($to, $subject, $message, implode("\r\n", $headers));
}

// Fonction pour logger dans un fichier
function logToFile($message) {
    $logFile = __DIR__ . '/logs/cron_dynamic.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

// Fonction pour générer un email HTML
function generateEmailHTML($type, $maintenance, $user, $daysUntilDue = null) {
    $vehicleName = $maintenance['vehicle_name'] ?? 'Véhicule #' . $maintenance['vehicle_id'];
    $maintenanceType = $maintenance['maintenance_type'];
    $dueDate = date('d/m/Y', strtotime($maintenance['due_date']));
    
    if ($type === 'upcoming') {
        $subject = "🔧 Rappel d'entretien - $vehicleName";
        $title = "Entretien à venir";
        $message = "L'entretien <strong>$maintenanceType</strong> pour $vehicleName est programmé pour le <strong>$dueDate</strong>";
        if ($daysUntilDue !== null) {
            $message .= " (dans $daysUntilDue jour" . ($daysUntilDue > 1 ? 's' : '') . ")";
        }
        $color = "#4CAF50";
    } else {
        $subject = "🚨 Entretien en retard - $vehicleName";
        $title = "Entretien en retard";
        $daysOverdue = abs((strtotime($maintenance['due_date']) - time()) / 86400);
        $daysOverdue = floor($daysOverdue);
        $message = "L'entretien <strong>$maintenanceType</strong> pour $vehicleName était programmé pour le <strong>$dueDate</strong> (retard de $daysOverdue jour" . ($daysOverdue > 1 ? 's' : '') . ")";
        $color = "#F44336";
    }
    
    $html = "
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>$subject</title>
    </head>
    <body style='font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5;'>
        <div style='max-width: 600px; margin: 0 auto; background-color: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
            <div style='background-color: $color; color: white; padding: 20px; text-align: center;'>
                <h1 style='margin: 0; font-size: 24px;'>🚗 TerrainTrack</h1>
                <p style='margin: 10px 0 0 0; font-size: 16px;'>$title</p>
            </div>
            
            <div style='padding: 30px;'>
                <h2 style='color: #333; margin-top: 0;'>Bonjour " . ($user['name'] ?? 'Utilisateur') . ",</h2>
                
                <p style='color: #666; font-size: 16px; line-height: 1.6;'>$message</p>
                
                <div style='background-color: #f8f9fa; padding: 20px; border-radius: 6px; margin: 20px 0;'>
                    <h3 style='color: #333; margin-top: 0;'>Détails de l'entretien :</h3>
                    <ul style='color: #666; margin: 0; padding-left: 20px;'>
                        <li><strong>Véhicule :</strong> $vehicleName</li>
                        <li><strong>Type d'entretien :</strong> $maintenanceType</li>
                        <li><strong>Date prévue :</strong> $dueDate</li>
                        <li><strong>Priorité :</strong> " . ucfirst($maintenance['priority']) . "</li>
                    </ul>
                </div>
                
                <p style='color: #666; font-size: 14px; margin-top: 30px;'>
                    Cet email a été envoyé automatiquement selon vos préférences de notification.
                    <br>Pour modifier vos préférences, connectez-vous à TerrainTrack.
                </p>
            </div>
            
            <div style='background-color: #f8f9fa; padding: 15px; text-align: center; border-top: 1px solid #eee;'>
                <p style='margin: 0; color: #999; font-size: 12px;'>© 2025 TerrainTrack - Système de gestion de maintenance</p>
            </div>
        </div>
    </body>
    </html>";
    
    return [$subject, $html];
}

try {
    echo "🔄 Démarrage du script de rappels dynamiques...\n";
    logToFile("Démarrage du script de rappels dynamiques");
    
    // Connexion à la base de données
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Connexion à la base de données réussie !\n";
    logToFile("Connexion à la base de données réussie");
    
    // Récupérer tous les utilisateurs avec leurs préférences
    echo "\n👥 Récupération des utilisateurs et préférences...\n";
    $sql = "SELECT u.id, u.name, u.email, u.notification_email,
                   COALESCE(np.email_notifications, 1) as email_notifications,
                   COALESCE(np.maintenance_reminders, 1) as maintenance_reminders,
                   COALESCE(np.reminder_frequency_days, 7) as reminder_frequency_days
            FROM users u
            LEFT JOIN notification_preferences np ON u.id = np.user_id
            WHERE u.is_active = 1 
            AND (u.notification_email IS NOT NULL OR u.email IS NOT NULL)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "   - Utilisateurs avec notifications : " . count($users) . "\n";
    logToFile("Utilisateurs avec notifications : " . count($users));
    
    $totalEmailsSent = 0;
    
    // Pour chaque utilisateur, vérifier les entretiens selon sa fréquence
    foreach ($users as $user) {
        $userId = $user['id'];
        $userName = $user['name'] ?? 'Utilisateur';
        $userEmail = $user['notification_email'] ?? $user['email'];
        $reminderFrequency = (int)$user['reminder_frequency_days'];
        
        // Vérifier si l'utilisateur veut recevoir des rappels d'entretien
        if (!$user['email_notifications'] || !$user['maintenance_reminders']) {
            continue;
        }
        
        echo "\n👤 Traitement de $userName (fréquence: $reminderFrequency jours)...\n";
        logToFile("Traitement de $userName (ID: $userId, fréquence: $reminderFrequency jours)");
        
        // 1. Entretiens à venir selon la fréquence personnalisée
        $sql = "SELECT * FROM maintenance_schedules 
                WHERE due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? DAY)
                AND status = 'scheduled'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$reminderFrequency]);
        $upcoming = $stmt->fetchAll();
        
        foreach ($upcoming as $maintenance) {
            $daysUntilDue = ceil((strtotime($maintenance['due_date']) - time()) / 86400);
            
            // Vérifier si on doit envoyer un rappel (pas plus d'un par jour)
            $lastSentSql = "SELECT COUNT(*) as count FROM notification_logs 
                           WHERE user_id = ? 
                           AND notification_type = 'maintenance_reminder'
                           AND subject LIKE ?
                           AND sent_at > DATE_SUB(NOW(), INTERVAL 1 DAY)";
            $lastSentStmt = $pdo->prepare($lastSentSql);
            $vehicleSearchPattern = '%' . ($maintenance['vehicle_name'] ?? 'Véhicule #' . $maintenance['vehicle_id']) . '%';
            $lastSentStmt->execute([$userId, $vehicleSearchPattern]);
            $lastSent = $lastSentStmt->fetch();
            
            if ($lastSent['count'] == 0) {
                list($subject, $htmlContent) = generateEmailHTML('upcoming', $maintenance, $user, $daysUntilDue);
                
                if (sendEmailSMTP($userEmail, $subject, $htmlContent, $config)) {
                    echo "   ✅ Rappel envoyé pour " . ($maintenance['vehicle_name'] ?? 'Véhicule #' . $maintenance['vehicle_id']) . "\n";
                    
                    // Logger dans notification_logs
                    $logSql = "INSERT INTO notification_logs (user_id, notification_type, subject, message, status, sent_at)
                              VALUES (?, 'maintenance_reminder', ?, ?, 'sent', NOW())";
                    $logStmt = $pdo->prepare($logSql);
                    $logMessage = "Rappel d'entretien envoyé à $userEmail: " . ($maintenance['vehicle_name'] ?? 'Véhicule #' . $maintenance['vehicle_id']) . 
                                 " - " . $maintenance['maintenance_type'] . " (dans $daysUntilDue jour(s))";
                    $logStmt->execute([
                        $userId,
                        $subject,
                        $logMessage
                    ]);
                    
                    $totalEmailsSent++;
                    logToFile("Rappel envoyé à $userEmail pour entretien ID " . $maintenance['id']);
                } else {
                    echo "   ❌ Échec envoi rappel pour " . ($maintenance['vehicle_name'] ?? 'Véhicule #' . $maintenance['vehicle_id']) . "\n";
                    logToFile("Échec envoi rappel à $userEmail pour entretien ID " . $maintenance['id']);
                }
            }
        }
        
        // 2. Entretiens en retard (toujours vérifiés, peu importe la fréquence)
        $sql = "SELECT * FROM maintenance_schedules 
                WHERE due_date < NOW() AND status = 'scheduled'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $overdue = $stmt->fetchAll();
        
        foreach ($overdue as $maintenance) {
            // Vérifier si on doit envoyer une alerte (pas plus d'une par jour)
            $lastSentSql = "SELECT COUNT(*) as count FROM notification_logs 
                           WHERE user_id = ? 
                           AND notification_type = 'maintenance_alert'
                           AND subject LIKE ?
                           AND sent_at > DATE_SUB(NOW(), INTERVAL 1 DAY)";
            $lastSentStmt = $pdo->prepare($lastSentSql);
            $vehicleSearchPattern = '%' . ($maintenance['vehicle_name'] ?? 'Véhicule #' . $maintenance['vehicle_id']) . '%';
            $lastSentStmt->execute([$userId, $vehicleSearchPattern]);
            $lastSent = $lastSentStmt->fetch();
            
            if ($lastSent['count'] == 0) {
                list($subject, $htmlContent) = generateEmailHTML('overdue', $maintenance, $user);
                
                if (sendEmailSMTP($userEmail, $subject, $htmlContent, $config)) {
                    echo "   🚨 Alerte retard envoyée pour " . ($maintenance['vehicle_name'] ?? 'Véhicule #' . $maintenance['vehicle_id']) . "\n";
                    
                    // Logger dans notification_logs
                    $logSql = "INSERT INTO notification_logs (user_id, notification_type, subject, message, status, sent_at)
                              VALUES (?, 'maintenance_alert', ?, ?, 'sent', NOW())";
                    $logStmt = $pdo->prepare($logSql);
                    $daysOverdue = floor((time() - strtotime($maintenance['due_date'])) / 86400);
                    $logMessage = "Alerte entretien en retard envoyée à $userEmail: " . ($maintenance['vehicle_name'] ?? 'Véhicule #' . $maintenance['vehicle_id']) . 
                                 " - " . $maintenance['maintenance_type'] . " (retard de $daysOverdue jour(s))";
                    $logStmt->execute([
                        $userId,
                        $subject,
                        $logMessage
                    ]);
                    
                    $totalEmailsSent++;
                    logToFile("Alerte retard envoyée à $userEmail pour entretien ID " . $maintenance['id']);
                } else {
                    echo "   ❌ Échec envoi alerte pour " . ($maintenance['vehicle_name'] ?? 'Véhicule #' . $maintenance['vehicle_id']) . "\n";
                    logToFile("Échec envoi alerte à $userEmail pour entretien ID " . $maintenance['id']);
                }
            }
        }
    }
    
    echo "\n🎉 Script terminé avec succès !\n";
    echo "📊 Résumé :\n";
    echo "   - Utilisateurs traités : " . count($users) . "\n";
    echo "   - Emails envoyés : $totalEmailsSent\n";
    
    logToFile("Script terminé - Emails envoyés : $totalEmailsSent");
    
} catch (Exception $e) {
    $errorMsg = "Erreur: " . $e->getMessage() . " dans " . $e->getFile() . ":" . $e->getLine();
    echo "❌ $errorMsg\n";
    logToFile($errorMsg);
}
?>
