<?php

echo "📱 Guide de configuration Twilio pour TerrainTrack\n";
echo "================================================\n\n";

echo "🎯 ÉTAPE 1 : Créer un compte Twilio\n";
echo "===================================\n";
echo "1. 🌐 Allez sur : https://www.twilio.com/\n";
echo "2. 📝 Cliquez sur 'Sign up for free'\n";
echo "3. 📧 Remplissez le formulaire avec vos informations\n";
echo "4. 📱 Vérifiez votre numéro de téléphone\n";
echo "5. 🎁 Vous recevez 15$ de crédit gratuit (≈2000 SMS)\n\n";

echo "🔑 ÉTAPE 2 : Récupérer vos identifiants\n";
echo "=======================================\n";
echo "Dans le dashboard Twilio, notez :\n";
echo "- 🆔 Account SID (commence par 'AC...')\n";
echo "- 🔐 Auth Token (cliquez sur 'Show' pour le voir)\n";
echo "- 📞 Phone Number (votre numéro Twilio)\n\n";

echo "⚙️ ÉTAPE 3 : Configuration TerrainTrack\n";
echo "=======================================\n";

// Vérifier si le fichier .env existe
$envFile = '.env';
$envExists = file_exists($envFile);

if (!$envExists) {
    echo "📝 Création du fichier .env...\n";
    
    $envContent = "# Configuration SMS Twilio pour TerrainTrack
# Remplacez les valeurs par vos identifiants Twilio

# Twilio Configuration
SMS_PROVIDER=twilio
SMS_API_URL=https://api.twilio.com/2010-04-01/Accounts/VOTRE_ACCOUNT_SID/Messages.json
SMS_ACCOUNT_SID=VOTRE_ACCOUNT_SID
SMS_API_KEY=VOTRE_AUTH_TOKEN
SMS_SENDER=VOTRE_NUMERO_TWILIO
SMS_ENABLED=true

# Exemple de configuration (à remplacer) :
# SMS_API_URL=
# SMS_ACCOUNT_SID=
# SMS_API_KEY=your_auth_token_here
# SMS_SENDER=+33123456789
";
    
    file_put_contents($envFile, $envContent);
    echo "✅ Fichier .env créé avec la configuration Twilio\n";
} else {
    echo "ℹ️ Fichier .env existe déjà\n";
    echo "📝 Ajoutez ces lignes à votre fichier .env :\n\n";
    echo "SMS_PROVIDER=twilio\n";
    echo "SMS_API_URL=https://api.twilio.com/2010-04-01/Accounts/VOTRE_ACCOUNT_SID/Messages.json\n";
    echo "SMS_ACCOUNT_SID=VOTRE_ACCOUNT_SID\n";
    echo "SMS_API_KEY=VOTRE_AUTH_TOKEN\n";
    echo "SMS_SENDER=VOTRE_NUMERO_TWILIO\n";
    echo "SMS_ENABLED=true\n\n";
}

echo "🔧 ÉTAPE 4 : Adapter le code TerrainTrack\n";
echo "=========================================\n";
echo "Le code doit être adapté pour utiliser l'API Twilio...\n";

?>

