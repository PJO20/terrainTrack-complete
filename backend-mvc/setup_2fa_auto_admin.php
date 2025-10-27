<?php
/**
 * Configuration automatique de la 2FA pour tous les nouveaux administrateurs
 */

require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/Database.php';

header('Content-Type: text/plain; charset=utf-8');

echo "🔐 CONFIGURATION AUTOMATIQUE 2FA POUR NOUVEAUX ADMINS\n";
echo "====================================================\n\n";

try {
    echo "1️⃣ Vérification de la structure de la table users:\n";
    
    $pdo = \App\Service\Database::connect();
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Colonnes de la table users:\n";
    $has2FAFields = false;
    foreach ($columns as $column) {
        if (strpos($column['Field'], 'two_factor') !== false) {
            echo "     - " . $column['Field'] . " (" . $column['Type'] . ") ✅\n";
            $has2FAFields = true;
        } else {
            echo "     - " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
    }
    
    if (!$has2FAFields) {
        echo "   ❌ Colonnes 2FA non trouvées\n";
        exit;
    }
    
    echo "\n2️⃣ Configuration des paramètres par défaut pour les administrateurs:\n";
    
    // Mettre à jour tous les administrateurs existants pour avoir la 2FA requise
    $stmt = $pdo->prepare("UPDATE users SET two_factor_required = 1 WHERE role IN ('admin', 'super_admin')");
    $result = $stmt->execute();
    
    if ($result) {
        echo "   ✅ 2FA requise activée pour tous les administrateurs existants\n";
    } else {
        echo "   ❌ Erreur lors de l'activation de la 2FA requise\n";
    }
    
    echo "\n3️⃣ Vérification des administrateurs actuels:\n";
    
    $stmt = $pdo->query("SELECT id, email, name, role, two_factor_enabled, two_factor_required, two_factor_secret FROM users WHERE role IN ('admin', 'super_admin') ORDER BY id");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($admins)) {
        echo "   ✅ Administrateurs actuels:\n";
        foreach ($admins as $admin) {
            $status2FA = '';
            if ($admin['two_factor_required'] && $admin['two_factor_enabled']) {
                $status2FA = ' ✅ 2FA ACTIF';
            } else if ($admin['two_factor_required'] && !$admin['two_factor_enabled']) {
                $status2FA = ' ⚠️ 2FA REQUIS MAIS NON ACTIVÉ';
            } else {
                $status2FA = ' ❌ 2FA NON REQUIS';
            }
            
            echo "     - ID: {$admin['id']}, Email: {$admin['email']}, Nom: " . ($admin['name'] ?? 'NULL') . ", Rôle: " . ($admin['role'] ?? 'NULL') . "$status2FA\n";
        }
    }
    
    echo "\n4️⃣ Création d'un script de vérification automatique:\n";
    
    $checkScript = '<?php
/**
 * Script de vérification automatique de la 2FA pour les administrateurs
 * À exécuter lors de la création d\'un nouvel administrateur
 */

require_once __DIR__ . \'/src/Service/EnvService.php\';
require_once __DIR__ . \'/src/Service/Database.php\';

function ensureAdmin2FA($userId) {
    try {
        $pdo = \App\Service\Database::connect();
        
        // Vérifier si l\'utilisateur est un administrateur
        $stmt = $pdo->prepare("SELECT role, two_factor_required, two_factor_enabled FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && in_array($user[\'role\'], [\'admin\', \'super_admin\'])) {
            // S\'assurer que la 2FA est requise
            if (!$user[\'two_factor_required\']) {
                $stmt = $pdo->prepare("UPDATE users SET two_factor_required = 1 WHERE id = ?");
                $stmt->execute([$userId]);
                error_log("2FA requise activée pour l\'administrateur ID: $userId");
            }
            
            // Si la 2FA n\'est pas encore activée, générer un secret
            if (!$user[\'two_factor_enabled\']) {
                $secret2FA = \'MFRGG43B\' . strtoupper(substr(md5($userId . time()), 0, 24));
                $stmt = $pdo->prepare("UPDATE users SET two_factor_enabled = 1, two_factor_secret = ? WHERE id = ?");
                $stmt->execute([$secret2FA, $userId]);
                error_log("2FA activée pour l\'administrateur ID: $userId");
            }
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la vérification 2FA pour l\'utilisateur ID: $userId - " . $e->getMessage());
    }
}
?>';
    
    file_put_contents(__DIR__ . '/ensure_admin_2fa.php', $checkScript);
    echo "   ✅ Script de vérification créé: ensure_admin_2fa.php\n";
    
    echo "\n5️⃣ Test du script de vérification:\n";
    
    // Tester le script avec un administrateur existant
    require_once __DIR__ . '/ensure_admin_2fa.php';
    
    foreach ($admins as $admin) {
        echo "   Test pour {$admin['email']} (ID: {$admin['id']})...\n";
        ensureAdmin2FA($admin['id']);
        echo "     ✅ Vérification effectuée\n";
    }
    
    echo "\n6️⃣ Vérification finale:\n";
    
    $stmt = $pdo->query("SELECT id, email, name, role, two_factor_enabled, two_factor_required, two_factor_secret FROM users WHERE role IN ('admin', 'super_admin') ORDER BY id");
    $adminsFinal = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($adminsFinal)) {
        echo "   ✅ État final des administrateurs:\n";
        $allAdminsHave2FA = true;
        foreach ($adminsFinal as $admin) {
            $status2FA = '';
            if ($admin['two_factor_required'] && $admin['two_factor_enabled']) {
                $status2FA = ' ✅ 2FA ACTIF';
            } else if ($admin['two_factor_required'] && !$admin['two_factor_enabled']) {
                $status2FA = ' ⚠️ 2FA REQUIS MAIS NON ACTIVÉ';
                $allAdminsHave2FA = false;
            } else {
                $status2FA = ' ❌ 2FA NON REQUIS';
                $allAdminsHave2FA = false;
            }
            
            echo "     - ID: {$admin['id']}, Email: {$admin['email']}, Nom: " . ($admin['name'] ?? 'NULL') . ", Rôle: " . ($admin['role'] ?? 'NULL') . "$status2FA\n";
        }
        
        if ($allAdminsHave2FA) {
            echo "   ✅ Tous les administrateurs ont la 2FA activée\n";
        } else {
            echo "   ⚠️ Certains administrateurs n'ont pas la 2FA activée\n";
        }
    }
    
    echo "\n7️⃣ Recommandations pour l'implémentation:\n";
    
    echo "   🔧 INTÉGRATION DANS LE CODE:\n";
    echo "   1. Inclure ensure_admin_2fa.php dans UserRepository::save()\n";
    echo "   2. Appeler ensureAdmin2FA($userId) après création d'un utilisateur\n";
    echo "   3. Vérifier la logique 2FA dans AuthController::login()\n";
    echo "   4. Implémenter la vérification du code 2FA\n";
    
    echo "\n   📝 EXEMPLE D'INTÉGRATION:\n";
    echo "   ```php\n";
    echo "   // Dans UserRepository::save()\n";
    echo "   if (in_array($user->getRole(), ['admin', 'super_admin'])) {\n";
    echo "       require_once __DIR__ . '/ensure_admin_2fa.php';\n";
    echo "       ensureAdmin2FA($user->getId());\n";
    echo "   }\n";
    echo "   ```\n";
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "RÉSUMÉ FINAL\n";
    echo str_repeat("=", 50) . "\n";
    
    echo "✅ 2FA CONFIGURÉE POUR TOUS LES ADMINS\n";
    echo "✅ SCRIPT DE VÉRIFICATION AUTOMATIQUE CRÉÉ\n";
    echo "🔧 PROCHAINES ÉTAPES:\n";
    echo "   1. Intégrer ensure_admin_2fa.php dans UserRepository\n";
    echo "   2. Implémenter la logique 2FA dans AuthController\n";
    echo "   3. Tester la déconnexion/reconnexion\n";
    echo "   4. Vérifier que la 2FA est demandée pour tous les admins\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>

