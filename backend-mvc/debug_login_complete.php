<?php
/**
 * Diagnostic complet du problème de connexion
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔍 Diagnostic complet du problème de connexion\n";
echo "============================================\n\n";

// Configuration directe de la base de données MAMP
$dbHost = 'localhost';
$dbName = 'exemple';
$dbUser = 'root';
$dbPass = 'root';
$dbPort = 8889;

try {
    // Connexion à la base de données
    $db = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    echo "✅ Connexion à la base de données réussie !\n\n";
    
    echo "1. 🔍 Vérification de l'utilisateur momo@gmail.com...\n";
    
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['momo@gmail.com']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "   ✅ Utilisateur trouvé :\n";
        echo "   - ID: {$user['id']}\n";
        echo "   - Nom: {$user['name']}\n";
        echo "   - Email: {$user['email']}\n";
        echo "   - Rôle: {$user['role']}\n";
        echo "   - Actif: " . ($user['is_active'] ? 'Oui' : 'Non') . "\n";
        echo "   - Mot de passe: " . substr($user['password'], 0, 30) . "...\n";
    } else {
        echo "   ❌ Utilisateur momo@gmail.com non trouvé\n";
        exit(1);
    }
    
    echo "\n2. 🔐 Test de tous les mots de passe possibles...\n";
    
    $possiblePasswords = [
        'Ojose28+',
        'momo',
        'Momo',
        'MOMO',
        'admin',
        'Admin',
        'password',
        'Password',
        '123456',
        'admin123'
    ];
    
    $passwordFound = false;
    foreach ($possiblePasswords as $pwd) {
        if (password_verify($pwd, $user['password'])) {
            echo "   ✅ Mot de passe trouvé : '{$pwd}'\n";
            $passwordFound = true;
            break;
        }
    }
    
    if (!$passwordFound) {
        echo "   ❌ Aucun mot de passe ne correspond\n";
        echo "   🔧 Création d'un nouveau mot de passe...\n";
        
        $newPassword = 'momo123';
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
        $result = $stmt->execute([$hashedPassword, 'momo@gmail.com']);
        
        if ($result) {
            echo "   ✅ Nouveau mot de passe créé : 'momo123'\n";
        } else {
            echo "   ❌ Erreur lors de la création du nouveau mot de passe\n";
        }
    }
    
    echo "\n3. 🔧 Test du service Database...\n";
    
    // Tester le service Database
    try {
        require_once __DIR__ . '/src/Service/Database.php';
        $pdo = \App\Service\Database::connect();
        echo "   ✅ Service Database fonctionne\n";
        
        // Tester une requête
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users");
        $stmt->execute();
        $result = $stmt->fetch();
        echo "   ✅ Requête via Database service : {$result['count']} utilisateurs\n";
        
    } catch (Exception $e) {
        echo "   ❌ Erreur avec le service Database : " . $e->getMessage() . "\n";
    }
    
    echo "\n4. 🔧 Test du service EnvService...\n";
    
    try {
        require_once __DIR__ . '/src/Service/EnvService.php';
        \App\Service\EnvService::load();
        echo "   ✅ Service EnvService fonctionne\n";
        
        $dbHost = \App\Service\EnvService::get('DB_HOST', 'localhost');
        $dbName = \App\Service\EnvService::get('DB_NAME', 'exemple');
        echo "   ✅ Variables d'environnement : {$dbHost} / {$dbName}\n";
        
    } catch (Exception $e) {
        echo "   ❌ Erreur avec le service EnvService : " . $e->getMessage() . "\n";
    }
    
    echo "\n5. 🔧 Test du service SessionManager...\n";
    
    try {
        require_once __DIR__ . '/src/Service/SessionManager.php';
        echo "   ✅ Service SessionManager chargé\n";
        
        \App\Service\SessionManager::start();
        echo "   ✅ Session démarrée\n";
        
    } catch (Exception $e) {
        echo "   ❌ Erreur avec le service SessionManager : " . $e->getMessage() . "\n";
    }
    
    echo "\n6. 🔧 Test du service CsrfService...\n";
    
    try {
        require_once __DIR__ . '/src/Service/CsrfService.php';
        $csrf = new \App\Service\CsrfService();
        echo "   ✅ Service CsrfService fonctionne\n";
        
        $token = $csrf->generateToken('login');
        echo "   ✅ Token CSRF généré : " . substr($token, 0, 20) . "...\n";
        
    } catch (Exception $e) {
        echo "   ❌ Erreur avec le service CsrfService : " . $e->getMessage() . "\n";
    }
    
    echo "\n7. 🔧 Test du service RateLimitService...\n";
    
    try {
        require_once __DIR__ . '/src/Service/RateLimitService.php';
        $rateLimit = new \App\Service\RateLimitService();
        echo "   ✅ Service RateLimitService fonctionne\n";
        
        $clientIp = '127.0.0.1';
        $email = 'momo@gmail.com';
        $check = $rateLimit->checkLoginAttempts($clientIp, $email);
        echo "   ✅ Rate limit check : " . ($check['allowed'] ? 'Autorisé' : 'Bloqué') . "\n";
        
    } catch (Exception $e) {
        echo "   ❌ Erreur avec le service RateLimitService : " . $e->getMessage() . "\n";
    }
    
    echo "\n8. 🔧 Test du service ValidationService...\n";
    
    try {
        require_once __DIR__ . '/src/Service/ValidationService.php';
        $validator = new \App\Service\ValidationService();
        echo "   ✅ Service ValidationService fonctionne\n";
        
        $validator->validateEmail('momo@gmail.com', 'email');
        $validator->validateRequired('test', 'password');
        echo "   ✅ Validation des données : " . ($validator->hasErrors() ? 'Erreurs' : 'OK') . "\n";
        
    } catch (Exception $e) {
        echo "   ❌ Erreur avec le service ValidationService : " . $e->getMessage() . "\n";
    }
    
    echo "\n9. 🧪 Test complet de l'authentification...\n";
    
    // Simuler le processus de connexion complet
    $email = 'momo@gmail.com';
    $password = $passwordFound ? $possiblePasswords[0] : 'momo123';
    
    echo "   🔍 Test avec : {$email} / {$password}\n";
    
    // Rechercher l'utilisateur
    $stmt = $db->prepare("SELECT id, email, password, name, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "   ✅ Utilisateur trouvé\n";
        
        // Vérifier le mot de passe
        if (password_verify($password, $user['password'])) {
            echo "   ✅ Mot de passe correct\n";
            
            // Créer la session
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $_SESSION['user'] = [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'role' => $user['role'],
                'is_admin' => ($user['role'] === 'admin')
            ];
            
            $_SESSION['last_activity'] = time();
            $_SESSION['authenticated'] = true;
            
            echo "   ✅ Session créée avec succès\n";
            echo "   ✅ Authentification complète réussie !\n";
            
        } else {
            echo "   ❌ Mot de passe incorrect\n";
        }
    } else {
        echo "   ❌ Utilisateur non trouvé\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ DIAGNOSTIC COMPLET TERMINÉ !\n";
    echo str_repeat("=", 60) . "\n";
    
    echo "\n🔑 Identifiants de test :\n";
    echo "   - Email : momo@gmail.com\n";
    echo "   - Mot de passe : " . ($passwordFound ? $possiblePasswords[0] : 'momo123') . "\n";
    
    echo "\n🚀 Si tout est vert, la connexion devrait fonctionner !\n";
    echo "📱 Essayez de vous connecter sur : http://localhost:8889/exemple/backend-mvc/public/\n";
    
} catch (PDOException $e) {
    echo "❌ ERREUR DE BASE DE DONNÉES : " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ ERREUR : " . $e->getMessage() . "\n";
}
?>
