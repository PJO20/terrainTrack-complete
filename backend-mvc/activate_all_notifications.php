<?php
/**
 * Script pour activer les notifications pour tous les utilisateurs
 * TerrainTrack - Activation des notifications
 */

echo "🔔 Activation des notifications pour tous les utilisateurs\n";
echo "==================================================\n\n";

// Configuration de la base de données
$host = 'localhost';
$port = '8889';
$dbname = 'exemple';
$username = 'root';
$password = EnvService::get('DB_PASS', 'root');

try {
    // Connexion à la base de données
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Connexion à la base de données réussie !\n\n";
    
    // 1. Activer les notifications email pour tous les utilisateurs
    echo "1. 📧 Activation des notifications email...\n";
    $sql = "UPDATE users SET notification_email = 1 WHERE is_active = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $affected = $stmt->rowCount();
    echo "   ✅ $affected utilisateurs activés pour les emails\n";
    
    // 2. Activer les notifications SMS pour tous les utilisateurs
    echo "\n2. 📱 Activation des notifications SMS...\n";
    $sql = "UPDATE users SET notification_sms = 1 WHERE is_active = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $affected = $stmt->rowCount();
    echo "   ✅ $affected utilisateurs activés pour les SMS\n";
    
    // 3. Vérifier les utilisateurs avec notifications
    echo "\n3. 👥 Vérification des utilisateurs...\n";
    $sql = "SELECT id, name, email, notification_email, notification_sms FROM users WHERE is_active = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "   - Utilisateurs trouvés : " . count($users) . "\n";
    foreach ($users as $user) {
        $emailStatus = $user['notification_email'] ? '✅' : '❌';
        $smsStatus = $user['notification_sms'] ? '✅' : '❌';
        echo "   - {$user['name']} ({$user['email']}) - Email: $emailStatus SMS: $smsStatus\n";
    }
    
    // 4. Tester le script de rappels maintenant
    echo "\n4. 🧪 Test du script de rappels...\n";
    echo "   Exécutez maintenant : php reminder_cron_simple.php\n";
    
    echo "\n🎉 ACTIVATION TERMINÉE !\n";
    echo "==================================================\n";
    echo "✅ Tous les utilisateurs ont maintenant les notifications activées\n";
    echo "✅ Vous pouvez maintenant configurer le cron\n";
    echo "✅ Les rappels automatiques fonctionneront\n";
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}

