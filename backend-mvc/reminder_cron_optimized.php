<?php
/**
 * Script de rappel automatique optimisé pour cron
 */

// Charger PHPMailer en premier
require_once __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration directe de la base de données MAMP
$dbHost = 'localhost';
$dbName = 'exemple';
$dbUser = 'root';
$dbPass = 'root';
$dbPort = 8889;

// Configuration SMTP Gmail
$smtpHost = 'smtp.gmail.com';
$smtpPort = 587;
$smtpUsername = 'pjorsini20@gmail.com';
$smtpPassword = 'gmqncgtfunpfnkjh';
$fromEmail = 'noreply@terraintrack.com';
$fromName = 'TerrainTrack';

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
    
    echo "[" . date('Y-m-d H:i:s') . "] 🔔 Démarrage des rappels automatiques\n";
    
    // 1. Rappels d'entretien programmés (7 jours avant)
    echo "[" . date('Y-m-d H:i:s') . "] 📅 Vérification des entretiens programmés...\n";
    
    $stmt = $db->prepare("
        SELECT ms.*, u.name as user_name, u.email as user_email 
        FROM maintenance_schedules ms
        JOIN users u ON u.id = ms.assigned_technician_id
        WHERE ms.due_date = DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND ms.status = 'scheduled'
        AND u.notification_email = 1
    ");
    $stmt->execute();
    $upcomingMaintenance = $stmt->fetchAll();
    
    echo "[" . date('Y-m-d H:i:s') . "] 📧 Entretiens à venir trouvés : " . count($upcomingMaintenance) . "\n";
    
    foreach ($upcomingMaintenance as $maintenance) {
        // Envoyer le rappel
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUsername;
        $mail->Password = $smtpPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $smtpPort;
        $mail->CharSet = 'UTF-8';
        
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($maintenance['user_email']);
        $mail->isHTML(true);
        $mail->Subject = "🔧 Rappel d'entretien programmé - " . date('Y-m-d');
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Rappel d'entretien</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #f59e0b; color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #fffbeb; padding: 30px; border-radius: 0 0 8px 8px; }
                .vehicle-info { background: #f3f4f6; padding: 20px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔧 Rappel d'entretien programmé</h1>
                </div>
                <div class='content'>
                    <p>Bonjour {$maintenance['user_name']},</p>
                    <p>Un entretien est programmé pour votre véhicule :</p>
                    <div class='vehicle-info'>
                        <h3>🚗 Véhicule ID : {$maintenance['vehicle_id']}</h3>
                        <p><strong>Type d'entretien :</strong> {$maintenance['maintenance_type']}</p>
                        <p><strong>Date prévue :</strong> {$maintenance['due_date']}</p>
                        <p><strong>Description :</strong> {$maintenance['description']}</p>
                        <p><strong>Priorité :</strong> {$maintenance['priority']}</p>
                    </div>
                    <p>Merci de vous préparer pour cette intervention.</p>
                </div>
            </div>
        </body>
        </html>";
        
        $result = $mail->send();
        
        if ($result) {
            echo "[" . date('Y-m-d H:i:s') . "] ✅ Rappel envoyé à {$maintenance['user_email']}\n";
            
            // Enregistrer dans les logs
            $logStmt = $db->prepare("
                INSERT INTO notification_logs 
                (user_id, notification_type, subject, recipient_email, status, sent_at, created_at)
                VALUES (?, 'maintenance_reminder', ?, ?, 'sent', NOW(), NOW())
            ");
            $logStmt->execute([
                $maintenance['assigned_technician_id'],
                $mail->Subject,
                $maintenance['user_email']
            ]);
        } else {
            echo "[" . date('Y-m-d H:i:s') . "] ❌ Échec envoi à {$maintenance['user_email']}\n";
        }
    }
    
    // 2. Alertes d'entretiens en retard
    echo "[" . date('Y-m-d H:i:s') . "] ⚠️ Vérification des entretiens en retard...\n";
    
    $stmt = $db->prepare("
        SELECT ms.*, u.name as user_name, u.email as user_email 
        FROM maintenance_schedules ms
        JOIN users u ON u.id = ms.assigned_technician_id
        WHERE ms.due_date < CURDATE()
        AND ms.status = 'scheduled'
        AND u.notification_email = 1
    ");
    $stmt->execute();
    $overdueMaintenance = $stmt->fetchAll();
    
    echo "[" . date('Y-m-d H:i:s') . "] ⚠️ Entretiens en retard trouvés : " . count($overdueMaintenance) . "\n";
    
    foreach ($overdueMaintenance as $maintenance) {
        // Envoyer l'alerte
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUsername;
        $mail->Password = $smtpPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $smtpPort;
        $mail->CharSet = 'UTF-8';
        
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($maintenance['user_email']);
        $mail->isHTML(true);
        $mail->Subject = "⚠️ ALERTE : Entretien en retard - " . date('Y-m-d');
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Alerte entretien en retard</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc2626; color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #fef2f2; padding: 30px; border-radius: 0 0 8px 8px; }
                .alert-info { background: #fee2e2; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #dc2626; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>⚠️ ALERTE : Entretien en retard</h1>
                </div>
                <div class='content'>
                    <p>Bonjour {$maintenance['user_name']},</p>
                    <p><strong>ATTENTION :</strong> Un entretien est en retard !</p>
                    <div class='alert-info'>
                        <h3>🚗 Véhicule ID : {$maintenance['vehicle_id']}</h3>
                        <p><strong>Type d'entretien :</strong> {$maintenance['maintenance_type']}</p>
                        <p><strong>Date d'échéance :</strong> {$maintenance['due_date']}</p>
                        <p><strong>Description :</strong> {$maintenance['description']}</p>
                        <p><strong>Priorité :</strong> {$maintenance['priority']}</p>
                    </div>
                    <p><strong>Action requise :</strong> Planifiez cet entretien en urgence !</p>
                </div>
            </div>
        </body>
        </html>";
        
        $result = $mail->send();
        
        if ($result) {
            echo "[" . date('Y-m-d H:i:s') . "] ✅ Alerte envoyée à {$maintenance['user_email']}\n";
            
            // Enregistrer dans les logs
            $logStmt = $db->prepare("
                INSERT INTO notification_logs 
                (user_id, notification_type, subject, recipient_email, status, sent_at, created_at)
                VALUES (?, 'maintenance_overdue', ?, ?, 'sent', NOW(), NOW())
            ");
            $logStmt->execute([
                $maintenance['assigned_technician_id'],
                $mail->Subject,
                $maintenance['user_email']
            ]);
        } else {
            echo "[" . date('Y-m-d H:i:s') . "] ❌ Échec envoi alerte à {$maintenance['user_email']}\n";
        }
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] ✅ Rappels automatiques terminés\n";
    
} catch (PDOException $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ❌ ERREUR DE BASE DE DONNÉES : " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ❌ ERREUR : " . $e->getMessage() . "\n";
}
?>
