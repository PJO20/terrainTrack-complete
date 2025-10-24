<?php
/**
 * Script de rappels automatiques avec Gmail SMTP
 * TerrainTrack - Système de notifications
 */

// Configuration Gmail SMTP
$smtpConfig = [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'pjorsini20@gmail.com',
    'password' => 'votre_app_password_ici', // Remplacez par votre mot de passe d'application
    'encryption' => 'tls'
];

// Configuration base de données
$dbConfig = [
    'host' => 'localhost',
    'port' => '8889',
    'dbname' => 'exemple',
    'username' => 'root',
    'password' => EnvService::get('DB_PASS', 'root')
];

// Fonction pour envoyer un email avec Gmail SMTP
function sendGmailEmail($to, $subject, $message, $smtpConfig) {
    // Headers pour Gmail
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/plain; charset=UTF-8',
        'From: TerrainTrack <' . $smtpConfig['username'] . '>',
        'Reply-To: ' . $smtpConfig['username']
    ];
    
    // Utiliser la fonction mail() de PHP (Gmail sera configuré dans php.ini)
    return mail($to, $subject, $message, implode("\r\n", $headers));
}

// Fonction pour logger
function logToFile($message) {
    $logFile = __DIR__ . '/logs/cron_gmail.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

try {
    echo "🔄 Démarrage du script de rappels avec Gmail SMTP...\n";
    logToFile("Démarrage du script de rappels avec Gmail");
    
    // Connexion à la base de données
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Connexion à la base de données réussie !\n";
    
    // Récupérer les utilisateurs
    $sql = "SELECT id, name, email FROM users WHERE is_active = 1 AND notification_email = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "👥 Utilisateurs avec notifications : " . count($users) . "\n";
    
    // Envoyer un email de test à vous-même
    $testEmail = "pjorsini20@gmail.com";
    $subject = "🧪 Test Gmail SMTP - TerrainTrack";
    $message = "Bonjour,\n\n";
    $message .= "Ceci est un test d'envoi d'email avec Gmail SMTP.\n";
    $message .= "Si vous recevez ce message, la configuration fonctionne !\n\n";
    $message .= "Cordialement,\nL'équipe TerrainTrack";
    
    echo "📧 Envoi d'un email de test à $testEmail...\n";
    
    $result = sendGmailEmail($testEmail, $subject, $message, $smtpConfig);
    
    if ($result) {
        echo "✅ Email de test envoyé avec succès !\n";
        echo "📬 Vérifiez votre boîte mail (et le dossier SPAM)\n";
        logToFile("Email de test envoyé avec succès à $testEmail");
    } else {
        echo "❌ Échec de l'envoi de l'email de test\n";
        logToFile("Échec de l'envoi de l'email de test");
    }
    
    echo "\n🎉 Test terminé !\n";
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    logToFile("ERREUR : " . $e->getMessage());
    exit(1);
}

