<?php
/**
 * Script de monitoring des rappels automatiques
 * Affiche les statistiques et l'état du système de rappels
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Monitoring des Rappels - TerrainTrack</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #2196f3; }
        .step { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #6c757d; }
        h1 { color: #333; text-align: center; }
        h2 { color: #495057; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .stat-value { font-size: 2.5rem; font-weight: bold; margin-bottom: 10px; }
        .stat-label { color: #666; font-size: 0.9rem; }
        .stat-success { color: #28a745; }
        .stat-warning { color: #ffc107; }
        .stat-error { color: #dc3545; }
        .log-entry { background: #f8f9fa; padding: 10px; margin: 5px 0; border-radius: 5px; font-family: monospace; font-size: 0.9rem; }
        .log-error { background: #f8d7da; color: #721c24; }
        .log-success { background: #d4edda; color: #155724; }
        .log-info { background: #d1ecf1; color: #0c5460; }
        .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .table tr:hover { background: #f8f9fa; }
        .status-indicator { display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin-right: 8px; }
        .status-active { background: #28a745; }
        .status-inactive { background: #dc3545; }
        .status-warning { background: #ffc107; }
        .refresh-btn { background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 10px 0; }
        .refresh-btn:hover { background: #5a67d8; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>📊 Monitoring des Rappels Automatiques</h1>
        <p><strong>TerrainTrack - Système de Rappels</strong></p>
        <button class='refresh-btn' onclick='location.reload()'>🔄 Actualiser</button>";

try {
    // Charger l'autoloader
    require_once __DIR__ . '/vendor/autoload.php';
    
    // Charger la configuration des services
    $services = require __DIR__ . '/config/services.php';
    $container = new \App\Container\Container($services);
    
    // Récupérer les services
    $maintenanceRepo = $container->get(\App\Repository\MaintenanceSchedulesRepository::class);
    $preferencesRepo = $container->get(\App\Repository\NotificationPreferencesRepository::class);
    $userRepo = $container->get(\App\Repository\UserRepository::class);
    $logsRepo = $container->get(\App\Repository\NotificationLogsRepository::class);
    
    echo "<div class='step'><h2>📈 Statistiques générales</h2>";
    
    // Statistiques des entretiens
    $upcomingMaintenance = $maintenanceRepo->findUpcomingMaintenance(7);
    $overdueMaintenance = $maintenanceRepo->findOverdueMaintenance();
    $allMaintenance = $maintenanceRepo->findAll();
    
    // Statistiques des utilisateurs
    $allUsers = $userRepo->findAll();
    $usersWithPreferences = 0;
    $usersWithEmailEnabled = 0;
    $usersWithSmsEnabled = 0;
    
    foreach ($allUsers as $user) {
        $preferences = $preferencesRepo->findByUserId($user['id']);
        if ($preferences) {
            $usersWithPreferences++;
            if ($preferences['email_notifications']) $usersWithEmailEnabled++;
            if ($preferences['sms_notifications']) $usersWithSmsEnabled++;
        }
    }
    
    // Statistiques des logs
    $recentLogs = $logsRepo->findAll();
    $emailStats = $logsRepo->getStatsByType('email');
    $smsStats = $logsRepo->getStatsByType('sms');
    
    echo "<div class='stats-grid'>";
    
    echo "<div class='stat-card'>";
    echo "<div class='stat-value stat-success'>" . count($upcomingMaintenance) . "</div>";
    echo "<div class='stat-label'>Entretiens à venir (7j)</div>";
    echo "</div>";
    
    echo "<div class='stat-card'>";
    echo "<div class='stat-value stat-warning'>" . count($overdueMaintenance) . "</div>";
    echo "<div class='stat-label'>Entretiens en retard</div>";
    echo "</div>";
    
    echo "<div class='stat-card'>";
    echo "<div class='stat-value stat-success'>" . count($allMaintenance) . "</div>";
    echo "<div class='stat-label'>Total entretiens</div>";
    echo "</div>";
    
    echo "<div class='stat-card'>";
    echo "<div class='stat-value stat-success'>{$usersWithPreferences}</div>";
    echo "<div class='stat-label'>Utilisateurs configurés</div>";
    echo "</div>";
    
    echo "<div class='stat-card'>";
    echo "<div class='stat-value stat-success'>{$usersWithEmailEnabled}</div>";
    echo "<div class='stat-label'>Emails activés</div>";
    echo "</div>";
    
    echo "<div class='stat-card'>";
    echo "<div class='stat-value stat-success'>{$usersWithSmsEnabled}</div>";
    echo "<div class='stat-label'>SMS activés</div>";
    echo "</div>";
    
    echo "<div class='stat-card'>";
    echo "<div class='stat-value stat-success'>" . count($recentLogs) . "</div>";
    echo "<div class='stat-label'>Notifications envoyées</div>";
    echo "</div>";
    
    echo "<div class='stat-card'>";
    echo "<div class='stat-value stat-success'>" . ($emailStats['success_rate'] ?? 0) . "%</div>";
    echo "<div class='stat-label'>Taux de succès email</div>";
    echo "</div>";
    
    echo "</div>";
    
    echo "</div>";
    
    // Détails des entretiens
    echo "<div class='step'><h2>🔧 Détails des entretiens</h2>";
    
    if (!empty($upcomingMaintenance)) {
        echo "<h3>Entretiens à venir (7 jours)</h3>";
        echo "<table class='table'>";
        echo "<thead><tr><th>ID</th><th>Véhicule</th><th>Type</th><th>Date prévue</th><th>Échéance</th><th>Priorité</th><th>Statut</th></tr></thead>";
        echo "<tbody>";
        
        foreach ($upcomingMaintenance as $maintenance) {
            $daysUntilDue = $this->calculateDaysUntilDue($maintenance['due_date']);
            $priorityClass = $daysUntilDue <= 1 ? 'stat-error' : ($daysUntilDue <= 3 ? 'stat-warning' : 'stat-success');
            
            echo "<tr>";
            echo "<td>{$maintenance['id']}</td>";
            echo "<td>{$maintenance['vehicle_id']}</td>";
            echo "<td>{$maintenance['maintenance_type']}</td>";
            echo "<td>{$maintenance['scheduled_date']}</td>";
            echo "<td>{$maintenance['due_date']}</td>";
            echo "<td><span class='{$priorityClass}'>{$maintenance['priority']}</span></td>";
            echo "<td><span class='status-indicator status-active'></span>Actif</td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
    }
    
    if (!empty($overdueMaintenance)) {
        echo "<h3>Entretiens en retard</h3>";
        echo "<table class='table'>";
        echo "<thead><tr><th>ID</th><th>Véhicule</th><th>Type</th><th>Date prévue</th><th>Échéance</th><th>Priorité</th><th>Statut</th></tr></thead>";
        echo "<tbody>";
        
        foreach ($overdueMaintenance as $maintenance) {
            echo "<tr>";
            echo "<td>{$maintenance['id']}</td>";
            echo "<td>{$maintenance['vehicle_id']}</td>";
            echo "<td>{$maintenance['maintenance_type']}</td>";
            echo "<td>{$maintenance['scheduled_date']}</td>";
            echo "<td>{$maintenance['due_date']}</td>";
            echo "<td><span class='stat-error'>{$maintenance['priority']}</span></td>";
            echo "<td><span class='status-indicator status-error'></span>En retard</td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
    }
    
    echo "</div>";
    
    // Logs récents
    echo "<div class='step'><h2>📋 Logs récents</h2>";
    
    $recentLogs = array_slice($recentLogs, 0, 20); // Limiter à 20 entrées
    
    if (!empty($recentLogs)) {
        echo "<h3>Dernières notifications (20 dernières)</h3>";
        
        foreach ($recentLogs as $log) {
            $logClass = 'log-info';
            if ($log['status'] === 'failed') $logClass = 'log-error';
            if ($log['status'] === 'sent') $logClass = 'log-success';
            
            echo "<div class='log-entry {$logClass}'>";
            echo "<strong>[{$log['sent_at']}]</strong> ";
            echo "<strong>{$log['notification_type']}</strong> - ";
            echo "{$log['subject']} - ";
            echo "Statut: <strong>{$log['status']}</strong>";
            if (!empty($log['error_message'])) {
                echo " - Erreur: {$log['error_message']}";
            }
            echo "</div>";
        }
    } else {
        echo "<p class='info'>ℹ️ Aucun log de notification trouvé.</p>";
    }
    
    echo "</div>";
    
    // État du système
    echo "<div class='step'><h2>⚙️ État du système</h2>";
    
    $logFile = __DIR__ . '/logs/reminder_cron.log';
    $lastRun = 'Jamais';
    $cronStatus = 'Inconnu';
    
    if (file_exists($logFile)) {
        $lastModified = filemtime($logFile);
        $lastRun = date('Y-m-d H:i:s', $lastModified);
        
        // Vérifier si le script s'est exécuté récemment (dans la dernière heure)
        $timeDiff = time() - $lastModified;
        if ($timeDiff < 3600) {
            $cronStatus = 'Actif';
        } else {
            $cronStatus = 'Inactif';
        }
    }
    
    echo "<div class='info'>";
    echo "<h3>État des rappels automatiques</h3>";
    echo "<p><strong>Dernière exécution :</strong> {$lastRun}</p>";
    echo "<p><strong>Statut :</strong> <span class='status-indicator " . ($cronStatus === 'Actif' ? 'status-active' : 'status-inactive') . "'></span>{$cronStatus}</p>";
    echo "<p><strong>Fichier de log :</strong> {$logFile}</p>";
    echo "</div>";
    
    // Recommandations
    echo "<div class='step'><h2>💡 Recommandations</h2>";
    
    echo "<div class='info'>";
    echo "<h3>Actions recommandées</h3>";
    
    if (count($overdueMaintenance) > 0) {
        echo "<p class='warning'>⚠️ <strong>Attention :</strong> " . count($overdueMaintenance) . " entretien(s) en retard nécessitent une action immédiate.</p>";
    }
    
    if ($cronStatus === 'Inactif') {
        echo "<p class='error'>❌ <strong>Problème :</strong> Le système de rappels automatiques semble inactif. Vérifiez la configuration cron.</p>";
    }
    
    if ($usersWithSmsEnabled === 0) {
        echo "<p class='warning'>⚠️ <strong>Information :</strong> Aucun utilisateur n'a activé les notifications SMS.</p>";
    }
    
    if (($emailStats['success_rate'] ?? 0) < 80) {
        echo "<p class='warning'>⚠️ <strong>Attention :</strong> Le taux de succès des emails est faible (" . ($emailStats['success_rate'] ?? 0) . "%). Vérifiez la configuration SMTP.</p>";
    }
    
    echo "<p class='success'>✅ <strong>Bon :</strong> " . count($upcomingMaintenance) . " entretien(s) programmé(s) pour les 7 prochains jours.</p>";
    echo "</div>";
    
    echo "</div>";
    
    // Actions rapides
    echo "<div class='step'><h2>🚀 Actions rapides</h2>";
    
    echo "<div class='info'>";
    echo "<h3>Commandes utiles</h3>";
    echo "<div class='code'>";
    echo "# Exécuter le script de rappels manuellement<br>";
    echo "php " . __DIR__ . "/reminder_cron.php<br><br>";
    echo "# Voir les logs en temps réel<br>";
    echo "tail -f " . __DIR__ . "/logs/reminder_cron.log<br><br>";
    echo "# Vérifier la configuration cron<br>";
    echo "crontab -l<br><br>";
    echo "# Tester l'envoi d'email<br>";
    echo "php " . __DIR__ . "/test_reminder_cron.php";
    echo "</div>";
    echo "</div>";
    
    echo "</div>";
    
} catch (\Exception $e) {
    echo "<div class='error'>
        <h3>❌ Erreur lors du monitoring</h3>
        <p><strong>Message :</strong> " . $e->getMessage() . "</p>
        <p><strong>Fichier :</strong> " . $e->getFile() . " (ligne " . $e->getLine() . ")</p>
    </div>";
}

/**
 * Calcule le nombre de jours jusqu'à l'échéance
 */
function calculateDaysUntilDue($dueDate)
{
    $due = new \DateTime($dueDate);
    $now = new \DateTime();
    $diff = $now->diff($due);
    return $diff->days * ($due > $now ? 1 : -1);
}

echo "</div></body></html>";
?>
