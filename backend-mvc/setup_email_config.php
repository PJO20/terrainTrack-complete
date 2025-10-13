<?php
/**
 * Script de configuration pour l'envoi d'emails
 */

echo "📧 CONFIGURATION DE L'ENVOI D'EMAILS\n";
echo "====================================\n\n";

echo "Ce script vous aide à configurer l'envoi d'emails réels.\n\n";

echo "🔧 ÉTAPES DE CONFIGURATION :\n";
echo "1. Choisissez votre fournisseur d'email\n";
echo "2. Configurez vos identifiants\n";
echo "3. Testez l'envoi\n\n";

echo "📋 FOURNISSEURS SUPPORTÉS :\n";
echo "• Gmail (smtp.gmail.com)\n";
echo "• Outlook/Office 365 (smtp.office365.com)\n";
echo "• Yahoo (smtp.mail.yahoo.com)\n";
echo "• Autres serveurs SMTP\n\n";

echo "🔐 CONFIGURATION GMAIL :\n";
echo "1. Activez l'authentification à 2 facteurs\n";
echo "2. Générez un mot de passe d'application\n";
echo "3. Utilisez ce mot de passe dans la configuration\n\n";

echo "📝 FICHIER DE CONFIGURATION :\n";
echo "Modifiez le fichier : config/email_config.php\n\n";

echo "🔧 EXEMPLE DE CONFIGURATION GMAIL :\n";
echo "```php\n";
echo "'smtp' => [\n";
echo "    'host' => 'smtp.gmail.com',\n";
echo "    'port' => 587,\n";
echo "    'username' => 'votre-email@gmail.com',\n";
echo "    'password' => 'votre-mot-de-passe-app',\n";
echo "    'encryption' => 'tls',\n";
echo "],\n";
echo "```\n\n";

echo "🧪 TEST DE CONFIGURATION :\n";
echo "Une fois configuré, testez avec :\n";
echo "php test_email_config.php\n\n";

echo "📚 DOCUMENTATION :\n";
echo "• Gmail: https://support.google.com/mail/answer/185833\n";
echo "• Outlook: https://support.microsoft.com/en-us/office/pop-imap-and-smtp-settings-for-outlook-com-d088b986-291d-42b8-9564-9c414e2aa040\n";
echo "• Yahoo: https://help.yahoo.com/kb/SLN4075.html\n\n";

echo "✨ CONFIGURATION TERMINÉE !\n";
echo "Modifiez config/email_config.php avec vos identifiants.\n";
?>
