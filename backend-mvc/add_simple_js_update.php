<?php
/**
 * Script simple pour ajouter la mise à jour de la date en JavaScript
 */

$filePath = '/Applications/MAMP/htdocs/exemple/backend-mvc/template/settings.html.twig';

// Lire le contenu du fichier
$content = file_get_contents($filePath);

// Ajouter la mise à jour de la date après le message de succès
$oldCode = '                    // Afficher un message de succès
                    showNotification(\'Mot de passe changé avec succès !\', \'success\');
                    
                    // Fermer la modal après 2 secondes';

$newCode = '                    // Afficher un message de succès
                    showNotification(\'Mot de passe changé avec succès !\', \'success\');
                    
                    // Mettre à jour la date de modification du mot de passe
                    const now = new Date();
                    const day = String(now.getDate()).padStart(2, \'0\');
                    const month = String(now.getMonth() + 1).padStart(2, \'0\');
                    const year = now.getFullYear();
                    const hours = String(now.getHours()).padStart(2, \'0\');
                    const minutes = String(now.getMinutes()).padStart(2, \'0\');
                    const formattedDate = `${day}/${month}/${year} à ${hours}:${minutes}`;
                    
                    const dateElement = document.getElementById(\'password-last-modified\');
                    if (dateElement) {
                        dateElement.textContent = `Dernière modification : ${formattedDate}`;
                    }
                    
                    // Fermer la modal après 2 secondes';

$newContent = str_replace($oldCode, $newCode, $content);

if ($newContent !== $content) {
    file_put_contents($filePath, $newContent);
    echo "✅ JavaScript mis à jour avec succès !\n";
    echo "📝 La date se mettra à jour automatiquement après le changement de mot de passe.\n";
} else {
    echo "❌ Aucun changement détecté.\n";
}
?>
