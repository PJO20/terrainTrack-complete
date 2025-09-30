<?php
/**
 * Script pour vider le cache et forcer le rechargement
 */

echo "🗑️ Vidage du cache Twig\n";
echo "=======================\n\n";

// Vider le cache Twig
$cacheDir = __DIR__ . '/var/cache';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    
    $dirs = glob($cacheDir . '/*', GLOB_ONLYDIR);
    foreach ($dirs as $dir) {
        rmdir($dir);
    }
    
    echo "✅ Cache Twig vidé !\n";
} else {
    echo "⚠️ Répertoire cache n'existe pas\n";
}

// Vider aussi le cache des assets
$assetsCacheDir = __DIR__ . '/var/cache/assets';
if (is_dir($assetsCacheDir)) {
    $files = glob($assetsCacheDir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    echo "✅ Cache assets vidé !\n";
}

// Créer un timestamp pour forcer le rechargement
$timestamp = time();
echo "\n🔄 Timestamp de rechargement : $timestamp\n";

// Modifier le template pour forcer le rechargement
$templatePath = __DIR__ . '/template/notifications/preferences.html.twig';
if (file_exists($templatePath)) {
    $content = file_get_contents($templatePath);
    
    // Ajouter un commentaire avec timestamp pour forcer le rechargement
    $comment = "<!-- Cache cleared at $timestamp -->\n";
    
    if (strpos($content, '<!-- Cache cleared at') !== false) {
        // Remplacer l'ancien timestamp
        $content = preg_replace('/<!-- Cache cleared at \d+ -->/', $comment, $content);
    } else {
        // Ajouter le timestamp au début
        $content = $comment . $content;
    }
    
    file_put_contents($templatePath, $content);
    echo "✅ Template modifié avec timestamp $timestamp\n";
}

echo "\n🎯 MAINTENANT :\n";
echo "1. Allez sur http://localhost:8888/notifications/preferences\n";
echo "2. Faites un CTRL+F5 (ou CMD+SHIFT+R sur Mac) pour forcer le rechargement\n";
echo "3. Vérifiez si l'email de notification affiche maintenant 'pjorsini20@gmail.com'\n";
echo "\n💡 Si ça ne marche toujours pas, le problème vient du template lui-même.\n";
?>


