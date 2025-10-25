<?php
/**
 * Activation de la 2FA pour tous les administrateurs
 */

require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/Database.php';

header('Content-Type: text/plain; charset=utf-8');

echo "🔐 ACTIVATION 2FA POUR TOUS LES ADMINS\n";
echo "=======================================\n\n";

try {
    echo "1️⃣ Vérification des administrateurs actuels:\n";
    
    $pdo = \App\Service\Database::connect();
    $stmt = $pdo->query("SELECT id, email, name, role, two_factor_enabled, two_factor_required, two_factor_secret FROM users WHERE role IN ('admin', 'super_admin') ORDER BY id");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($admins)) {
        echo "   ✅ Administrateurs trouvés:\n";
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
    } else {
        echo "   ❌ Aucun administrateur trouvé\n";
        exit;
    }
    
    echo "\n2️⃣ Activation de la 2FA pour tous les administrateurs:\n";
    
    $updatedCount = 0;
    $errors = [];
    
    foreach ($admins as $admin) {
        echo "   Traitement de {$admin['email']} (ID: {$admin['id']})...\n";
        
        try {
            // Générer un secret 2FA unique pour chaque admin
            $secret2FA = 'MFRGG43B' . strtoupper(substr(md5($admin['email'] . time()), 0, 24));
            
            // Activer la 2FA requise et activée
            $stmt = $pdo->prepare("UPDATE users SET two_factor_required = 1, two_factor_enabled = 1, two_factor_secret = ? WHERE id = ?");
            $result = $stmt->execute([$secret2FA, $admin['id']]);
            
            if ($result) {
                echo "     ✅ 2FA activée avec succès\n";
                $updatedCount++;
            } else {
                echo "     ❌ Erreur lors de l'activation\n";
                $errors[] = "Erreur pour {$admin['email']}";
            }
        } catch (Exception $e) {
            echo "     ❌ Exception: " . $e->getMessage() . "\n";
            $errors[] = "Exception pour {$admin['email']}: " . $e->getMessage();
        }
    }
    
    echo "\n3️⃣ Résumé des mises à jour:\n";
    echo "   ✅ Administrateurs mis à jour: $updatedCount\n";
    echo "   ❌ Erreurs: " . count($errors) . "\n";
    
    if (!empty($errors)) {
        echo "   Détails des erreurs:\n";
        foreach ($errors as $error) {
            echo "     - $error\n";
        }
    }
    
    echo "\n4️⃣ Vérification des mises à jour:\n";
    
    $stmt = $pdo->query("SELECT id, email, name, role, two_factor_enabled, two_factor_required, two_factor_secret FROM users WHERE role IN ('admin', 'super_admin') ORDER BY id");
    $adminsUpdated = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($adminsUpdated)) {
        echo "   ✅ Administrateurs après mise à jour:\n";
        foreach ($adminsUpdated as $admin) {
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
    
    echo "\n5️⃣ Configuration des nouveaux administrateurs:\n";
    
    // Créer une règle pour que tous les nouveaux administrateurs aient la 2FA requise
    echo "   Configuration pour les nouveaux administrateurs...\n";
    
    // Vérifier s'il existe une table de configuration système
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'system_settings'");
        $systemTableExists = $stmt->fetch() !== false;
        
        if ($systemTableExists) {
            // Ajouter une configuration pour la 2FA obligatoire pour les admins
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute([
                '2fa_required_for_admins',
                '1',
                '2FA obligatoire pour tous les administrateurs'
            ]);
            echo "   ✅ Configuration système mise à jour\n";
        } else {
            echo "   ℹ️ Table system_settings non trouvée - configuration manuelle requise\n";
        }
    } catch (Exception $e) {
        echo "   ⚠️ Erreur lors de la configuration système: " . $e->getMessage() . "\n";
    }
    
    echo "\n6️⃣ Test de la logique de connexion:\n";
    
    // Vérifier si AuthController contient la logique 2FA
    $authControllerFile = __DIR__ . '/src/Controller/AuthController.php';
    if (file_exists($authControllerFile)) {
        $content = file_get_contents($authControllerFile);
        
        if (strpos($content, 'two_factor') !== false) {
            echo "   ✅ AuthController contient des références à 2FA\n";
        } else {
            echo "   ❌ AuthController ne contient pas de références à 2FA\n";
            echo "   🔧 SOLUTION: Implémenter la logique 2FA dans AuthController\n";
        }
        
        if (strpos($content, 'login') !== false) {
            echo "   ✅ AuthController contient la méthode login\n";
        } else {
            echo "   ❌ AuthController ne contient pas la méthode login\n";
        }
    } else {
        echo "   ❌ Fichier AuthController non trouvé\n";
    }
    
    echo "\n7️⃣ Recommandations:\n";
    
    $allAdminsHave2FA = true;
    foreach ($adminsUpdated as $admin) {
        if (!$admin['two_factor_required'] || !$admin['two_factor_enabled']) {
            $allAdminsHave2FA = false;
            break;
        }
    }
    
    if ($allAdminsHave2FA) {
        echo "   ✅ Tous les administrateurs ont la 2FA activée\n";
        echo "   🔧 PROBLÈME: La logique de vérification 2FA ne fonctionne pas\n";
        echo "   🔧 SOLUTION: Vérifier la logique de connexion dans AuthController\n";
        echo "   🔧 ACTION: Se déconnecter et se reconnecter pour tester la 2FA\n";
    } else {
        echo "   ❌ Certains administrateurs n'ont pas la 2FA activée\n";
        echo "   🔧 SOLUTION: Vérifier la configuration\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "RÉSUMÉ FINAL\n";
    echo str_repeat("=", 50) . "\n";
    
    if ($allAdminsHave2FA) {
        echo "✅ 2FA ACTIVÉE POUR TOUS LES ADMINS\n";
        echo "🔧 PROBLÈME: Logique de vérification 2FA\n";
        echo "🔧 SOLUTION: Vérifier la logique de connexion\n";
        echo "🎯 PROCHAINES ÉTAPES:\n";
        echo "   1. Se déconnecter\n";
        echo "   2. Se reconnecter avec n'importe quel admin\n";
        echo "   3. Vérifier que la 2FA est demandée\n";
        echo "   4. Implémenter la logique 2FA dans AuthController si nécessaire\n";
    } else {
        echo "❌ PROBLÈME: 2FA pas activée pour tous les admins\n";
        echo "🔧 SOLUTION: Vérifier la configuration\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
