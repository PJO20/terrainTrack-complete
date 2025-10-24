<?php
/**
 * Script pour simuler une session admin et tester l'accès aux interventions
 */

// Démarrer la session
session_start();

echo "🔧 SIMULATION SESSION ADMIN\n";
echo "===========================\n\n";

// Simuler une session admin
$_SESSION['user'] = [
    'id' => 7,
    'email' => 'momo@gmail.com',
    'name' => 'Admin User',
    'role' => 'admin',
    'is_admin' => true,
    'is_super_admin' => true
];

echo "✅ Session admin simulée:\n";
echo "   ID: " . $_SESSION['user']['id'] . "\n";
echo "   Email: " . $_SESSION['user']['email'] . "\n";
echo "   Rôle: " . $_SESSION['user']['role'] . "\n";
echo "   Admin: " . ($_SESSION['user']['is_admin'] ? 'OUI' : 'NON') . "\n";
echo "   Super Admin: " . ($_SESSION['user']['is_super_admin'] ? 'OUI' : 'NON') . "\n\n";

echo "🔗 LIENS DE TEST:\n";
echo "----------------\n";
echo "Création d'intervention: http://localhost:8888/intervention/create\n";
echo "Liste des interventions: http://localhost:8888/intervention/list\n";
echo "Dashboard: http://localhost:8888/dashboard\n\n";

echo "✅ Session prête - Testez maintenant l'accès aux interventions !\n";
?>
