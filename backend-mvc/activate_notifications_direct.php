<?php
/**
 * Activation directe des notifications pour tous les utilisateurs
 */

// Charger PHPMailer en premier
require_once __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔔 Activation des Notifications TerrainTrack\n";
echo "==========================================\n\n";

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
$smtpPassword = 'gmqncgtfunpfnkjh'; // Votre mot de passe d'application
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
    echo "✅ Connexion à la base de données réussie !\n\n";
    
    echo "1. 👤 Configuration des préférences pour tous les utilisateurs...\n";
    
    // Récupérer tous les utilisateurs
    $stmt = $db->prepare("SELECT * FROM users WHERE is_active = 1");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "   - Utilisateurs trouvés : " . count($users) . "\n";
    
    $configuredCount = 0;
    $testedCount = 0;
    
    foreach ($users as $user) {
        $userId = $user['id'];
        $userName = $user['name'] ?? 'Utilisateur';
        $userEmail = $user['email'];
        
        echo "\n   👤 Configuration pour : {$userName} ({$userEmail})\n";
        
        // Configurer les préférences de notification
        $preferences = [
            'user_id' => $userId,
            'email_notifications' => 1,  // 1 pour true
            'sms_notifications' => 0,    // 0 pour false
            'intervention_assignments' => 1,  // 1 pour true
            'maintenance_reminders' => 1,     // 1 pour true
            'critical_alerts' => 1,          // 1 pour true
            'reminder_frequency_days' => 7
        ];
        
        // Sauvegarder les préférences
        $stmt = $db->prepare("
            INSERT INTO notification_preferences 
            (user_id, email_notifications, sms_notifications, intervention_assignments, 
             maintenance_reminders, critical_alerts, reminder_frequency_days, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
            email_notifications = VALUES(email_notifications),
            sms_notifications = VALUES(sms_notifications),
            intervention_assignments = VALUES(intervention_assignments),
            maintenance_reminders = VALUES(maintenance_reminders),
            critical_alerts = VALUES(critical_alerts),
            reminder_frequency_days = VALUES(reminder_frequency_days),
            updated_at = NOW()
        ");
        
        $success = $stmt->execute([
            $preferences['user_id'],
            $preferences['email_notifications'],
            $preferences['sms_notifications'],
            $preferences['intervention_assignments'],
            $preferences['maintenance_reminders'],
            $preferences['critical_alerts'],
            $preferences['reminder_frequency_days']
        ]);
        
        if ($success) {
            echo "   ✅ Préférences configurées pour {$userName}\n";
            $configuredCount++;
            
            // Tester l'envoi d'email
            echo "   📧 Test d'envoi d'email...\n";
            
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
            $mail->addAddress($userEmail);
            $mail->isHTML(true);
            $mail->Subject = "🔔 Notifications TerrainTrack Activées - " . date('Y-m-d H:i:s');
            $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <title>Notifications Activées</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #10b981; color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                    .content { background: #f0fdf4; padding: 30px; border-radius: 0 0 8px 8px; }
                    .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>✅ Notifications Activées !</h1>
                    </div>
                    <div class='content'>
                        <p>Bonjour {$userName},</p>
                        <p>Les notifications email de TerrainTrack ont été activées pour votre compte.</p>
                        <p><strong>Vous recevrez maintenant :</strong></p>
                        <ul>
                            <li>📧 Rappels d'entretien programmés</li>
                            <li>⚠️ Alertes pour entretiens en retard</li>
                            <li>🔧 Messages personnalisés avec détails du véhicule</li>
                            <li>📋 Assignations d'interventions</li>
                        </ul>
                        <p>Merci d'utiliser TerrainTrack !</p>
                    </div>
                    <div class='footer'>
                        <p><strong>TerrainTrack</strong> - Système de notifications</p>
                    </div>
                </div>
            </body>
            </html>";
            
            $testEmailSent = $mail->send();
            
            if ($testEmailSent) {
                echo "   ✅ Email de test envoyé à {$userEmail}\n";
                $testedCount++;
            } else {
                echo "   ❌ Échec de l'envoi de l'email de test\n";
            }
        } else {
            echo "   ❌ Erreur lors de la configuration pour {$userName}\n";
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ Configuration terminée : {$configuredCount} utilisateurs configurés, {$testedCount} emails de test envoyés\n";
    echo str_repeat("=", 50) . "\n";
    
    echo "\n2. 🔧 Test des notifications d'entretien...\n";
    
    // Créer des entretiens de test
    echo "   📅 Création d'entretiens de test...\n";
    
    // Entretien à venir
    $upcomingMaintenance = [
        'vehicle_id' => 1,
        'maintenance_type' => 'Révision générale',
        'due_date' => date('Y-m-d', strtotime('+5 days')),
        'description' => 'Révision complète : vidange, filtres, freins',
        'priority' => 'medium',
        'assigned_technician_id' => 1
    ];
    
    $stmt = $db->prepare("
        INSERT INTO maintenance_schedules 
        (vehicle_id, maintenance_type, scheduled_date, due_date, description, priority, assigned_technician_id, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $upcomingMaintenance['vehicle_id'],
        $upcomingMaintenance['maintenance_type'],
        $upcomingMaintenance['due_date'], // scheduled_date = due_date
        $upcomingMaintenance['due_date'],
        $upcomingMaintenance['description'],
        $upcomingMaintenance['priority'],
        $upcomingMaintenance['assigned_technician_id']
    ]);
    
    echo "   ✅ Entretien à venir créé : {$upcomingMaintenance['maintenance_type']} pour le {$upcomingMaintenance['due_date']}\n";
    
    // Entretien en retard
    $overdueMaintenance = [
        'vehicle_id' => 2,
        'maintenance_type' => 'Contrôle technique',
        'due_date' => date('Y-m-d', strtotime('-3 days')),
        'description' => 'Contrôle technique expiré depuis 3 jours',
        'priority' => 'high',
        'assigned_technician_id' => 1
    ];
    
    $stmt = $db->prepare("
        INSERT INTO maintenance_schedules 
        (vehicle_id, maintenance_type, scheduled_date, due_date, description, priority, assigned_technician_id, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $overdueMaintenance['vehicle_id'],
        $overdueMaintenance['maintenance_type'],
        $overdueMaintenance['due_date'], // scheduled_date = due_date
        $overdueMaintenance['due_date'],
        $overdueMaintenance['description'],
        $overdueMaintenance['priority'],
        $overdueMaintenance['assigned_technician_id']
    ]);
    
    echo "   ✅ Entretien en retard créé : {$overdueMaintenance['maintenance_type']} (retard de 3 jours)\n";
    
    echo "\n3. 📧 Test des notifications d'entretien...\n";
    
    // Prendre le premier utilisateur pour le test
    if (!empty($users)) {
        $testUser = $users[0];
        $testUserEmail = $testUser['email'];
        
        echo "   👤 Test avec l'utilisateur : {$testUser['name']} ({$testUserEmail})\n";
        
        // Test 1: Rappel d'entretien programmé
        echo "   📧 Test : Rappel d'entretien programmé\n";
        
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
        $mail->addAddress($testUserEmail);
        $mail->isHTML(true);
        $mail->Subject = "🔧 Rappel d'entretien programmé - " . date('Y-m-d H:i:s');
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
                    <p>Bonjour {$testUser['name']},</p>
                    <p>Un entretien est programmé pour votre véhicule :</p>
                    <div class='vehicle-info'>
                        <h3>🚗 Véhicule : Renault Master</h3>
                        <p><strong>Type d'entretien :</strong> {$upcomingMaintenance['maintenance_type']}</p>
                        <p><strong>Date prévue :</strong> {$upcomingMaintenance['due_date']}</p>
                        <p><strong>Description :</strong> {$upcomingMaintenance['description']}</p>
                        <p><strong>Technicien assigné :</strong> Pierre Jorsini</p>
                    </div>
                    <p>Merci de vous préparer pour cette intervention.</p>
                </div>
            </div>
        </body>
        </html>";
        
        $reminderSent = $mail->send();
        
        if ($reminderSent) {
            echo "   ✅ Rappel d'entretien envoyé avec succès !\n";
        } else {
            echo "   ❌ Échec de l'envoi du rappel d'entretien\n";
        }
        
        // Test 2: Alerte d'entretien en retard
        echo "   ⚠️ Test : Alerte d'entretien en retard\n";
        
        $mail2 = new PHPMailer(true);
        $mail2->isSMTP();
        $mail2->Host = $smtpHost;
        $mail2->SMTPAuth = true;
        $mail2->Username = $smtpUsername;
        $mail2->Password = $smtpPassword;
        $mail2->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail2->Port = $smtpPort;
        $mail2->CharSet = 'UTF-8';
        
        $mail2->setFrom($fromEmail, $fromName);
        $mail2->addAddress($testUserEmail);
        $mail2->isHTML(true);
        $mail2->Subject = "⚠️ ALERTE : Entretien en retard - " . date('Y-m-d H:i:s');
        $mail2->Body = "
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
                    <p>Bonjour {$testUser['name']},</p>
                    <p><strong>ATTENTION :</strong> Un entretien est en retard !</p>
                    <div class='alert-info'>
                        <h3>🚗 Véhicule : Peugeot Boxer</h3>
                        <p><strong>Type d'entretien :</strong> {$overdueMaintenance['maintenance_type']}</p>
                        <p><strong>Date d'échéance :</strong> {$overdueMaintenance['due_date']} (retard de 3 jours)</p>
                        <p><strong>Description :</strong> {$overdueMaintenance['description']}</p>
                        <p><strong>Priorité :</strong> HAUTE</p>
                    </div>
                    <p><strong>Action requise :</strong> Planifiez cet entretien en urgence !</p>
                </div>
            </div>
        </body>
        </html>";
        
        $alertSent = $mail2->send();
        
        if ($alertSent) {
            echo "   ✅ Alerte d'entretien en retard envoyée avec succès !\n";
        } else {
            echo "   ❌ Échec de l'envoi de l'alerte d'entretien en retard\n";
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🎉 ACTIVATION TERMINÉE !\n";
    echo "📧 Vérifiez les emails reçus par tous les utilisateurs\n";
    echo "📁 N'oubliez pas de vérifier le dossier SPAM\n";
    echo str_repeat("=", 60) . "\n";
    
    echo "\n📋 Résumé de l'activation :\n";
    echo "   ✅ {$configuredCount} utilisateurs configurés\n";
    echo "   ✅ {$testedCount} emails de test envoyés\n";
    echo "   ✅ Tous les types de notifications activés\n";
    echo "   ✅ Rappels d'entretien programmés\n";
    echo "   ✅ Alertes pour entretiens en retard\n";
    echo "   ✅ Messages personnalisés avec détails du véhicule\n";
    
    echo "\n🚀 Prochaines étapes :\n";
    echo "   1. Vérifiez les emails reçus\n";
    echo "   2. Configurez les tâches cron pour les rappels automatiques\n";
    echo "   3. Testez le système avec des données réelles\n";
    
} catch (PDOException $e) {
    echo "❌ ERREUR DE BASE DE DONNÉES : " . $e->getMessage() . "\n";
    echo "Vérifiez que MAMP est démarré et que la base 'exemple' existe.\n";
} catch (Exception $e) {
    echo "❌ ERREUR : " . $e->getMessage() . "\n";
}
?>
