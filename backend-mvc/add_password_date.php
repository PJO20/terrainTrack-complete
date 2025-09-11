<?php
/**
 * Script pour ajouter password_updated_at aux données utilisateur
 */

$filePath = '/Applications/MAMP/htdocs/exemple/backend-mvc/src/Controller/SettingsController.php';

// Lire le contenu du fichier
$content = file_get_contents($filePath);

// Ancien code à remplacer
$oldCode = '            \'is_admin\' => ($userData[\'role\'] ?? $currentUser[\'role\']) === \'admin\',
            \'is_super_admin\' => ($userData[\'role\'] ?? $currentUser[\'role\']) === \'super_admin\',
            \'can_access_permissions\' => $canAccessPermissions
        ];';

// Nouveau code avec password_updated_at
$newCode = '            \'is_admin\' => ($userData[\'role\'] ?? $currentUser[\'role\']) === \'admin\',
            \'is_super_admin\' => ($userData[\'role\'] ?? $currentUser[\'role\']) === \'super_admin\',
            \'can_access_permissions\' => $canAccessPermissions,
            \'password_updated_at\' => $userData[\'password_updated_at\'] ?? null
        ];';

// Effectuer le remplacement
$newContent = str_replace($oldCode, $newCode, $content);

// Vérifier si le remplacement a eu lieu
if ($newContent !== $content) {
    // Sauvegarder le fichier modifié
    file_put_contents($filePath, $newContent);
    echo "✅ Données utilisateur mises à jour avec succès !\n";
    echo "📝 La date de modification du mot de passe sera maintenant disponible.\n";
} else {
    echo "❌ Aucun changement détecté. Le code pourrait déjà être mis à jour.\n";
}

echo "\n🔍 Vérification du fichier...\n";
$verification = file_get_contents($filePath);
if (strpos($verification, '\'password_updated_at\' => $userData[\'password_updated_at\']') !== false) {
    echo "✅ La mise à jour est bien présente dans le fichier.\n";
} else {
    echo "❌ La mise à jour n'a pas été appliquée correctement.\n";
}
?>
