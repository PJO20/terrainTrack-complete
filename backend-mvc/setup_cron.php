<?php
/**
 * Script de configuration automatique des tâches cron pour TerrainTrack
 * Ce script aide à configurer les tâches cron pour les rappels automatiques
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Configuration Cron - TerrainTrack</title>
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
        .code { background: #f8f9fa; padding: 10px; border-radius: 3px; font-family: monospace; margin: 10px 0; }
        .cron-example { background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
        .command { background: #1a202c; color: #e2e8f0; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
        .btn { display: inline-block; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { background: #5a67d8; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>⏰ Configuration des Tâches Cron</h1>
        <p><strong>TerrainTrack - Système de Rappels Automatiques</strong></p>";

try {
    $scriptPath = realpath(__DIR__ . '/reminder_cron.php');
    $phpPath = PHP_BINARY;
    $logPath = __DIR__ . '/logs/reminder_cron.log';
    
    echo "<div class='step'><h2>🔍 Étape 1 : Vérification des chemins</h2>";
    
    echo "<p><strong>Script de rappels :</strong> {$scriptPath}</p>";
    echo "<p><strong>PHP :</strong> {$phpPath}</p>";
    echo "<p><strong>Logs :</strong> {$logPath}</p>";
    
    if (file_exists($scriptPath)) {
        echo "<p class='success'>✅ Script de rappels trouvé</p>";
    } else {
        echo "<p class='error'>❌ Script de rappels non trouvé</p>";
    }
    
    if (is_executable($phpPath)) {
        echo "<p class='success'>✅ PHP exécutable trouvé</p>";
    } else {
        echo "<p class='error'>❌ PHP non exécutable</p>";
    }
    
    echo "</div>";
    
    echo "<div class='step'><h2>📋 Étape 2 : Options de configuration cron</h2>";
    
    echo "<h3>Option 1 : Toutes les heures (Recommandé)</h3>";
    echo "<div class='cron-example'>0 * * * * {$phpPath} {$scriptPath}</div>";
    echo "<p class='info'>ℹ️ Exécute le script toutes les heures à la minute 0. Idéal pour la plupart des cas d'usage.</p>";
    
    echo "<h3>Option 2 : Toutes les 30 minutes</h3>";
    echo "<div class='cron-example'>*/30 * * * * {$phpPath} {$scriptPath}</div>";
    echo "<p class='info'>ℹ️ Exécute le script toutes les 30 minutes. Utile pour des rappels plus fréquents.</p>";
    
    echo "<h3>Option 3 : Tous les jours à 8h00</h3>";
    echo "<div class='cron-example'>0 8 * * * {$phpPath} {$scriptPath}</div>";
    echo "<p class='info'>ℹ️ Exécute le script une fois par jour à 8h00. Économique en ressources.</p>";
    
    echo "<h3>Option 4 : Deux fois par jour (8h et 18h)</h3>";
    echo "<div class='cron-example'>0 8,18 * * * {$phpPath} {$scriptPath}</div>";
    echo "<p class='info'>ℹ️ Exécute le script à 8h00 et 18h00. Bon compromis entre fréquence et performance.</p>";
    
    echo "</div>";
    
    echo "<div class='step'><h2>🛠️ Étape 3 : Instructions de configuration</h2>";
    
    echo "<h3>Méthode 1 : Configuration manuelle</h3>";
    echo "<div class='code'>";
    echo "1. Ouvrir le terminal<br>";
    echo "2. Exécuter : <strong>crontab -e</strong><br>";
    echo "3. Ajouter une des lignes ci-dessus<br>";
    echo "4. Sauvegarder et quitter (Ctrl+X, puis Y, puis Entrée dans nano)<br>";
    echo "5. Vérifier avec : <strong>crontab -l</strong>";
    echo "</div>";
    
    echo "<h3>Méthode 2 : Configuration automatique (Linux/Mac)</h3>";
    echo "<div class='code'>";
    echo "# Ajouter la tâche cron<br>";
    echo "echo '0 * * * * {$phpPath} {$scriptPath}' | crontab -<br><br>";
    echo "# Vérifier la configuration<br>";
    echo "crontab -l";
    echo "</div>";
    
    echo "<h3>Méthode 3 : Configuration via fichier</h3>";
    echo "<div class='code'>";
    echo "# Créer un fichier cron<br>";
    echo "echo '0 * * * * {$phpPath} {$scriptPath}' > /tmp/terraintrack_cron<br><br>";
    echo "# Installer la tâche<br>";
    echo "crontab /tmp/terraintrack_cron<br><br>";
    echo "# Nettoyer<br>";
    echo "rm /tmp/terraintrack_cron";
    echo "</div>";
    
    echo "</div>";
    
    echo "<div class='step'><h2>🧪 Étape 4 : Test de la configuration</h2>";
    
    echo "<h3>Test manuel du script</h3>";
    echo "<div class='command'>php {$scriptPath}</div>";
    
    echo "<h3>Test de la configuration cron</h3>";
    echo "<div class='code'>";
    echo "# Vérifier les tâches cron actuelles<br>";
    echo "crontab -l<br><br>";
    echo "# Tester l'exécution manuelle<br>";
    echo "php {$scriptPath}<br><br>";
    echo "# Vérifier les logs<br>";
    echo "tail -f {$logPath}";
    echo "</div>";
    
    echo "</div>";
    
    echo "<div class='step'><h2>📊 Étape 5 : Monitoring et maintenance</h2>";
    
    echo "<h3>Surveillance des logs</h3>";
    echo "<div class='code'>";
    echo "# Voir les dernières entrées<br>";
    echo "tail -n 50 {$logPath}<br><br>";
    echo "# Suivre les logs en temps réel<br>";
    echo "tail -f {$logPath}<br><br>";
    echo "# Rechercher des erreurs<br>";
    echo "grep ERROR {$logPath}";
    echo "</div>";
    
    echo "<h3>Maintenance des logs</h3>";
    echo "<div class='code'>";
    echo "# Rotation des logs (garder 7 jours)<br>";
    echo "find " . dirname($logPath) . " -name '*.log' -mtime +7 -delete<br><br>";
    echo "# Compression des anciens logs<br>";
    echo "gzip {$logPath}.$(date +%Y%m%d)";
    echo "</div>";
    
    echo "</div>";
    
    echo "<div class='step'><h2>🔧 Étape 6 : Dépannage</h2>";
    
    echo "<h3>Problèmes courants</h3>";
    echo "<div class='info'>";
    echo "<p><strong>Le script ne s'exécute pas :</strong></p>";
    echo "<ul>";
    echo "<li>Vérifier que PHP est dans le PATH</li>";
    echo "<li>Vérifier les permissions du script</li>";
    echo "<li>Vérifier la syntaxe de la tâche cron</li>";
    echo "</ul>";
    echo "<p><strong>Erreurs de permissions :</strong></p>";
    echo "<ul>";
    echo "<li>Vérifier les permissions du dossier logs/</li>";
    echo "<li>Vérifier les permissions de la base de données</li>";
    echo "</ul>";
    echo "<p><strong>Erreurs de configuration :</strong></p>";
    echo "<ul>";
    echo "<li>Vérifier la configuration SMTP</li>";
    echo "<li>Vérifier la configuration SMS</li>";
    echo "<li>Vérifier la connexion à la base de données</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>Commandes de diagnostic</h3>";
    echo "<div class='code'>";
    echo "# Vérifier le statut de cron<br>";
    echo "systemctl status cron<br><br>";
    echo "# Redémarrer cron<br>";
    echo "systemctl restart cron<br><br>";
    echo "# Vérifier les logs système<br>";
    echo "journalctl -u cron";
    echo "</div>";
    
    echo "</div>";
    
    echo "<div class='step'><h2>✅ Résumé de la configuration</h2>";
    
    echo "<div class='info'>";
    echo "<h3>🎉 Configuration cron prête !</h3>";
    echo "<p><strong>Étapes suivantes :</strong></p>";
    echo "<ol>";
    echo "<li>Choisir une fréquence d'exécution</li>";
    echo "<li>Configurer la tâche cron</li>";
    echo "<li>Tester l'exécution manuelle</li>";
    echo "<li>Surveiller les logs</li>";
    echo "<li>Configurer les paramètres SMTP/SMS en production</li>";
    echo "</ol>";
    echo "<p><strong>Recommandation :</strong> Commencer avec l'exécution toutes les heures, puis ajuster selon les besoins.</p>";
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



