<?php
/**
 * Nettoyage de l'administrateur de test
 */

require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/Database.php';

header('Content-Type: text/plain; charset=utf-8');

echo "🧹 NETTOYAGE ADMINISTRATEUR DE TEST\n";
echo "===================================\n\n";

try {
    $pdo = \App\Service\Database::connect();
    
    // Supprimer l'administrateur de test s'il existe
    $stmt = $pdo->prepare("DELETE FROM users WHERE email = ?");
    $result = $stmt->execute(['admin_test@example.com']);
    
    if ($result) {
        echo "✅ Administrateur de test supprimé\n";
    } else {
        echo "ℹ️ Aucun administrateur de test à supprimer\n";
    }
    
    // Vérifier l'état des administrateurs
    $stmt = $pdo->query("SELECT id, email, name, role, two_factor_enabled, two_factor_required FROM users WHERE role IN ('admin', 'super_admin') ORDER BY id");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nÉtat des administrateurs:\n";
    foreach ($admins as $admin) {
        $status2FA = '';
        if ($admin['two_factor_required'] && $admin['two_factor_enabled']) {
            $status2FA = ' ✅ 2FA ACTIF';
        } else if ($admin['two_factor_required'] && !$admin['two_factor_enabled']) {
            $status2FA = ' ⚠️ 2FA REQUIS MAIS NON ACTIVÉ';
        } else {
            $status2FA = ' ❌ 2FA NON REQUIS';
        }
        
        echo "  - ID: {$admin['id']}, Email: {$admin['email']}, Nom: " . ($admin['name'] ?? 'NULL') . ", Rôle: " . ($admin['role'] ?? 'NULL') . "$status2FA\n";
    }
    
    echo "\n✅ Nettoyage terminé\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}
?>
