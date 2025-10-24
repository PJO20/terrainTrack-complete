<?php
/**
 * Script pour déboguer l'objet user dans le template
 */

session_start();

// Simuler une session utilisateur
$_SESSION['authenticated'] = 1;
$_SESSION['user'] = ['id' => 7, 'email' => 'momo@gmail.com', 'name' => 'Momo'];

echo "🔍 Debug de l'objet user dans le contrôleur\n";
echo "==========================================\n\n";

// Inclure les dépendances
require_once __DIR__ . '/vendor/autoload.php';

try {
    // Configuration directe de la base de données
    $host = 'localhost';
    $port = '8889';
    $dbname = 'exemple';
    $username = 'root';
    $password = 'root';
    
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Simuler exactement ce que fait le contrôleur
    $sessionManager = new \App\Service\SessionManager();
    $userRepository = new \App\Repository\UserRepository($pdo);
    
    echo "1. 📋 Session utilisateur :\n";
    $currentUser = $sessionManager->getCurrentUser();
    echo "  - Session ID: " . $currentUser['id'] . "\n";
    echo "  - Session Email: " . $currentUser['email'] . "\n";
    
    echo "\n2. 👤 UserRepository->findById(" . $currentUser['id'] . ") :\n";
    $user = $userRepository->findById($currentUser['id']);
    
    if ($user) {
        echo "  ✅ Utilisateur trouvé :\n";
        echo "    - ID: " . $user->getId() . "\n";
        echo "    - Email: " . $user->getEmail() . "\n";
        echo "    - Notification Email: " . ($user->getNotificationEmail() ?? 'NULL') . "\n";
        
        // Test de la méthode directement
        echo "\n3. 🧪 Test des méthodes :\n";
        echo "    - user->getEmail(): " . $user->getEmail() . "\n";
        echo "    - user->getNotificationEmail(): " . ($user->getNotificationEmail() ?? 'NULL') . "\n";
        
        // Test de la logique Twig
        echo "\n4. 🎨 Simulation logique Twig :\n";
        $notificationEmail = $user->getNotificationEmail() ?? $user->getEmail();
        echo "    - Résultat: " . $notificationEmail . "\n";
        
        // Vérifier que l'objet a bien toutes les propriétés
        echo "\n5. 🔍 Propriétés de l'objet User :\n";
        $reflection = new ReflectionClass($user);
        $properties = $reflection->getProperties();
        
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($user);
            echo "    - " . $property->getName() . ": " . ($value ?? 'NULL') . "\n";
        }
        
    } else {
        echo "  ❌ Utilisateur non trouvé\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "📍 Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n🎯 Maintenant testez sur http://localhost:8888/notifications/preferences\n";
?>


