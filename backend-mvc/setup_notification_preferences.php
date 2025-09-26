<?php
/**
 * Script pour configurer les préférences de notification et tester l'envoi d'emails
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Configuration des Préférences de Notification</title>
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
        .form-group { margin: 15px 0; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; }
        .checkbox-group input[type='checkbox'] { width: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔔 Configuration des Préférences de Notification</h1>
        <p><strong>TerrainTrack - Système de Rappels Automatiques</strong></p>";

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
    
    echo "<div class='step'><h2>👤 Étape 1 : Sélection de l'utilisateur</h2>";
    
    // Récupérer tous les utilisateurs
    $users = $userRepo->findAll();
    
    if (empty($users)) {
        echo "<p class='error'>❌ Aucun utilisateur trouvé dans la base de données.</p>";
        exit;
    }
    
    echo "<form method='POST'>";
    echo "<div class='form-group'>";
    echo "<label for='user_id'>Sélectionner un utilisateur :</label>";
    echo "<select name='user_id' id='user_id' required>";
    foreach ($users as $user) {
        $selected = (isset($_POST['user_id']) && $_POST['user_id'] == $user['id']) ? 'selected' : '';
        echo "<option value='{$user['id']}' {$selected}>{$user['name']} ({$user['email']})</option>";
    }
    echo "</select>";
    echo "</div>";
    
    echo "<div class='form-group'>";
    echo "<label for='action'>Action à effectuer :</label>";
    echo "<select name='action' id='action' required>";
    echo "<option value='configure'>Configurer les préférences</option>";
    echo "<option value='test'>Tester l'envoi d'emails</option>";
    echo "<option value='view'>Voir les préférences actuelles</option>";
    echo "</select>";
    echo "</div>";
    
    echo "<button type='submit' class='btn'>🚀 Exécuter</button>";
    echo "</form>";
    
    echo "</div>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['action'])) {
        $userId = (int)$_POST['user_id'];
        $action = $_POST['action'];
        $user = $userRepo->findById($userId);
        
        if (!$user) {
            echo "<p class='error'>❌ Utilisateur non trouvé.</p>";
            exit;
        }
        
        echo "<div class='step'><h2>⚙️ Étape 2 : Traitement de la demande</h2>";
        echo "<p class='info'>ℹ️ Utilisateur sélectionné : <strong>{$user->getName()}</strong> ({$user->getEmail()})</p>";
        
        switch ($action) {
            case 'configure':
                echo "<h3>🔧 Configuration des préférences de notification</h3>";
                
                // Configurer les préférences pour recevoir tous les types de notifications
                $preferences = [
                    'user_id' => $userId,
                    'email_notifications' => true,
                    'sms_notifications' => false, // Désactivé par défaut
                    'intervention_assignments' => true,
                    'maintenance_reminders' => true,
                    'critical_alerts' => true,
                    'reminder_frequency_days' => 7
                ];
                
                $success = $preferencesRepo->save($preferences);
                
                if ($success) {
                    echo "<p class='success'>✅ Préférences de notification configurées avec succès !</p>";
                    echo "<div class='info'>";
                    echo "<h4>📧 Notifications activées :</h4>";
                    echo "<ul>";
                    echo "<li>✅ Notifications par email</li>";
                    echo "<li>✅ Assignations d'interventions</li>";
                    echo "<li>✅ Rappels d'entretien</li>";
                    echo "<li>✅ Alertes critiques</li>";
                    echo "<li>📅 Fréquence des rappels : 7 jours</li>";
                    echo "</ul>";
                    echo "</div>";
                } else {
                    echo "<p class='error'>❌ Erreur lors de la configuration des préférences.</p>";
                }
                break;
                
            case 'test':
                echo "<h3>📧 Test d'envoi d'emails de notification</h3>";
                
                // Récupérer les entretiens en retard pour le test
                $overdueMaintenance = $maintenanceRepo->findOverdueMaintenance();
                $upcomingMaintenance = $maintenanceRepo->findUpcomingMaintenance(7);
                
                echo "<p class='info'>ℹ️ Entretiens en retard : " . count($overdueMaintenance) . "</p>";
                echo "<p class='info'>ℹ️ Entretiens à venir : " . count($upcomingMaintenance) . "</p>";
                
                // Test 1: Email de rappel d'entretien
                if (!empty($upcomingMaintenance)) {
                    $maintenance = $upcomingMaintenance[0];
                    $vehicleName = $maintenance['vehicle_name'] ?? 'Véhicule inconnu';
                    
                    echo "<h4>📧 Test 1: Rappel d'entretien programmé</h4>";
                    $emailSent = $emailService->sendMaintenanceReminderNotification(
                        $userId, 
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
                
                // Test 2: Email d'alerte d'entretien en retard
                if (!empty($overdueMaintenance)) {
                    $maintenance = $overdueMaintenance[0];
                    $vehicleName = $maintenance['vehicle_name'] ?? 'Véhicule inconnu';
                    
                    echo "<h4>⚠️ Test 2: Alerte d'entretien en retard</h4>";
                    $emailSent = $emailService->sendCriticalAlertNotification(
                        $userId,
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
                
                // Test 3: Email de test général
                echo "<h4>🧪 Test 3: Email de test général</h4>";
                $testEmailSent = $emailService->sendTestEmail(
                    $user->getEmail(), 
                    "Test de notification TerrainTrack - " . date('Y-m-d H:i:s')
                );
                
                if ($testEmailSent) {
                    echo "<p class='success'>✅ Email de test envoyé avec succès !</p>";
                } else {
                    echo "<p class='error'>❌ Échec de l'envoi de l'email de test.</p>";
                }
                
                echo "<div class='info'>";
                echo "<h4>📬 Vérifiez votre boîte email :</h4>";
                echo "<p>Vous devriez recevoir les emails de test à l'adresse : <strong>{$user->getEmail()}</strong></p>";
                echo "<p>Si vous ne recevez pas les emails, vérifiez :</p>";
                echo "<ul>";
                echo "<li>Votre dossier spam/courrier indésirable</li>";
                echo "<li>La configuration SMTP du serveur</li>";
                echo "<li>Que l'adresse email est correcte</li>";
                echo "</ul>";
                echo "</div>";
                break;
                
            case 'view':
                echo "<h3>👁️ Préférences actuelles</h3>";
                
                $preferences = $preferencesRepo->findByUserId($userId);
                
                if ($preferences) {
                    echo "<div class='info'>";
                    echo "<h4>📋 Configuration actuelle :</h4>";
                    echo "<ul>";
                    echo "<li>📧 Notifications email : " . ($preferences['email_notifications'] ? '✅ Activées' : '❌ Désactivées') . "</li>";
                    echo "<li>📱 Notifications SMS : " . ($preferences['sms_notifications'] ? '✅ Activées' : '❌ Désactivées') . "</li>";
                    echo "<li>🔧 Assignations d'interventions : " . ($preferences['intervention_assignments'] ? '✅ Activées' : '❌ Désactivées') . "</li>";
                    echo "<li>🔧 Rappels d'entretien : " . ($preferences['maintenance_reminders'] ? '✅ Activés' : '❌ Désactivés') . "</li>";
                    echo "<li>⚠️ Alertes critiques : " . ($preferences['critical_alerts'] ? '✅ Activées' : '❌ Désactivées') . "</li>";
                    echo "<li>📅 Fréquence des rappels : {$preferences['reminder_frequency_days']} jours</li>";
                    echo "</ul>";
                    echo "</div>";
                } else {
                    echo "<p class='warning'>⚠️ Aucune préférence configurée pour cet utilisateur.</p>";
                }
                break;
        }
        
        echo "</div>";
    }
    
    echo "<div class='step'><h2>🚀 Étape 3 : Configuration automatique des rappels</h2>";
    
    echo "<div class='info'>";
    echo "<h3>📅 Pour activer les rappels automatiques :</h3>";
    echo "<ol>";
    echo "<li><strong>Configurez vos préférences</strong> en sélectionnant votre utilisateur et l'action 'Configurer les préférences'</li>";
    echo "<li><strong>Testez l'envoi d'emails</strong> avec l'action 'Tester l'envoi d'emails'</li>";
    echo "<li><strong>Configurez les tâches cron</strong> pour les rappels automatiques :</li>";
    echo "</ol>";
    
    echo "<div style='background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0;'>";
    echo "# Exécuter toutes les heures (recommandé)<br>";
    echo "0 * * * * " . PHP_BINARY . " " . __DIR__ . "/reminder_cron.php<br><br>";
    echo "# Ou toutes les 30 minutes<br>";
    echo "*/30 * * * * " . PHP_BINARY . " " . __DIR__ . "/reminder_cron.php";
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
    
} catch (\Exception $e) {
    echo "<div class='error'>
        <h3>❌ Erreur lors de la configuration</h3>
        <p><strong>Message :</strong> " . $e->getMessage() . "</p>
        <p><strong>Fichier :</strong> " . $e->getFile() . " (ligne " . $e->getLine() . ")</p>
    </div>";
}

echo "</div></body></html>";
?>



