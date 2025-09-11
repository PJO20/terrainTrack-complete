<?php
/**
 * Script pour ajouter la mise à jour automatique de la date de modification du mot de passe
 */

$filePath = '/Applications/MAMP/htdocs/exemple/backend-mvc/template/settings.html.twig';

// Lire le contenu du fichier
$content = file_get_contents($filePath);

// Code à ajouter après la ligne de succès
$oldCode = '                    // Afficher un message de succès
                    showNotification(\'Mot de passe changé avec succès !\', \'success\');
                    
                    // Fermer la modal après 2 secondes';

// Nouveau code avec mise à jour de la date
$newCode = '                    // Afficher un message de succès
                    showNotification(\'Mot de passe changé avec succès !\', \'success\');
                    
                    // Mettre à jour la date de modification du mot de passe
                    updatePasswordDate();
                    
                    // Fermer la modal après 2 secondes';

// Effectuer le remplacement
$newContent = str_replace($oldCode, $newCode, $content);

// Maintenant, ajouter la fonction updatePasswordDate à la fin du script
$functionToAdd = '
    // Fonction pour mettre à jour la date de modification du mot de passe
    function updatePasswordDate() {
        const now = new Date();
        const options = { 
            day: \'2-digit\', 
            month: \'2-digit\', 
            year: \'numeric\', 
            hour: \'2-digit\', 
            minute: \'2-digit\',
            hour12: false
        };
        const formattedDate = now.toLocaleDateString(\'fr-FR\', options);
        
        // Mettre à jour l\'élément qui affiche la date
        const dateElement = document.querySelector(\'.security-description\');
        if (dateElement) {
            dateElement.textContent = `Dernière modification : ${formattedDate}`;
        }
    }';

// Ajouter la fonction avant la fermeture du script
$newContent = str_replace('        });', $functionToAdd . '        });', $newContent);

// Vérifier si le remplacement a eu lieu
if ($newContent !== $content) {
    // Sauvegarder le fichier modifié
    file_put_contents($filePath, $newContent);
    echo "✅ JavaScript mis à jour avec succès !\n";
    echo "📝 La date de modification du mot de passe se mettra à jour automatiquement.\n";
} else {
    echo "❌ Aucun changement détecté. Le code pourrait déjà être mis à jour.\n";
}

echo "\n🔍 Vérification du fichier...\n";
$verification = file_get_contents($filePath);
if (strpos($verification, 'updatePasswordDate()') !== false && strpos($verification, 'function updatePasswordDate()') !== false) {
    echo "✅ La mise à jour est bien présente dans le fichier.\n";
} else {
    echo "❌ La mise à jour n'a pas été appliquée correctement.\n";
}
?>
