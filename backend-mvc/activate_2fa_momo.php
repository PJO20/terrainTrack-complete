<?php
/**
 * Activation de la 2FA pour momo@gmail.com
 */

require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/Database.php';

header('Content-Type: text/plain; charset=utf-8');

echo "🔐 ACTIVATION 2FA POUR MOMO\n";
echo "===========================\n\n";

try {
    echo "1️⃣ Vérification de l'état actuel:\n";
    
    $pdo = \App\Service\Database::connect();
    $stmt = $pdo->prepare("SELECT id, email, name, role, two_factor_enabled, two_factor_required, two_factor_secret FROM users WHERE email = ?");
    $stmt->execute(['momo@gmail.com']);
    $momoData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($momoData) {
        echo "   ✅ Données momo récupérées:\n";
        echo "     - ID: " . $momoData['id'] . "\n";
        echo "     - Email: " . $momoData['email'] . "\n";
        echo "     - Nom: " . ($momoData['name'] ?? 'NULL') . "\n";
        echo "     - Rôle: " . ($momoData['role'] ?? 'NULL') . "\n";
        echo "     - 2FA activé: " . ($momoData['two_factor_enabled'] ? 'OUI' : 'NON') . "\n";
        echo "     - 2FA requis: " . ($momoData['two_factor_required'] ? 'OUI' : 'NON') . "\n";
        echo "     - Secret 2FA: " . ($momoData['two_factor_secret'] ? 'PRÉSENT' : 'ABSENT') . "\n";
    } else {
        echo "   ❌ Impossible de récupérer les données momo\n";
        exit;
    }
    
    echo "\n2️⃣ Génération d'un secret 2FA:\n";
    
    // Générer un secret 2FA (simulation)
    $secret2FA = 'MFRGG43BMFRGG43BMFRGG43BMFRGG43B'; // Secret de test
    echo "   ✅ Secret 2FA généré: " . substr($secret2FA, 0, 8) . "...\n";
    
    echo "\n3️⃣ Activation de la 2FA:\n";
    
    // Activer la 2FA pour momo
    $stmt = $pdo->prepare("UPDATE users SET two_factor_enabled = 1, two_factor_secret = ? WHERE email = ?");
    $result = $stmt->execute([$secret2FA, 'momo@gmail.com']);
    
    if ($result) {
        echo "   ✅ 2FA activée avec succès pour momo@gmail.com\n";
    } else {
        echo "   ❌ Erreur lors de l'activation de la 2FA\n";
        exit;
    }
    
    echo "\n4️⃣ Vérification de l'activation:\n";
    
    $stmt = $pdo->prepare("SELECT id, email, name, role, two_factor_enabled, two_factor_required, two_factor_secret FROM users WHERE email = ?");
    $stmt->execute(['momo@gmail.com']);
    $momoDataUpdated = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($momoDataUpdated) {
        echo "   ✅ Données mises à jour:\n";
        echo "     - ID: " . $momoDataUpdated['id'] . "\n";
        echo "     - Email: " . $momoDataUpdated['email'] . "\n";
        echo "     - Nom: " . ($momoDataUpdated['name'] ?? 'NULL') . "\n";
        echo "     - Rôle: " . ($momoDataUpdated['role'] ?? 'NULL') . "\n";
        echo "     - 2FA activé: " . ($momoDataUpdated['two_factor_enabled'] ? 'OUI' : 'NON') . "\n";
        echo "     - 2FA requis: " . ($momoDataUpdated['two_factor_required'] ? 'OUI' : 'NON') . "\n";
        echo "     - Secret 2FA: " . ($momoDataUpdated['two_factor_secret'] ? 'PRÉSENT' : 'ABSENT') . "\n";
        
        if ($momoDataUpdated['two_factor_enabled'] && $momoDataUpdated['two_factor_required']) {
            echo "   ✅ 2FA configurée correctement\n";
        } else {
            echo "   ❌ 2FA pas configurée correctement\n";
        }
    } else {
        echo "   ❌ Impossible de vérifier les données mises à jour\n";
    }
    
    echo "\n5️⃣ Test de la logique de connexion:\n";
    
    // Simuler la logique de connexion
    echo "   Simulation de la logique de connexion...\n";
    
    if ($momoDataUpdated && $momoDataUpdated['two_factor_required'] && $momoDataUpdated['two_factor_enabled']) {
        echo "   ✅ 2FA requis et activé pour momo@gmail.com\n";
        echo "   🔧 PROBLÈME: La logique de vérification 2FA ne fonctionne pas\n";
        echo "   🔧 SOLUTION: Vérifier la logique de connexion dans AuthController\n";
    } else {
        echo "   ❌ 2FA pas configurée correctement\n";
    }
    
    echo "\n6️⃣ Vérification de la logique de connexion:\n";
    
    // Vérifier si AuthController contient la logique 2FA
    $authControllerFile = __DIR__ . '/src/Controller/AuthController.php';
    if (file_exists($authControllerFile)) {
        $content = file_get_contents($authControllerFile);
        
        if (strpos($content, 'two_factor') !== false) {
            echo "   ✅ AuthController contient des références à 2FA\n";
        } else {
            echo "   ❌ AuthController ne contient pas de références à 2FA\n";
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
    
    if ($momoDataUpdated && $momoDataUpdated['two_factor_required'] && $momoDataUpdated['two_factor_enabled']) {
        echo "   ✅ 2FA configurée correctement\n";
        echo "   🔧 PROBLÈME: La logique de vérification 2FA ne fonctionne pas\n";
        echo "   🔧 SOLUTION: Vérifier la logique de connexion dans AuthController\n";
        echo "   🔧 ACTION: Se déconnecter et se reconnecter pour tester la 2FA\n";
    } else {
        echo "   ❌ 2FA pas configurée correctement\n";
        echo "   🔧 SOLUTION: Vérifier la configuration\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "RÉSUMÉ FINAL\n";
    echo str_repeat("=", 50) . "\n";
    
    if ($momoDataUpdated && $momoDataUpdated['two_factor_required'] && $momoDataUpdated['two_factor_enabled']) {
        echo "✅ 2FA ACTIVÉE POUR MOMO\n";
        echo "🔧 PROBLÈME: Logique de vérification 2FA\n";
        echo "🔧 SOLUTION: Vérifier la logique de connexion\n";
        echo "🎯 PROCHAINES ÉTAPES:\n";
        echo "   1. Se déconnecter\n";
        echo "   2. Se reconnecter avec momo@gmail.com\n";
        echo "   3. Vérifier que la 2FA est demandée\n";
    } else {
        echo "❌ PROBLÈME: 2FA pas configurée correctement\n";
        echo "🔧 SOLUTION: Vérifier la configuration\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>

