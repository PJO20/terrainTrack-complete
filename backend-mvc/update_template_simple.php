<?php
/**
 * Script simple pour mettre à jour la date de modification du mot de passe
 */

$filePath = '/Applications/MAMP/htdocs/exemple/backend-mvc/template/settings.html.twig';

// Lire le contenu du fichier
$content = file_get_contents($filePath);

// Remplacer la date codée en dur par une date dynamique avec ID
$oldCode = '          <p class="security-description">Dernière modification : Il y a 2 mois</p>';
$newCode = '          <p class="security-description" id="password-last-modified">Dernière modification : {% if user.password_updated_at %}{{ user.password_updated_at|date(\'d/m/Y à H:i\') }}{% else %}Jamais{% endif %}</p>';

$newContent = str_replace($oldCode, $newCode, $content);

if ($newContent !== $content) {
    file_put_contents($filePath, $newContent);
    echo "✅ Template mis à jour avec succès !\n";
} else {
    echo "❌ Aucun changement détecté.\n";
}
?>
