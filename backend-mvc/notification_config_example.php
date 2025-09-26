<?php
/**
 * Exemple de configuration pour les notifications automatiques
 * Copiez ce fichier vers .env et configurez vos paramètres
 */

// Configuration SMTP pour les emails
$_ENV['SMTP_HOST'] = 'localhost';
$_ENV['SMTP_PORT'] = '587';
$_ENV['SMTP_USERNAME'] = ''; // Votre nom d'utilisateur SMTP
$_ENV['SMTP_PASSWORD'] = ''; // Votre mot de passe SMTP
$_ENV['FROM_EMAIL'] = 'noreply@terraintrack.com';
$_ENV['FROM_NAME'] = 'TerrainTrack';

// Configuration SMS (optionnel)
$_ENV['SMS_API_URL'] = ''; // URL de l'API SMS (ex: Twilio, OVH)
$_ENV['SMS_API_KEY'] = ''; // Clé API SMS
$_ENV['SMS_SENDER'] = 'TerrainTrack';

// Exemples de configuration pour différents fournisseurs :

// Gmail SMTP
// $_ENV['SMTP_HOST'] = 'smtp.gmail.com';
// $_ENV['SMTP_PORT'] = '587';
// $_ENV['SMTP_USERNAME'] = 'votre-email@gmail.com';
// $_ENV['SMTP_PASSWORD'] = 'votre-mot-de-passe-app';

// OVH SMTP
// $_ENV['SMTP_HOST'] = 'ssl0.ovh.net';
// $_ENV['SMTP_PORT'] = '587';
// $_ENV['SMTP_USERNAME'] = 'votre-email@votre-domaine.com';
// $_ENV['SMTP_PASSWORD'] = 'votre-mot-de-passe';

// Twilio SMS
// $_ENV['SMS_API_URL'] = 'https://api.twilio.com/2010-04-01/Accounts/VOTRE_ACCOUNT_SID/Messages.json';
// $_ENV['SMS_API_KEY'] = 'VOTRE_AUTH_TOKEN';

// OVH SMS
// $_ENV['SMS_API_URL'] = 'https://api.ovh.com/1.0/sms/';
// $_ENV['SMS_API_KEY'] = 'VOTRE_API_KEY';

echo "Configuration des notifications chargée avec succès !\n";
echo "SMTP Host: " . $_ENV['SMTP_HOST'] . "\n";
echo "SMTP Port: " . $_ENV['SMTP_PORT'] . "\n";
echo "From Email: " . $_ENV['FROM_EMAIL'] . "\n";
echo "SMS API URL: " . ($_ENV['SMS_API_URL'] ?: 'Non configuré') . "\n";
?>



