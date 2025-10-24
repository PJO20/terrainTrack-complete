<?php
/**
 * Script pour contourner temporairement l'authentification des interventions
 */

// Charger les dépendances
require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/Database.php';
require_once __DIR__ . '/src/Service/SessionManager.php';

// Démarrer la session
session_start();

// Vérifier la connexion
if (!SessionManager::isLoggedIn()) {
    header('Location: /login');
    exit;
}

// Récupérer l'utilisateur actuel
$currentUser = SessionManager::getCurrentUser();
$userRole = $currentUser['role'] ?? '';

echo "🔍 BYPASS AUTHENTIFICATION INTERVENTIONS\n";
echo "========================================\n\n";

echo "Utilisateur connecté: " . $currentUser['email'] . "\n";
echo "Rôle: " . $userRole . "\n";
echo "Admin: " . ($userRole === 'admin' ? 'OUI' : 'NON') . "\n\n";

// Vérifier si l'utilisateur est admin
if ($userRole === 'admin') {
    echo "✅ Utilisateur admin détecté - Accès autorisé\n";
    echo "🔧 Redirection vers la création d'intervention...\n";
    
    // Rediriger vers la page de création d'intervention
    header('Location: /intervention/create');
    exit;
} else {
    echo "❌ Utilisateur non admin - Accès refusé\n";
    echo "Rôle actuel: " . $userRole . "\n";
    echo "Rôles autorisés: admin, super_admin\n";
}
?>
