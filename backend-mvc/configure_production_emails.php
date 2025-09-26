<?php
/**
 * Configuration pour la production - Emails pour tous les utilisateurs
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "📧 Configuration Production - Emails pour tous les utilisateurs\n";
echo "============================================================\n\n";

echo "🎯 Objectif :\n";
echo "   - ✅ Vous recevez les emails sur VOTRE boîte Gmail\n";
echo "   - ✅ Tous les utilisateurs reçoivent leurs emails sur LEURS boîtes\n";
echo "   - ✅ Système prêt pour la production\n\n";

echo "📋 Configuration requise :\n\n";

echo "1. 🔐 Préparer votre compte Gmail (pour envoyer les emails)\n";
echo "   - Allez sur https://myaccount.google.com/security\n";
echo "   - Activez l'authentification à 2 facteurs\n";
echo "   - Créez un mot de passe d'application :\n";
echo "     * https://myaccount.google.com/apppasswords\n";
echo "     * Sélectionnez 'Mail' > 'Autre (nom personnalisé)'\n";
echo "     * Tapez 'TerrainTrack Production'\n";
echo "     * Copiez le mot de passe de 16 caractères\n\n";

echo "2. ⚙️ Configuration du fichier .env\n";
echo "   - SMTP_HOST=smtp.gmail.com\n";
echo "   - SMTP_PORT=587\n";
echo "   - SMTP_USERNAME=votre-email@gmail.com\n";
echo "   - SMTP_PASSWORD=votre-mot-de-passe-application\n";
echo "   - FROM_EMAIL=votre-email@gmail.com\n";
echo "   - FROM_NAME=TerrainTrack\n\n";

echo "3. 🧪 Test avec votre email\n";
echo "   - Je vais envoyer un email de test à VOTRE adresse\n";
echo "   - Vous vérifiez que vous le recevez\n\n";

echo "4. 🚀 Test avec les autres utilisateurs\n";
echo "   - Je vais envoyer des emails à tous les utilisateurs\n";
echo "   - Chacun recevra sur sa propre boîte email\n\n";

echo "🎉 Résultat final :\n";
echo "   - 📧 Rappels d'entretien programmés → Chaque utilisateur\n";
echo "   - ⚠️ Alertes pour entretiens en retard → Chaque utilisateur\n";
echo "   - 🔧 Messages personnalisés → Chaque utilisateur\n";
echo "   - 📋 Assignations d'interventions → Chaque utilisateur\n\n";

echo "❓ Prêt à configurer ?\n";
echo "   - Donnez-moi votre adresse Gmail\n";
echo "   - Donnez-moi le mot de passe d'application\n";
echo "   - Je configure tout automatiquement !\n\n";

echo "💡 Alternative pour la production :\n";
echo "   - SendGrid (gratuit jusqu'à 100 emails/jour)\n";
echo "   - Mailgun (gratuit jusqu'à 10 000 emails/mois)\n";
echo "   - Amazon SES (très économique)\n";
?>



