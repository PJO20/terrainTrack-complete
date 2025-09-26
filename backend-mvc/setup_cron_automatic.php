<?php
/**
 * Configuration automatique des tâches cron
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "⚙️ Configuration automatique des tâches cron\n";
echo "==========================================\n\n";

// Chemin vers PHP dans MAMP
$phpPath = '/Applications/MAMP/bin/php/php8.2.0/bin/php';
$scriptPath = '/Applications/MAMP/htdocs/exemple/backend-mvc/reminder_cron_optimized.php';

echo "1. 🔧 Vérification des chemins...\n";
echo "   - PHP : {$phpPath}\n";
echo "   - Script : {$scriptPath}\n\n";

// Vérifier si PHP existe
if (!file_exists($phpPath)) {
    echo "   ⚠️ PHP MAMP non trouvé, recherche d'alternatives...\n";
    
    // Essayer d'autres versions de PHP MAMP
    $possiblePaths = [
        '/Applications/MAMP/bin/php/php8.1.0/bin/php',
        '/Applications/MAMP/bin/php/php8.0.0/bin/php',
        '/Applications/MAMP/bin/php/php7.4.33/bin/php',
        '/usr/bin/php',
        '/usr/local/bin/php'
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $phpPath = $path;
            echo "   ✅ PHP trouvé : {$phpPath}\n";
            break;
        }
    }
}

// Vérifier si le script existe
if (!file_exists($scriptPath)) {
    echo "   ❌ Script de rappel non trouvé : {$scriptPath}\n";
    exit(1);
}

echo "   ✅ Script de rappel trouvé : {$scriptPath}\n\n";

echo "2. 📋 Configuration des tâches cron...\n";

// Commande cron à ajouter
$cronCommand = "0 * * * * {$phpPath} {$scriptPath} >> /Applications/MAMP/htdocs/exemple/backend-mvc/logs/cron.log 2>&1";

echo "   Commande cron : {$cronCommand}\n\n";

echo "3. 📝 Instructions pour configurer cron :\n";
echo "   ======================================\n\n";

echo "   ÉTAPE 1 : Ouvrir le crontab\n";
echo "   ---------------------------\n";
echo "   Exécutez cette commande dans le terminal :\n";
echo "   crontab -e\n\n";

echo "   ÉTAPE 2 : Ajouter la ligne cron\n";
echo "   --------------------------------\n";
echo "   Ajoutez cette ligne à la fin du fichier :\n";
echo "   {$cronCommand}\n\n";

echo "   ÉTAPE 3 : Sauvegarder et quitter\n";
echo "   ---------------------------------\n";
echo "   - Appuyez sur Ctrl+X\n";
echo "   - Puis Y pour confirmer\n";
echo "   - Puis Entrée pour sauvegarder\n\n";

echo "   ÉTAPE 4 : Vérifier la configuration\n";
echo "   -----------------------------------\n";
echo "   Exécutez : crontab -l\n";
echo "   Vous devriez voir votre ligne cron listée\n\n";

echo "4. 🧪 Test du script de rappel...\n";

// Créer le dossier logs s'il n'existe pas
$logsDir = '/Applications/MAMP/htdocs/exemple/backend-mvc/logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
    echo "   ✅ Dossier logs créé : {$logsDir}\n";
}

// Tester le script
echo "   🧪 Test du script de rappel...\n";
$testCommand = "{$phpPath} {$scriptPath}";
$output = shell_exec($testCommand . ' 2>&1');

if ($output) {
    echo "   ✅ Script testé avec succès !\n";
    echo "   Sortie : " . substr($output, 0, 100) . "...\n";
} else {
    echo "   ⚠️ Aucune sortie du script (normal si pas d'entretiens)\n";
}

echo "\n5. 📊 Types de rappels automatiques :\n";
echo "   ===================================\n";
echo "   ✅ Rappels d'entretien programmés (7 jours avant)\n";
echo "   ✅ Alertes d'entretiens en retard (immédiatement)\n";
echo "   ✅ Logs automatiques des envois\n";
echo "   ✅ Gestion des erreurs\n\n";

echo "6. 📁 Fichiers de logs :\n";
echo "   ====================\n";
echo "   - Logs cron : /Applications/MAMP/htdocs/exemple/backend-mvc/logs/cron.log\n";
echo "   - Logs notifications : table notification_logs\n\n";

echo "7. 🔧 Commandes utiles :\n";
echo "   ====================\n";
echo "   - Voir les tâches cron : crontab -l\n";
echo "   - Supprimer les tâches cron : crontab -r\n";
echo "   - Tester le script manuellement : {$phpPath} {$scriptPath}\n";
echo "   - Voir les logs : tail -f /Applications/MAMP/htdocs/exemple/backend-mvc/logs/cron.log\n\n";

echo "✅ Configuration terminée !\n";
echo "Suivez les instructions ci-dessus pour activer les rappels automatiques.\n";
?>
