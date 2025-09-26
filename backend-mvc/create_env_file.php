<?php
/**
 * Script pour créer le fichier .env
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "📝 Création du fichier .env\n";
echo "==========================\n\n";

$envContent = "# Configuration de la base de données
DB_HOST=localhost
DB_NAME=exemple
DB_USER=root
DB_PASS=root
DB_PORT=8889

# Configuration SMTP Gmail
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=pjorsini20@gmail.com
SMTP_PASSWORD=gmqncgtfunpfnkjh
FROM_EMAIL=noreply@terraintrack.com
FROM_NAME=TerrainTrack

# Configuration de l'application
APP_DEBUG=true
APP_ENV=development

# Configuration des logs
LOG_PATH=/Applications/MAMP/htdocs/exemple/backend-mvc/logs/app.log";

$envFile = __DIR__ . '/.env';
$result = file_put_contents($envFile, $envContent);

if ($result !== false) {
    echo "✅ Fichier .env créé avec succès !\n";
    echo "📁 Emplacement : {$envFile}\n";
    echo "📝 Contenu :\n";
    echo $envContent;
} else {
    echo "❌ Erreur lors de la création du fichier .env\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ FICHIER .ENV CRÉÉ !\n";
echo str_repeat("=", 50) . "\n";

echo "\n🚀 Maintenant relancez le diagnostic :\n";
echo "php debug_login_complete.php\n";
?>
