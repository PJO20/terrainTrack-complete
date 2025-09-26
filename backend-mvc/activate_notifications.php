<?php
/**
 * Script pour activer automatiquement les notifications email
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Activation des Notifications Email</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #2196f3; }
        .warning { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107; }
        .step { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #6c757d; }
        h1 { color: #333; text-align: center; }
        h2 { color: #495057; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; }
        .btn { display: inline-block; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { background: #5a67d8; }
        .btn-success { background: #28a745; }
        .btn-warning { background: #ffc107; color: #333; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔔 Activation des Notifications Email</h1>
        <p><strong>TerrainTrack - Configuration Automatique</strong></p>";

try {
    // Charger l'autoloader
    require_once __DIR__ . '/vendor/autoload.php';
    
    // Charger la configuration des services
    $services = require __DIR__ . '/config/services.php';
    $container = new \App\Container\Container($services);
    
    // Récupérer les services
    $userRepo = $container->get(\App\Repository\UserRepository::class);
    $preferencesRepo = $container->get(\App\Repository\NotificationPreferencesRepository::class);
    $emailService = $container->get(\App\Service\EmailNotificationService::class);
    $maintenanceRepo = $container->get(\App\Repository\MaintenanceSchedulesRepository::class);
    
    echo "<div class='step'><h2>👤 Étape 1 : Configuration des préférences pour tous les utilisateurs</h2>";
    
    // Récupérer tous les utilisateurs
    $users = $userRepo->findAll();
    $configuredCount = 0;
    $testedCount = 0;
    
    foreach ($users as $user) {
        $userId = $user['id'];
        $userName = $user['name'] ?? 'Utilisateur';
        $userEmail = $user['email'];
        
        echo "<h3>👤 Configuration pour : {$userName} ({$userEmail})</h3>";
        
        // Configurer les préférences pour recevoir tous les types de notifications
        $preferences = [
            'user_id' => $userId,
            'email_notifications' => true,
            'sms_notifications' => false,
            'intervention_assignments' => true,
            'maintenance_reminders' => true,
            'critical_alerts' => true,
            'reminder_frequency_days' => 7
        ];
        
        $success = $preferencesRepo->save($preferences);
        
        if ($success) {
            echo "<p class='success'>✅ Préférences configurées pour {$userName}</p>";
            $configuredCount++;
            
            // Tester l'envoi d'email
            echo "<p class='info'>ℹ️ Test d'envoi d'email...</p>";
            
            $testEmailSent = $emailService->sendTestEmail(
                $userEmail, 
                "🔔 Notifications TerrainTrack Activées - " . date('Y-m-d H:i:s')
            );
            
            if ($testEmailSent) {
                echo "<p class='success'>✅ Email de test envoyé à {$userEmail}</p>";
                $testedCount++;
            } else {
                echo "<p class='error'>❌ Échec de l'envoi de l'email de test</p>";
            }
        } else {
            echo "<p class='error'>❌ Erreur lors de la configuration pour {$userName}</p>";
        }
        
        echo "<hr>";
    }
    
    echo "<p class='success'>✅ Configuration terminée : {$configuredCount} utilisateurs configurés, {$testedCount} emails de test envoyés</p>";
    echo "</div>";
    
    echo "<div class='step'><h2>📧 Étape 2 : Test des notifications d'entretien</h2>";
    
    // Récupérer les entretiens en retard et à venir
    $overdueMaintenance = $maintenanceRepo->findOverdueMaintenance();
    $upcomingMaintenance = $maintenanceRepo->findUpcomingMaintenance(7);
    
    echo "<p class='info'>ℹ️ Entretiens en retard : " . count($overdueMaintenance) . "</p>";
    echo "<p class='info'>ℹ️ Entretiens à venir : " . count($upcomingMaintenance) . "</p>";
    
    // Tester l'envoi de notifications d'entretien
    if (!empty($overdueMaintenance) || !empty($upcomingMaintenance)) {
        echo "<h3>🧪 Test des notifications d'entretien</h3>";
        
        // Prendre le premier utilisateur pour le test
        $testUser = $userRepo->findById($users[0]['id']);
        
        if ($testUser) {
            // Test 1: Rappel d'entretien programmé
            if (!empty($upcomingMaintenance)) {
                $maintenance = $upcomingMaintenance[0];
                $vehicleName = $maintenance['vehicle_name'] ?? 'Véhicule inconnu';
                
                echo "<h4>📧 Test : Rappel d'entretien programmé</h4>";
                $emailSent = $emailService->sendMaintenanceReminderNotification(
                    $testUser->getId(), 
                    $vehicleName, 
                    $maintenance['maintenance_type'], 
                    $maintenance['due_date']
                );
                
                if ($emailSent) {
                    echo "<p class='success'>✅ Email de rappel d'entretien envoyé avec succès !</p>";
                } else {
                    echo "<p class='error'>❌ Échec de l'envoi de l'email de rappel.</p>";
                }
            }
            
            // Test 2: Alerte d'entretien en retard
            if (!empty($overdueMaintenance)) {
                $maintenance = $overdueMaintenance[0];
                $vehicleName = $maintenance['vehicle_name'] ?? 'Véhicule inconnu';
                
                echo "<h4>⚠️ Test : Alerte d'entretien en retard</h4>";
                $emailSent = $emailService->sendCriticalAlertNotification(
                    $testUser->getId(),
                    'maintenance_overdue',
                    "Entretien en retard: {$maintenance['maintenance_type']}",
                    $vehicleName
                );
                
                if ($emailSent) {
                    echo "<p class='success'>✅ Email d'alerte d'entretien en retard envoyé avec succès !</p>";
                } else {
                    echo "<p class='error'>❌ Échec de l'envoi de l'email d'alerte.</p>";
                }
            }
        }
    } else {
        echo "<p class='warning'>⚠️ Aucun entretien trouvé pour les tests.</p>";
    }
    
    echo "</div>";
    
    echo "<div class='step'><h2>🚀 Étape 3 : Configuration des tâches cron</h2>";
    
    echo "<div class='info'>";
    echo "<h3>📅 Pour activer les rappels automatiques :</h3>";
    echo "<p>Ajoutez cette ligne à votre crontab pour exécuter les rappels toutes les heures :</p>";
    
    echo "<div style='background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0;'>";
    echo "0 * * * * " . PHP_BINARY . " " . __DIR__ . "/reminder_cron.php";
    echo "</div>";
    
    echo "<p><strong>Commandes pour configurer cron :</strong></p>";
    echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0;'>";
    echo "# Ouvrir le crontab<br>";
    echo "crontab -e<br><br>";
    echo "# Ajouter la ligne ci-dessus<br>";
    echo "# Sauvegarder et quitter<br><br>";
    echo "# Vérifier la configuration<br>";
    echo "crontab -l";
    echo "</div>";
    
    echo "<p><strong>Types de notifications que vous recevrez :</strong></p>";
    echo "<ul>";
    echo "<li>📧 <strong>Rappels d'entretien programmés</strong> - 7 jours avant l'échéance</li>";
    echo "<li>⚠️ <strong>Alertes pour entretiens en retard</strong> - Immédiatement</li>";
    echo "<li>📋 <strong>Messages personnalisés avec détails du véhicule</strong> - Informations complètes</li>";
    echo "<li>🔧 <strong>Assignations d'interventions</strong> - Lors de nouvelles assignations</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "</div>";
    
    echo "<div class='step'><h2>✅ Résumé de l'activation</h2>";
    
    echo "<div class='info'>";
    echo "<h3>🎉 Notifications email activées avec succès !</h3>";
    echo "<p><strong>Configuration effectuée :</strong></p>";
    echo "<ul>";
    echo "<li>✅ {$configuredCount} utilisateurs configurés</li>";
    echo "<li>✅ {$testedCount} emails de test envoyés</li>";
    echo "<li>✅ Tous les types de notifications activés</li>";
    echo "<li>✅ Rappels d'entretien programmés</li>";
    echo "<li>✅ Alertes pour entretiens en retard</li>";
    echo "<li>✅ Messages personnalisés avec détails du véhicule</li>";
    echo "</ul>";
    echo "<p><strong>Prochaines étapes :</strong></p>";
    echo "<ol>";
    echo "<li>Vérifiez vos emails (y compris le dossier spam)</li>";
    echo "<li>Configurez les tâches cron pour les rappels automatiques</li>";
    echo "<li>Testez le système avec des données réelles</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "</div>";
    
} catch (\Exception $e) {
    echo "<div class='error'>
        <h3>❌ Erreur lors de l'activation</h3>
        <p><strong>Message :</strong> " . $e->getMessage() . "</p>
        <p><strong>Fichier :</strong> " . $e->getFile() . " (ligne " . $e->getLine() . ")</p>
    </div>";
}

echo "</div></body></html>";
?>



