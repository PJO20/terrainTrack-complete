<?php
/**
 * Configuration d'un serveur SMTP local simple
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "📧 Configuration SMTP Local pour MAMP\n";
echo "====================================\n\n";

echo "🔧 MAMP ne configure pas de serveur SMTP par défaut.\n";
echo "Voici les options pour recevoir de vrais emails :\n\n";

echo "📋 Option 1 : Utiliser un service SMTP gratuit\n";
echo "   - Gmail (gratuit, fiable)\n";
echo "   - Outlook/Hotmail (gratuit)\n";
echo "   - Yahoo Mail (gratuit)\n\n";

echo "📋 Option 2 : Utiliser un service de test d'email\n";
echo "   - Mailtrap (gratuit pour les tests)\n";
echo "   - MailHog (local, gratuit)\n";
echo "   - MailCatcher (local, gratuit)\n\n";

echo "📋 Option 3 : Configurer un serveur SMTP local\n";
echo "   - Postfix (complexe)\n";
echo "   - MailHog (simple)\n\n";

echo "🚀 Recommandation : MailHog (simple et gratuit)\n";
echo "   - Installez MailHog sur votre Mac\n";
echo "   - Configurez MAMP pour utiliser MailHog\n";
echo "   - Tous les emails seront capturés dans l'interface web\n\n";

echo "📝 Instructions pour MailHog :\n";
echo "1. Installez MailHog :\n";
echo "   brew install mailhog\n\n";
echo "2. Lancez MailHog :\n";
echo "   mailhog\n\n";
echo "3. Configurez MAMP pour utiliser MailHog :\n";
echo "   - Serveur SMTP : localhost\n";
echo "   - Port : 1025\n\n";
echo "4. Accédez à l'interface web :\n";
echo "   http://localhost:8025\n\n";

echo "🔧 Configuration actuelle de MAMP :\n";
echo "   - Fonction mail() : " . (function_exists('mail') ? '✅ Disponible' : '❌ Non disponible') . "\n";
echo "   - Serveur SMTP : " . (ini_get('SMTP') ?: 'Non configuré') . "\n";
echo "   - Port SMTP : " . (ini_get('smtp_port') ?: 'Non configuré') . "\n\n";

echo "💡 Alternative simple : Utiliser un service de test\n";
echo "   - Mailtrap.io (gratuit)\n";
echo "   - Créez un compte gratuit\n";
echo "   - Utilisez leurs paramètres SMTP\n";
echo "   - Tous les emails apparaîtront dans leur interface\n\n";

echo "✅ Choisissez une option et je vous aiderai à la configurer !\n";
?>



