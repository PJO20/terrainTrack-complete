<?php
/**
 * Script pour mettre à jour la date de modification du mot de passe dans le template
 */

$filePath = '/Applications/MAMP/htdocs/exemple/backend-mvc/template/settings.html.twig';

// Lire le contenu du fichier
$content = file_get_contents($filePath);

// Ancien code à remplacer
$oldCode = '          <p class="security-description">Dernière modification : Il y a 2 mois</p>';

// Nouveau code avec la date dynamique
$newCode = '          <p class="security-description">Dernière modification : {% if user.password_updated_at %}{{ user.password_updated_at|date(\'d/m/Y à H:i\') }}{% else %}Jamais{% endif %}</p>';

// Effectuer le remplacement
$newContent = str_replace($oldCode, $newCode, $content);

// Vérifier si le remplacement a eu lieu
if ($newContent !== $content) {
    // Sauvegarder le fichier modifié
    file_put_contents($filePath, $newContent);
    echo "✅ Template mis à jour avec succès !\n";
    echo "📝 La date de modification du mot de passe sera maintenant dynamique.\n";
} else {
    echo "❌ Aucun changement détecté. Le code pourrait déjà être mis à jour.\n";
}

echo "\n🔍 Vérification du fichier...\n";
$verification = file_get_contents($filePath);
if (strpos($verification, 'user.password_updated_at') !== false) {
    echo "✅ La mise à jour est bien présente dans le fichier.\n";
} else {
    echo "❌ La mise à jour n'a pas été appliquée correctement.\n";
}
?>
