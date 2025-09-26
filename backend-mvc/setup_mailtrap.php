<?php
/**
 * Configuration Mailtrap pour recevoir de vrais emails
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "📧 Configuration Mailtrap pour de vrais emails\n";
echo "============================================\n\n";

echo "🎯 Mailtrap est parfait car :\n";
echo "   - ✅ Gratuit (100 emails/mois)\n";
echo "   - ✅ Interface web pour voir les emails\n";
echo "   - ✅ Aucune configuration complexe\n";
echo "   - ✅ Parfait pour le développement\n\n";

echo "📝 Étapes pour configurer Mailtrap :\n\n";

echo "1. 🌐 Créer un compte Mailtrap\n";
echo "   - Allez sur https://mailtrap.io\n";
echo "   - Créez un compte gratuit\n";
echo "   - Confirmez votre email\n\n";

echo "2. 📋 Récupérer les paramètres SMTP\n";
echo "   - Connectez-vous à votre compte\n";
echo "   - Allez dans 'Email Testing' > 'Inboxes'\n";
echo "   - Cliquez sur 'Add Inbox' > 'Create Inbox'\n";
echo "   - Sélectionnez 'PHP' dans la liste des intégrations\n";
echo "   - Copiez les paramètres SMTP affichés\n\n";

echo "3. ⚙️ Configurer le fichier .env\n";
echo "   - Remplacez les valeurs dans .env par celles de Mailtrap\n";
echo "   - Exemple de configuration :\n\n";

echo "   SMTP_HOST=sandbox.smtp.mailtrap.io\n";
echo "   SMTP_PORT=2525\n";
echo "   SMTP_USERNAME=votre-username-mailtrap\n";
echo "   SMTP_PASSWORD=votre-password-mailtrap\n";
echo "   FROM_EMAIL=noreply@terraintrack.com\n";
echo "   FROM_NAME=TerrainTrack\n\n";

echo "4. 🧪 Tester l'envoi\n";
echo "   - Exécutez : php test_email_simple.php\n";
echo "   - Vérifiez dans l'interface Mailtrap\n\n";

echo "🎉 Avantages de Mailtrap :\n";
echo "   - 📧 Emails visibles dans l'interface web\n";
echo "   - 🔍 Détails complets de chaque email\n";
echo "   - 📊 Statistiques d'envoi\n";
echo "   - 🚫 Pas de spam dans votre boîte réelle\n";
echo "   - ⚡ Configuration en 2 minutes\n\n";

echo "🚀 Voulez-vous que je vous aide à configurer Mailtrap ?\n";
?>



