<?php
/**
 * Simuler une connexion utilisateur
 */

// Démarrer la session
session_start();

require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/SessionManager.php';

echo "🔐 SIMULATION CONNEXION UTILISATEUR\n";
echo "===================================\n\n";

// Simuler une connexion réussie
$user = [
    'id' => 7,
    'email' => 'momo@gmail.com',
    'name' => 'Admin User',
    'role' => 'admin',
    'session_timeout' => 30
];

\App\Service\SessionManager::setUser($user);

echo "1️⃣ Connexion simulée:\n";
echo "   Utilisateur: " . $user['name'] . "\n";
echo "   Email: " . $user['email'] . "\n";
echo "   Role: " . $user['role'] . "\n\n";

echo "2️⃣ Vérification session:\n";
echo "   isAuthenticated(): " . (\App\Service\SessionManager::isAuthenticated() ? 'OUI' : 'NON') . "\n";
echo "   getUser(): " . (\App\Service\SessionManager::getUser() ? 'OUI' : 'NON') . "\n\n";

echo "3️⃣ Test de sauvegarde:\n";
$result = \App\Service\SessionManager::updateUserSessionTimeout(60);
echo "   updateUserSessionTimeout(): " . ($result ? 'SUCCÈS' : 'ÉCHEC') . "\n";

if ($result) {
    echo "✅ La sauvegarde fonctionne maintenant !\n";
} else {
    echo "❌ La sauvegarde échoue toujours\n";
}

echo "\n4️⃣ Session persistante:\n";
echo "   Session ID: " . session_id() . "\n";
echo "   Session data: " . json_encode($_SESSION) . "\n";
?>
