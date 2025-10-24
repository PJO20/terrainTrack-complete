<?php
/**
 * Debug du problème d'accès aux interventions
 */

require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/Database.php';
require_once __DIR__ . '/src/Service/SessionManager.php';

header('Content-Type: text/plain; charset=utf-8');

echo "🔍 DEBUG ACCÈS INTERVENTIONS\n";
echo "============================\n\n";

try {
    echo "1️⃣ Vérification de l'état de la session:\n";
    
    // Démarrer la session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    echo "   Session ID: " . session_id() . "\n";
    echo "   Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE') . "\n";
    
    // Vérifier l'authentification
    $isAuthenticated = \App\Service\SessionManager::isAuthenticated();
    echo "   Authentifié: " . ($isAuthenticated ? 'OUI' : 'NON') . "\n";
    
    if ($isAuthenticated) {
        $currentUser = \App\Service\SessionManager::getCurrentUser();
        if ($currentUser) {
            echo "   Utilisateur actuel: " . $currentUser['email'] . " (ID: " . $currentUser['id'] . ")\n";
            echo "   Rôle: " . ($currentUser['role'] ?? 'NULL') . "\n";
        }
    }
    
    echo "\n2️⃣ Vérification des permissions admin:\n";
    
    if ($isAuthenticated && isset($currentUser)) {
        $userId = $currentUser['id'];
        $userRole = $currentUser['role'];
        
        echo "   ID utilisateur: $userId\n";
        echo "   Rôle: $userRole\n";
        
        // Vérifier si c'est un admin
        if (in_array($userRole, ['admin', 'super_admin'])) {
            echo "   ✅ Utilisateur a le rôle admin\n";
        } else {
            echo "   ❌ Utilisateur n'a PAS le rôle admin\n";
        }
        
        // Vérifier en base de données
        $pdo = \App\Service\Database::connect();
        $stmt = $pdo->prepare("SELECT id, email, name, role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            echo "   Données en base:\n";
            echo "     - ID: " . $userData['id'] . "\n";
            echo "     - Email: " . $userData['email'] . "\n";
            echo "     - Nom: " . ($userData['name'] ?? 'NULL') . "\n";
            echo "     - Rôle: " . ($userData['role'] ?? 'NULL') . "\n";
            
            if (in_array($userData['role'], ['admin', 'super_admin'])) {
                echo "   ✅ Rôle admin confirmé en base\n";
            } else {
                echo "   ❌ Rôle admin NON confirmé en base\n";
            }
        } else {
            echo "   ❌ Utilisateur non trouvé en base\n";
        }
    } else {
        echo "   ❌ Pas d'utilisateur authentifié\n";
    }
    
    echo "\n3️⃣ Test de l'endpoint intervention/create:\n";
    
    // Simuler une requête GET vers /intervention/create
    $originalRequestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $originalRequestUri = $_SERVER['REQUEST_URI'] ?? '/';
    
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/intervention/create';
    
    echo "   Simulation requête GET /intervention/create\n";
    
    // Capturer la sortie
    ob_start();
    
    try {
        // Inclure les classes nécessaires
        require_once __DIR__ . '/src/Controller/InterventionController.php';
        require_once __DIR__ . '/src/Service/TwigService.php';
        require_once __DIR__ . '/src/Repository/InterventionRepository.php';
        require_once __DIR__ . '/src/Repository/UserRepository.php';
        require_once __DIR__ . '/src/Repository/VehicleRepository.php';
        require_once __DIR__ . '/src/Service/SessionManager.php';
        
        // Créer les instances nécessaires
        $pdo = \App\Service\Database::connect();
        $twigService = new \App\Service\TwigService(\App\Service\SessionManager::class, __DIR__ . '/template');
        $interventionRepository = new \App\Repository\InterventionRepository($pdo);
        $userRepository = new \App\Repository\UserRepository($pdo);
        $vehicleRepository = new \App\Repository\VehicleRepository($pdo);
        
        $interventionController = new \App\Controller\InterventionController(
            $twigService,
            $interventionRepository,
            $userRepository,
            $vehicleRepository
        );
        
        // Appeler la méthode create
        $interventionController->create();
        
    } catch (Exception $e) {
        echo "   ❌ Erreur lors de l'appel InterventionController: " . $e->getMessage() . "\n";
        echo "   Stack trace: " . $e->getTraceAsString() . "\n";
    }
    
    $output = ob_get_clean();
    
    // Restaurer les variables originales
    $_SERVER['REQUEST_METHOD'] = $originalRequestMethod;
    $_SERVER['REQUEST_URI'] = $originalRequestUri;
    
    echo "   Longueur de la sortie: " . strlen($output) . " caractères\n";
    
    if (strlen($output) > 0) {
        echo "   ✅ InterventionController a généré du contenu\n";
        
        // Chercher des indices dans le contenu
        if (strpos($output, 'Accès non autorisé') !== false) {
            echo "   ❌ PROBLÈME: Contenu contient 'Accès non autorisé'\n";
        }
        if (strpos($output, 'permissions nécessaires') !== false) {
            echo "   ❌ PROBLÈME: Contenu contient 'permissions nécessaires'\n";
        }
        if (strpos($output, 'Créer une intervention') !== false) {
            echo "   ✅ Contenu contient 'Créer une intervention'\n";
        }
        
        // Afficher un extrait du contenu
        $extract = substr($output, 0, 500);
        echo "   Extrait du contenu (500 premiers caractères):\n";
        echo "   " . str_replace("\n", "\n   ", $extract) . "\n";
    } else {
        echo "   ❌ InterventionController n'a généré aucun contenu\n";
    }
    
    echo "\n4️⃣ Vérification des logs:\n";
    
    $logFile = __DIR__ . '/logs/app.log';
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        $logLines = explode("\n", $logContent);
        $recentLines = array_slice($logLines, -10); // 10 dernières lignes
        
        echo "   Dernières lignes du log:\n";
        foreach ($recentLines as $line) {
            if (!empty(trim($line))) {
                echo "     " . $line . "\n";
            }
        }
    } else {
        echo "   ❌ Fichier de log non trouvé: $logFile\n";
    }
    
    echo "\n5️⃣ Test de bypass d'authentification:\n";
    
    // Simuler un bypass d'authentification
    echo "   Simulation bypass d'authentification...\n";
    
    // Forcer la création d'une session admin
    $_SESSION['user'] = [
        'id' => 7,
        'email' => 'momo@gmail.com',
        'name' => 'PJ',
        'role' => 'admin'
    ];
    $_SESSION['authenticated'] = true;
    
    echo "   ✅ Session admin forcée\n";
    echo "   Utilisateur: " . $_SESSION['user']['email'] . "\n";
    echo "   Rôle: " . $_SESSION['user']['role'] . "\n";
    
    // Vérifier l'authentification
    $isAuthenticatedNow = \App\Service\SessionManager::isAuthenticated();
    echo "   Authentifié: " . ($isAuthenticatedNow ? 'OUI' : 'NON') . "\n";
    
    if ($isAuthenticatedNow) {
        $currentUserNow = \App\Service\SessionManager::getCurrentUser();
        if ($currentUserNow) {
            echo "   Utilisateur actuel: " . $currentUserNow['email'] . "\n";
            echo "   Rôle: " . $currentUserNow['role'] . "\n";
            
            if (in_array($currentUserNow['role'], ['admin', 'super_admin'])) {
                echo "   ✅ Rôle admin confirmé\n";
            } else {
                echo "   ❌ Rôle admin NON confirmé\n";
            }
        }
    }
    
    echo "\n6️⃣ Recommandations:\n";
    
    if (!$isAuthenticatedNow) {
        echo "   ❌ PROBLÈME: SessionManager ne reconnaît pas la session\n";
        echo "   🔧 SOLUTION: Vérifier la configuration de SessionManager\n";
    } else if (isset($currentUserNow) && in_array($currentUserNow['role'], ['admin', 'super_admin'])) {
        echo "   ✅ Session admin fonctionne\n";
        echo "   🔧 PROBLÈME: InterventionController bloque l'accès\n";
        echo "   🔧 SOLUTION: Vérifier la logique d'autorisation dans InterventionController\n";
    } else {
        echo "   ❌ PROBLÈME: Session créée mais rôle non admin\n";
        echo "   🔧 SOLUTION: Vérifier la logique de SessionManager\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "RÉSUMÉ FINAL\n";
    echo str_repeat("=", 50) . "\n";
    
    if ($isAuthenticatedNow && isset($currentUserNow) && in_array($currentUserNow['role'], ['admin', 'super_admin'])) {
        echo "✅ SESSION ADMIN FONCTIONNE\n";
        echo "🔧 PROBLÈME: InterventionController bloque l'accès\n";
        echo "🔧 SOLUTION: Vérifier la logique d'autorisation\n";
    } else {
        echo "❌ PROBLÈME: SessionManager ou rôle admin\n";
        echo "🔧 SOLUTION: Vérifier la configuration\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
