<?php
/**
 * Configuration Gmail SMTP pour recevoir de vrais emails
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "📧 Configuration Gmail SMTP pour de vrais emails\n";
echo "==============================================\n\n";

echo "🔐 Pour utiliser Gmail SMTP, vous devez :\n\n";

echo "1. 📱 Activer l'authentification à 2 facteurs sur votre compte Gmail\n";
echo "   - Allez sur https://myaccount.google.com/security\n";
echo "   - Activez l'authentification à 2 facteurs\n\n";

echo "2. 🔑 Créer un mot de passe d'application\n";
echo "   - Allez sur https://myaccount.google.com/apppasswords\n";
echo "   - Sélectionnez 'Mail' et 'Autre (nom personnalisé)'\n";
echo "   - Tapez 'TerrainTrack' comme nom\n";
echo "   - Copiez le mot de passe généré (16 caractères)\n\n";

echo "3. ⚙️ Configurer le fichier .env\n";
echo "   - Ouvrez le fichier .env dans votre éditeur\n";
echo "   - Remplacez les valeurs par vos informations Gmail\n\n";

echo "📝 Exemple de configuration .env :\n";
echo "   SMTP_HOST=smtp.gmail.com\n";
echo "   SMTP_PORT=587\n";
echo "   SMTP_USERNAME=votre-email@gmail.com\n";
echo "   SMTP_PASSWORD=votre-mot-de-passe-application-16-caracteres\n";
echo "   FROM_EMAIL=votre-email@gmail.com\n";
echo "   FROM_NAME=TerrainTrack\n\n";

echo "🚀 Une fois configuré, vous recevrez de vrais emails !\n\n";

echo "💡 Alternative : Utiliser un service de test d'email\n";
echo "   - Mailtrap.io (gratuit pour les tests)\n";
echo "   - Mailgun (gratuit jusqu'à 10 000 emails/mois)\n";
echo "   - SendGrid (gratuit jusqu'à 100 emails/jour)\n\n";

echo "❓ Voulez-vous que je vous aide à configurer Gmail ou un autre service ?\n";
?>



