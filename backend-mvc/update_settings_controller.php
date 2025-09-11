<?php
/**
 * Script pour ajouter password_updated_at à la requête du SettingsController
 */

$filePath = '/Applications/MAMP/htdocs/exemple/backend-mvc/src/Controller/SettingsController.php';

// Lire le contenu du fichier
$content = file_get_contents($filePath);

// Ancien code à remplacer
$oldCode = '        if (in_array(\'avatar\', $columns)) {
            $selectColumns .= ", avatar";
        }
        
        // Récupérer les données utilisateur';

// Nouveau code avec password_updated_at
$newCode = '        if (in_array(\'avatar\', $columns)) {
            $selectColumns .= ", avatar";
        }
        if (in_array(\'password_updated_at\', $columns)) {
            $selectColumns .= ", password_updated_at";
        }
        
        // Récupérer les données utilisateur';

// Effectuer le remplacement
$newContent = str_replace($oldCode, $newCode, $content);

// Vérifier si le remplacement a eu lieu
if ($newContent !== $content) {
    // Sauvegarder le fichier modifié
    file_put_contents($filePath, $newContent);
    echo "✅ SettingsController mis à jour avec succès !\n";
    echo "📝 La colonne password_updated_at sera maintenant récupérée.\n";
} else {
    echo "❌ Aucun changement détecté. Le code pourrait déjà être mis à jour.\n";
}

echo "\n🔍 Vérification du fichier...\n";
$verification = file_get_contents($filePath);
if (strpos($verification, 'password_updated_at') !== false) {
    echo "✅ La mise à jour est bien présente dans le fichier.\n";
} else {
    echo "❌ La mise à jour n'a pas été appliquée correctement.\n";
}
?>
