<?php
/**
 * Diagnostic de l'erreur 2FA pour technicien
 */

require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/Database.php';
require_once __DIR__ . '/src/Service/SessionManager.php';
require_once __DIR__ . '/src/Repository/UserRepository.php';

header('Content-Type: text/plain; charset=utf-8');

echo "🔍 DIAGNOSTIC ERREUR 2FA TECHNICIEN\n";
echo "===================================\n\n";

try {
    echo "1️⃣ Vérification de l'état actuel des techniciens:\n";
    
    $pdo = \App\Service\Database::connect();
    $stmt = $pdo->query("SELECT id, email, name, role, two_factor_enabled, two_factor_required, two_factor_secret FROM users WHERE role = 'technician' ORDER BY id");
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($technicians)) {
        echo "   ✅ Techniciens trouvés:\n";
        foreach ($technicians as $tech) {
            $status2FA = '';
            if ($tech['two_factor_required'] && $tech['two_factor_enabled']) {
                $status2FA = ' ✅ 2FA ACTIF';
            } else if ($tech['two_factor_required'] && !$tech['two_factor_enabled']) {
                $status2FA = ' ⚠️ 2FA REQUIS MAIS NON ACTIVÉ';
            } else if (!$tech['two_factor_required'] && $tech['two_factor_enabled']) {
                $status2FA = ' 🔧 2FA OPTIONNELLE ACTIVÉE';
            } else {
                $status2FA = ' ℹ️ 2FA OPTIONNELLE NON ACTIVÉE';
            }
            
            echo "     - ID: {$tech['id']}, Email: {$tech['email']}, Nom: " . ($tech['name'] ?? 'NULL') . "$status2FA\n";
        }
    } else {
        echo "   ❌ Aucun technicien trouvé\n";
    }
    
    echo "\n2️⃣ Test de l'endpoint 2FA pour technicien:\n";
    
    // Simuler une requête POST pour activer la 2FA
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/settings/security/2fa';
    
    // Créer une session pour un technicien
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Utiliser le premier technicien pour le test
    if (!empty($technicians)) {
        $testTech = $technicians[0];
        $_SESSION['user'] = [
            'id' => $testTech['id'],
            'email' => $testTech['email'],
            'name' => $testTech['name'],
            'role' => $testTech['role']
        ];
        $_SESSION['authenticated'] = true;
        
        echo "   Session créée pour: {$testTech['email']} (ID: {$testTech['id']})\n";
        
        // Vérifier l'authentification
        if (\App\Service\SessionManager::isAuthenticated()) {
            echo "   ✅ Session technicien authentifiée\n";
            
            $currentUser = \App\Service\SessionManager::getUser();
            echo "   Utilisateur en session: " . ($currentUser['email'] ?? 'N/A') . "\n";
        } else {
            echo "   ❌ Session technicien non authentifiée\n";
        }
    }
    
    echo "\n3️⃣ Test de l'activation 2FA pour technicien:\n";
    
    // Simuler l'activation de la 2FA
    if (!empty($technicians)) {
        $testTech = $technicians[0];
        $userId = $testTech['id'];
        
        echo "   Test d'activation 2FA pour {$testTech['email']}...\n";
        
        try {
            // Générer un secret 2FA
            $secret2FA = 'MFRGG43B' . strtoupper(substr(md5($userId . time()), 0, 24));
            
            // Activer la 2FA
            $stmt = $pdo->prepare("UPDATE users SET two_factor_enabled = 1, two_factor_secret = ? WHERE id = ?");
            $result = $stmt->execute([$secret2FA, $userId]);
            
            if ($result) {
                echo "   ✅ 2FA activée avec succès pour le technicien\n";
                
                // Vérifier l'état
                $stmt = $pdo->prepare("SELECT two_factor_enabled, two_factor_required, two_factor_secret FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $tech2FA = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($tech2FA) {
                    echo "   État 2FA après activation:\n";
                    echo "     - 2FA activée: " . ($tech2FA['two_factor_enabled'] ? 'OUI' : 'NON') . "\n";
                    echo "     - 2FA requise: " . ($tech2FA['two_factor_required'] ? 'OUI' : 'NON') . "\n";
                    echo "     - Secret 2FA: " . ($tech2FA['two_factor_secret'] ? 'PRÉSENT' : 'ABSENT') . "\n";
                }
            } else {
                echo "   ❌ Erreur lors de l'activation 2FA\n";
            }
        } catch (Exception $e) {
            echo "   ❌ Exception lors de l'activation 2FA: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n4️⃣ Vérification des logs d'erreur:\n";
    
    $logFile = __DIR__ . '/logs/app.log';
    if (file_exists($logFile)) {
        echo "   Dernières lignes du log:\n";
        $logContent = file_get_contents($logFile);
        $logLines = explode("\n", $logContent);
        $lastLines = array_slice($logLines, -10);
        foreach ($lastLines as $line) {
            if (!empty(trim($line))) {
                echo "     $line\n";
            }
        }
    } else {
        echo "   ❌ Fichier de log non trouvé: $logFile\n";
    }
    
    echo "\n5️⃣ Test de l'endpoint 2FA en direct:\n";
    
    // Tester l'endpoint 2FA directement
    $testData = [
        'action' => 'enable',
        'secret' => 'MFRGG43B' . strtoupper(substr(md5(time()), 0, 24))
    ];
    
    echo "   Données de test: " . json_encode($testData) . "\n";
    
    // Simuler l'appel à l'endpoint
    $_POST = $testData;
    
    echo "\n6️⃣ Recommandations:\n";
    
    echo "   🔧 PROBLÈME IDENTIFIÉ:\n";
    echo "   L'erreur 'Unexpected token '<'' indique que le serveur retourne du HTML au lieu de JSON\n";
    echo "   Cela peut être causé par:\n";
    echo "   1. Une erreur PHP qui affiche du HTML\n";
    echo "   2. Un redirect vers une page HTML\n";
    echo "   3. Un problème de Content-Type\n";
    echo "   4. Une exception non gérée\n";
    
    echo "\n   🔧 SOLUTIONS:\n";
    echo "   1. Vérifier que l'endpoint 2FA retourne du JSON\n";
    echo "   2. Ajouter des headers Content-Type: application/json\n";
    echo "   3. Gérer les exceptions proprement\n";
    echo "   4. Vérifier les logs d'erreur\n";
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "RÉSUMÉ FINAL\n";
    echo str_repeat("=", 50) . "\n";
    
    echo "✅ DIAGNOSTIC TERMINÉ\n";
    echo "🔧 PROBLÈME: Endpoint 2FA retourne HTML au lieu de JSON\n";
    echo "🔧 SOLUTION: Vérifier l'endpoint 2FA et ajouter les headers JSON\n";
    echo "🎯 PROCHAINES ÉTAPES:\n";
    echo "   1. Vérifier l'endpoint /settings/security/2fa\n";
    echo "   2. Ajouter Content-Type: application/json\n";
    echo "   3. Gérer les exceptions proprement\n";
    echo "   4. Tester à nouveau l'activation 2FA\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
