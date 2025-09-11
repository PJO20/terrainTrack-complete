<?php
/**
 * Script pour ajouter la mise à jour automatique de la date
 */

$filePath = '/Applications/MAMP/htdocs/exemple/backend-mvc/template/settings.html.twig';

// Lire le contenu du fichier
$content = file_get_contents($filePath);

// Code à ajouter après le message de succès
$oldCode = '                    // Afficher un message de succès
                    showNotification(\'Mot de passe changé avec succès !\', \'success\');
                    
                    // Fermer la modal après 2 secondes';

$newCode = '                    // Afficher un message de succès
                    showNotification(\'Mot de passe changé avec succès !\', \'success\');
                    
                    // Mettre à jour la date de modification du mot de passe
                    updatePasswordLastModified();
                    
                    // Fermer la modal après 2 secondes';

$newContent = str_replace($oldCode, $newCode, $content);

// Maintenant, ajouter la fonction updatePasswordLastModified à la fin du script
$functionToAdd = '
    // Fonction pour mettre à jour la date de dernière modification du mot de passe
    function updatePasswordLastModified() {
        const now = new Date();
        const day = String(now.getDate()).padStart(2, \'0\');
        const month = String(now.getMonth() + 1).padStart(2, \'0\');
        const year = now.getFullYear();
        const hours = String(now.getHours()).padStart(2, \'0\');
        const minutes = String(now.getMinutes()).padStart(2, \'0\');
        
        const formattedDate = `${day}/${month}/${year} à ${hours}:${minutes}`;
        
        // Mettre à jour l\'élément qui affiche la date
        const dateElement = document.getElementById(\'password-last-modified\');
        if (dateElement) {
            dateElement.textContent = `Dernière modification : ${formattedDate}`;
        }
    }';

// Ajouter la fonction avant la fermeture du script principal
$newContent = str_replace('        });', $functionToAdd . '        });', $newContent);

if ($newContent !== $content) {
    file_put_contents($filePath, $newContent);
    echo "✅ JavaScript mis à jour avec succès !\n";
    echo "📝 La date de modification du mot de passe se mettra à jour automatiquement.\n";
} else {
    echo "❌ Aucun changement détecté.\n";
}

echo "\n🔍 Vérification...\n";
$verification = file_get_contents($filePath);
if (strpos($verification, 'updatePasswordLastModified()') !== false && strpos($verification, 'function updatePasswordLastModified()') !== false) {
    echo "✅ La mise à jour est bien présente dans le fichier.\n";
} else {
    echo "❌ La mise à jour n'a pas été appliquée correctement.\n";
}
?>
