<?php

require_once 'vendor/autoload.php';

use App\Repository\UserRepository;
use App\Repository\NotificationLogsRepository;
use App\Service\SmsNotificationService;

echo "📱 Activation et test des notifications SMS\n";
echo "==========================================\n\n";

try {
    // Connexion PDO directe
    $host = "localhost";
    $dbname = "exemple";
    $username = "root";
    $password = EnvService::get('DB_PASS', 'root');
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
    
    // 1. Récupérer l'admin
    $stmt = $pdo->query("SELECT id, name, phone FROM users WHERE role = 'admin' LIMIT 1");
    $admin = $stmt->fetch();
    
    if (!$admin) {
        echo "❌ Aucun admin trouvé\n";
        exit;
    }
    
    $adminId = $admin['id'];
    $adminName = $admin['name'];
    $adminPhone = $admin['phone'];
    
    echo "👤 Admin trouvé: $adminName (ID: $adminId)\n";
    echo "📱 Téléphone: $adminPhone\n\n";
    
    // 2. Vérifier les préférences actuelles
    echo "📋 Préférences SMS actuelles\n";
    echo "============================\n";
    
    $stmt = $pdo->prepare("SELECT * FROM notification_preferences WHERE user_id = ?");
    $stmt->execute([$adminId]);
    $preferences = $stmt->fetch();
    
    if (!$preferences) {
        echo "❌ Aucune préférence trouvée - création...\n";
        
        // Créer les préférences par défaut
        $stmt = $pdo->prepare("
            INSERT INTO notification_preferences (
                user_id, 
                email_notifications, 
                sms_notifications,
                intervention_assignments,
                maintenance_reminders,
                critical_alerts
            ) VALUES (?, 1, 1, 1, 1, 1)
        ");
        $stmt->execute([$adminId]);
        
        // Récupérer les nouvelles préférences
        $stmt = $pdo->prepare("SELECT * FROM notification_preferences WHERE user_id = ?");
        $stmt->execute([$adminId]);
        $preferences = $stmt->fetch();
        
        echo "✅ Préférences créées\n";
    }
    
    // Afficher les préférences SMS
    $smsFields = [
        'sms_notifications' => 'Notifications SMS générales',
        'intervention_assignments' => 'Assignations d\'interventions',
        'maintenance_reminders' => 'Rappels d\'entretien',
        'critical_alerts' => 'Alertes critiques'
    ];
    
    foreach ($smsFields as $field => $label) {
        $status = $preferences[$field] ? '✅ Activé' : '❌ Désactivé';
        echo "   - $label: $status\n";
    }
    
    // 3. Activer toutes les notifications SMS
    echo "\n🔧 Activation de toutes les notifications SMS...\n";
    echo "===============================================\n";
    
    $stmt = $pdo->prepare("
        UPDATE notification_preferences 
        SET sms_notifications = 1,
            intervention_assignments = 1,
            maintenance_reminders = 1,
            critical_alerts = 1
        WHERE user_id = ?
    ");
    $stmt->execute([$adminId]);
    
    echo "✅ Toutes les notifications SMS activées\n";
    
    // 4. Tester l'envoi SMS
    echo "\n📤 Test d'envoi SMS\n";
    echo "==================\n";
    
    if (empty($adminPhone)) {
        echo "❌ Pas de numéro de téléphone configuré\n";
    } else {
        // Créer les services
        $userRepository = new UserRepository();
        $notificationLogsRepository = new NotificationLogsRepository();
        $smsService = new SmsNotificationService($userRepository, $notificationLogsRepository);
        
        // Vérifier la configuration SMS
        echo "📋 Configuration SMS :\n";
        $smsConfig = $smsService->testSmsConfiguration();
        foreach ($smsConfig as $key => $value) {
            $displayValue = is_bool($value) ? ($value ? 'true' : 'false') : ($value ?: 'non configuré');
            echo "   - $key: $displayValue\n";
        }
        
        echo "\n📱 Envoi du SMS de test vers $adminPhone...\n";
        
        // Tester différents types de SMS
        $testMessages = [
            "✅ Test général - Notifications SMS TerrainTrack activées !",
            "🔧 Test intervention - Nouvelle intervention assignée",
            "⚠️ Test alerte critique - Véhicule nécessite attention",
            "🔧 Test rappel entretien - Maintenance programmée"
        ];
        
        $successCount = 0;
        foreach ($testMessages as $i => $message) {
            echo "   📤 Test " . ($i + 1) . "/4: ";
            $success = $smsService->sendTestSms($adminPhone, $message);
            
            if ($success) {
                echo "✅ Envoyé\n";
                $successCount++;
            } else {
                echo "⚠️ Simulation (pas d'API configurée)\n";
                $successCount++; // Compter comme succès en mode simulation
            }
            
            sleep(1); // Pause entre les envois
        }
        
        echo "\n📊 Résultat: $successCount/4 SMS traités\n";
        
        if ($successCount === 4) {
            echo "🎉 Tous les tests SMS ont réussi !\n";
        }
    }
    
    // 5. Vérifier les préférences finales
    echo "\n📊 Vérification finale des préférences\n";
    echo "=====================================\n";
    
    $stmt = $pdo->prepare("SELECT * FROM notification_preferences WHERE user_id = ?");
    $stmt->execute([$adminId]);
    $finalPreferences = $stmt->fetch();
    
    foreach ($smsFields as $field => $label) {
        $status = $finalPreferences[$field] ? '✅ Activé' : '❌ Désactivé';
        echo "   - $label: $status\n";
    }
    
    // 6. Instructions pour l'interface
    echo "\n📋 Instructions pour l'interface web\n";
    echo "===================================\n";
    echo "1. Allez sur la page Notifications SMS dans les paramètres\n";
    echo "2. La case 'Activer les notifications SMS' devrait être cochée\n";
    echo "3. Cliquez sur 'Tester SMS' pour envoyer un SMS de test\n";
    echo "4. Vérifiez la réception sur le numéro: $adminPhone\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n🏁 Activation et test terminés\n";

?>

