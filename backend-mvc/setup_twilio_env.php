<?php

echo "🔧 Configuration automatique de Twilio\n";
echo "======================================\n\n";

$envContent = "# Configuration Twilio pour TerrainTrack
# Remplacez les valeurs par vos identifiants Twilio réels

# Provider SMS
SMS_PROVIDER=twilio
SMS_ENABLED=true

# Configuration Twilio - À REMPLACER par vos vraies valeurs
SMS_API_URL=https://api.twilio.com/2010-04-01/Accounts/VOTRE_ACCOUNT_SID/Messages.json
SMS_ACCOUNT_SID=VOTRE_ACCOUNT_SID
SMS_API_KEY=VOTRE_AUTH_TOKEN
SMS_SENDER=VOTRE_NUMERO_TWILIO

# Instructions :
# 1. Allez sur https://www.twilio.com/
# 2. Créez un compte gratuit (15\$ offerts)
# 3. Dans le dashboard, récupérez :
#    - Account SID (commence par AC...)
#    - Auth Token (cliquez sur \"Show\")
#    - Phone Number (votre numéro Twilio)
# 4. Modifiez ce fichier .env avec vos vraies valeurs
# 5. Testez via l'interface web
";

// Créer le fichier .env s'il n'existe pas
if (!file_exists('.env')) {
    file_put_contents('.env', $envContent);
    echo "✅ Fichier .env créé avec la configuration Twilio\n";
} else {
    echo "ℹ️ Fichier .env existe déjà\n";
    
    // Vérifier si la configuration Twilio est présente
    $currentEnv = file_get_contents('.env');
    if (strpos($currentEnv, 'SMS_PROVIDER') === false) {
        echo "📝 Ajout de la configuration Twilio au fichier .env existant...\n";
        file_put_contents('.env', "\n\n" . $envContent, FILE_APPEND);
        echo "✅ Configuration Twilio ajoutée\n";
    } else {
        echo "ℹ️ Configuration SMS déjà présente dans .env\n";
    }
}

echo "\n📋 Étapes suivantes\n";
echo "==================\n";
echo "1. 🌐 Allez sur https://www.twilio.com/\n";
echo "2. 📝 Créez un compte gratuit\n";
echo "3. 🔑 Récupérez vos identifiants :\n";
echo "   - Account SID (AC...)\n";
echo "   - Auth Token\n";
echo "   - Phone Number\n";
echo "4. ✏️ Modifiez le fichier .env avec vos vraies valeurs\n";
echo "5. 🧪 Testez avec : php test_twilio_configuration.php\n";
echo "6. 🌐 Testez via l'interface web\n";

echo "\n💡 Conseil\n";
echo "=========\n";
echo "Twilio offre 15\$ de crédit gratuit (≈2000 SMS)\n";
echo "Parfait pour tester TerrainTrack !\n";

echo "\n🏁 Configuration terminée\n";

?>

