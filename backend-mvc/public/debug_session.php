<?php
/**
 * Debug de la session utilisateur
 */

// Démarrer la session
session_start();

echo "<h2>🔍 Debug de la session</h2>";

echo "<h3>📋 Contenu de \$_SESSION :</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>🔐 Tests d'authentification :</h3>";
echo "<ul>";
echo "<li>Session démarrée : " . (session_status() === PHP_SESSION_ACTIVE ? 'Oui' : 'Non') . "</li>";
echo "<li>Clé 'authenticated' existe : " . (isset($_SESSION['authenticated']) ? 'Oui' : 'Non') . "</li>";
echo "<li>Valeur 'authenticated' : " . (isset($_SESSION['authenticated']) ? ($_SESSION['authenticated'] ? 'true' : 'false') : 'Non définie') . "</li>";
echo "<li>Clé 'user' existe : " . (isset($_SESSION['user']) ? 'Oui' : 'Non') . "</li>";
echo "<li>Clé 'user_id' existe : " . (isset($_SESSION['user_id']) ? 'Oui' : 'Non') . "</li>";
echo "</ul>";

if (isset($_SESSION['user'])) {
    echo "<h3>👤 Informations utilisateur :</h3>";
    echo "<pre>";
    print_r($_SESSION['user']);
    echo "</pre>";
}

echo "<br><br>";
echo "<a href='/notifications/preferences' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>← Retour aux préférences</a>";
?>
