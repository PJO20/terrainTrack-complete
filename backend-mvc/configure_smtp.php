<?php
/**
 * Script de configuration SMTP pour TerrainTrack
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "📧 Configuration SMTP pour TerrainTrack\n";
echo "=====================================\n\n";

// Configuration SMTP recommandée
$smtpConfigs = [
    'gmail' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => 'tls',
        'description' => 'Gmail SMTP (recommandé)'
    ],
    'outlook' => [
        'host' => 'smtp-mail.outlook.com',
        'port' => 587,
        'encryption' => 'tls',
        'description' => 'Outlook/Hotmail SMTP'
    ],
    'yahoo' => [
        'host' => 'smtp.mail.yahoo.com',
        'port' => 587,
        'encryption' => 'tls',
        'description' => 'Yahoo Mail SMTP'
    ],
    'local' => [
        'host' => 'localhost',
        'port' => 25,
        'encryption' => 'none',
        'description' => 'Serveur local (MAMP/XAMPP)'
    ]
];

echo "🔧 Options de configuration SMTP disponibles :\n\n";

foreach ($smtpConfigs as $key => $config) {
    echo "{$key}. {$config['description']}\n";
    echo "   - Serveur : {$config['host']}\n";
    echo "   - Port : {$config['port']}\n";
    echo "   - Chiffrement : {$config['encryption']}\n\n";
}

echo "📝 Pour configurer Gmail (recommandé) :\n";
echo "1. Activez l'authentification à 2 facteurs sur votre compte Gmail\n";
echo "2. Générez un mot de passe d'application :\n";
echo "   - Allez dans Paramètres Google > Sécurité\n";
echo "   - Authentification à 2 facteurs > Mots de passe d'application\n";
echo "   - Générez un mot de passe pour 'TerrainTrack'\n\n";

echo "🔧 Configuration actuelle :\n";
echo "   - SMTP Host : " . ($_ENV['SMTP_HOST'] ?? 'localhost') . "\n";
echo "   - SMTP Port : " . ($_ENV['SMTP_PORT'] ?? '587') . "\n";
echo "   - SMTP Username : " . ($_ENV['SMTP_USERNAME'] ?? 'Non configuré') . "\n";
echo "   - SMTP Password : " . (empty($_ENV['SMTP_PASSWORD']) ? 'Non configuré' : '***configuré***') . "\n";
echo "   - From Email : " . ($_ENV['FROM_EMAIL'] ?? 'noreply@terraintrack.com') . "\n";
echo "   - From Name : " . ($_ENV['FROM_NAME'] ?? 'TerrainTrack') . "\n\n";

echo "📋 Prochaines étapes :\n";
echo "1. Créez un fichier .env dans le répertoire backend-mvc/\n";
echo "2. Ajoutez la configuration SMTP\n";
echo "3. Testez l'envoi d'email\n\n";

// Créer un exemple de fichier .env
$envExample = "# Configuration SMTP pour TerrainTrack
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=votre-email@gmail.com
SMTP_PASSWORD=votre-mot-de-passe-application
FROM_EMAIL=noreply@terraintrack.com
FROM_NAME=TerrainTrack

# Configuration de base de données
DB_HOST=localhost
DB_NAME=exemple
DB_USER=root
DB_PASS=";

file_put_contents(__DIR__ . '/.env.example', $envExample);

echo "✅ Fichier .env.example créé avec la configuration recommandée\n";
echo "📁 Emplacement : " . __DIR__ . "/.env.example\n\n";

echo "🚀 Pour activer les vrais emails :\n";
echo "1. Copiez .env.example vers .env\n";
echo "2. Modifiez les valeurs avec vos informations SMTP\n";
echo "3. Exécutez : php test_email_pjorsini.php\n\n";

echo "✅ Configuration terminée !\n";
?>



