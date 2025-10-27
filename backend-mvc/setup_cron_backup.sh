#!/bin/bash

# Script de configuration des sauvegardes automatiques
# Usage: ./setup_cron_backup.sh

echo "🔄 Configuration des sauvegardes automatiques"
echo "============================================="

# Vérifier que nous sommes dans le bon répertoire
if [ ! -f "auto_backup_cron.php" ]; then
    echo "❌ Erreur: auto_backup_cron.php non trouvé"
    echo "Assurez-vous d'être dans le répertoire backend-mvc"
    exit 1
fi

# Créer les dossiers nécessaires
echo "📁 Création des dossiers..."
mkdir -p backups
mkdir -p logs
chmod 755 backups logs

# Rendre le script exécutable
chmod +x auto_backup_cron.php

# Tester la sauvegarde
echo "🧪 Test de la sauvegarde..."
php auto_backup_cron.php --run

if [ $? -eq 0 ]; then
    echo "✅ Test de sauvegarde réussi"
else
    echo "❌ Erreur lors du test de sauvegarde"
    exit 1
fi

# Configuration du cron
echo ""
echo "⏰ Configuration du cron..."
echo "=========================="

# Chemin absolu du script
SCRIPT_PATH=$(pwd)/auto_backup_cron.php
echo "Script: $SCRIPT_PATH"

# Vérifier si le cron existe déjà
if crontab -l 2>/dev/null | grep -q "auto_backup_cron.php"; then
    echo "⚠️  Une tâche cron existe déjà pour les sauvegardes"
    echo "Voulez-vous la remplacer ? (oui/non)"
    read -r response
    if [[ "$response" != "oui" ]]; then
        echo "❌ Configuration annulée"
        exit 0
    fi
fi

# Ajouter la tâche cron
echo "📝 Ajout de la tâche cron..."

# Créer un fichier temporaire avec les tâches existantes
crontab -l 2>/dev/null > /tmp/cron_backup.tmp

# Supprimer l'ancienne tâche si elle existe
grep -v "auto_backup_cron.php" /tmp/cron_backup.tmp > /tmp/cron_backup_new.tmp

# Ajouter la nouvelle tâche (tous les jours à 2h du matin)
echo "0 2 * * * /usr/bin/php $SCRIPT_PATH --run >> $SCRIPT_PATH.log 2>&1" >> /tmp/cron_backup_new.tmp

# Installer le nouveau crontab
crontab /tmp/cron_backup_new.tmp

# Nettoyer
rm /tmp/cron_backup.tmp /tmp/cron_backup_new.tmp

echo "✅ Tâche cron ajoutée avec succès"
echo ""
echo "📋 TÂCHES CRON ACTIVES :"
echo "========================"
crontab -l | grep -E "(auto_backup|backup)"

echo ""
echo "🎉 CONFIGURATION TERMINÉE !"
echo "=========================="
echo ""
echo "📝 INFORMATIONS :"
echo "- Sauvegarde automatique tous les jours à 2h00"
echo "- Dossier de sauvegardes: $(pwd)/backups"
echo "- Logs: $(pwd)/logs/backup.log"
echo "- Conservation: 7 jours"
echo ""
echo "🔧 COMMANDES UTILES :"
echo "- Voir les stats: php auto_backup_cron.php --stats"
echo "- Sauvegarde manuelle: php auto_backup_cron.php --run"
echo "- Restauration d'urgence: php emergency_restore.php"
echo "- Voir les sauvegardes: php backup_database.php --list"
echo ""
echo "⚠️  IMPORTANT :"
echo "- Testez la restauration régulièrement"
echo "- Vérifiez les logs en cas de problème"
echo "- Gardez une copie des sauvegardes hors site"



