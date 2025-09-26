<?php
/**
 * Configuration pour recevoir de vrais emails dans votre boîte mail
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "📧 Configuration pour recevoir de vrais emails\n";
echo "============================================\n\n";

echo "🎯 Objectif : Recevoir les emails TerrainTrack dans votre boîte mail réelle\n\n";

echo "📋 Étapes à suivre :\n\n";

echo "1. 🔐 Préparer votre compte Gmail\n";
echo "   - Allez sur https://myaccount.google.com/security\n";
echo "   - Activez l'authentification à 2 facteurs (si pas déjà fait)\n";
echo "   - C'est obligatoire pour utiliser SMTP avec Gmail\n\n";

echo "2. 🔑 Créer un mot de passe d'application\n";
echo "   - Allez sur https://myaccount.google.com/apppasswords\n";
echo "   - Cliquez sur 'Sélectionner une application' > 'Mail'\n";
echo "   - Cliquez sur 'Sélectionner un appareil' > 'Autre (nom personnalisé)'\n";
echo "   - Tapez 'TerrainTrack' comme nom\n";
echo "   - Cliquez sur 'Générer'\n";
echo "   - COPIEZ le mot de passe de 16 caractères (ex: abcd efgh ijkl mnop)\n";
echo "   - ⚠️  IMPORTANT : Vous ne pourrez plus voir ce mot de passe après !\n\n";

echo "3. ⚙️ Configurer le fichier .env\n";
echo "   - Ouvrez le fichier .env dans votre éditeur\n";
echo "   - Remplacez les valeurs par vos informations Gmail :\n\n";

echo "   SMTP_HOST=smtp.gmail.com\n";
echo "   SMTP_PORT=587\n";
echo "   SMTP_USERNAME=votre-email@gmail.com\n";
echo "   SMTP_PASSWORD=votre-mot-de-passe-application-16-caracteres\n";
echo "   FROM_EMAIL=votre-email@gmail.com\n";
echo "   FROM_NAME=TerrainTrack\n\n";

echo "4. 🧪 Tester l'envoi\n";
echo "   - Exécutez : php test_email_simple.php\n";
echo "   - Vérifiez votre boîte mail (et le dossier spam)\n\n";

echo "🎉 Résultat attendu :\n";
echo "   - ✅ Emails reçus dans votre boîte Gmail\n";
echo "   - ✅ Rappels d'entretien programmés\n";
echo "   - ✅ Alertes pour entretiens en retard\n";
echo "   - ✅ Messages personnalisés avec détails du véhicule\n";
echo "   - ✅ Assignations d'interventions\n\n";

echo "🚀 Voulez-vous que je vous aide à configurer maintenant ?\n";
echo "   - Dites-moi votre adresse Gmail\n";
echo "   - Je vous guide pour créer le mot de passe d'application\n";
echo "   - Je configure le fichier .env pour vous\n\n";

echo "💡 Alternative si vous n'avez pas Gmail :\n";
echo "   - Outlook/Hotmail (même processus)\n";
echo "   - Yahoo Mail (même processus)\n";
echo "   - Autre service email avec SMTP\n";
?>



