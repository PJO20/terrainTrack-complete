<?php

require_once 'vendor/autoload.php';

echo "🔍 Debug du statut 2FA de l'admin\n";
echo "==================================\n\n";

try {
    // Connexion PDO directe
    $host = "localhost";
    $dbname = "exemple";
    $username = "root";
    $password = "root";
    $port = 8889;
    
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "✅ Connexion à la base de données réussie\n\n";
    
    // Rechercher l'utilisateur admin
    $stmt = $pdo->prepare("SELECT id, email, password, name, role, notification_email, two_factor_enabled, two_factor_required FROM users WHERE email = ?");
    $stmt->execute(['momo@gmail.com']);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "❌ Utilisateur momo@gmail.com non trouvé\n";
        exit;
    }
    
    echo "👤 Utilisateur trouvé :\n";
    echo "   ID: {$user['id']}\n";
    echo "   Email: {$user['email']}\n";
    echo "   Nom: {$user['name']}\n";
    echo "   Rôle: {$user['role']}\n";
    echo "   Email notification: " . ($user['notification_email'] ?: 'Non défini') . "\n";
    echo "   2FA activée: " . ($user['two_factor_enabled'] ? 'OUI' : 'NON') . "\n";
    echo "   2FA obligatoire: " . ($user['two_factor_required'] ? 'OUI' : 'NON') . "\n\n";
    
    // Tester les méthodes du TwoFactorService
    $services = require_once 'config/services.php';
    $container = new \App\Container\Container($services);
    $twoFactorService = $container->get(\App\Service\TwoFactorService::class);
    
    echo "🔍 Test des méthodes TwoFactorService :\n";
    echo "======================================\n";
    
    $isRequired = $twoFactorService->isTwoFactorRequired($user['id']);
    $isEnabled = $twoFactorService->isTwoFactorEnabled($user['id']);
    
    echo "   isTwoFactorRequired({$user['id']}): " . ($isRequired ? 'TRUE' : 'FALSE') . "\n";
    echo "   isTwoFactorEnabled({$user['id']}): " . ($isEnabled ? 'TRUE' : 'FALSE') . "\n";
    echo "   Condition 2FA (required OR enabled): " . (($isRequired || $isEnabled) ? 'TRUE' : 'FALSE') . "\n\n";
    
    if ($isRequired || $isEnabled) {
        echo "✅ La 2FA devrait être déclenchée pour cet utilisateur\n";
    } else {
        echo "❌ La 2FA ne sera PAS déclenchée pour cet utilisateur\n";
        echo "💡 Vérifiez les valeurs dans la base de données\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n🏁 Debug terminé\n";

?>
