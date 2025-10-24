<?php

require_once 'vendor/autoload.php';

use App\Repository\UserRepository;
use App\Repository\NotificationLogsRepository;
use App\Repository\NotificationPreferencesRepository;
use App\Service\SmsNotificationService;

echo "🔧 Activation des notifications SMS\n";
echo "===================================\n\n";

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
    
    // 1. Vérifier les préférences SMS actuelles
    echo "📋 Vérification des préférences SMS actuelles\n";
    echo "=============================================\n";
    
    $stmt = $pdo->query("
        SELECT np.*, u.name, u.phone 
        FROM notification_preferences np 
        JOIN users u ON np.user_id = u.id 
        WHERE u.role = 'admin'
    ");
    $preferences = $stmt->fetchAll();
    
    if (empty($preferences)) {
        echo "❌ Aucune préférence trouvée pour l'admin\n";
        
        // Créer des préférences par défaut
        echo "🔧 Création des préférences par défaut...\n";
        $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        $admin = $stmt->fetch();
        
        if ($admin) {
            $adminId = $admin['id'];
            
            // Insérer les préférences SMS
            $smsPreferences = [
                'sms_notifications' => 1,
                'sms_intervention_assignments' => 1,
                'sms_maintenance_reminders' => 1,
                'sms_critical_alerts' => 1
            ];
            
            foreach ($smsPreferences as $preference => $value) {
                $stmt = $pdo->prepare("
                    INSERT INTO notification_preferences (user_id, preference_name, preference_value) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value)
                ");
                $stmt->execute([$adminId, $preference, $value]);
                echo "✅ Préférence $preference activée\n";
            }
        }
    } else {
        echo "📊 Préférences actuelles :\n";
        foreach ($preferences as $pref) {
            echo "   - {$pref['preference_name']}: {$pref['preference_value']}\n";
        }
    }
    
    // 2. Activer spécifiquement "Activer les notifications SMS"
    echo "\n🔧 Activation de la notification SMS principale...\n";
    echo "=================================================\n";
    
    $stmt = $pdo->query("SELECT id, phone FROM users WHERE role = 'admin' LIMIT 1");
    $admin = $stmt->fetch();
    
    if ($admin) {
        $adminId = $admin['id'];
        $phone = $admin['phone'];
        
        echo "👤 Admin ID: $adminId\n";
        echo "📱 Téléphone: $phone\n";
        
        // Activer les notifications SMS principales
        $stmt = $pdo->prepare("
            INSERT INTO notification_preferences (user_id, preference_name, preference_value) 
            VALUES (?, 'sms_notifications', 1) 
            ON DUPLICATE KEY UPDATE preference_value = 1
        ");
        $stmt->execute([$adminId]);
        echo "✅ Notifications SMS principales activées\n";
        
        // 3. Tester l'envoi SMS
        echo "\n📤 Test d'envoi SMS...\n";
        echo "=====================\n";
        
        // Créer les services
        $userRepository = new UserRepository();
        $notificationLogsRepository = new NotificationLogsRepository();
        $smsService = new SmsNotificationService($userRepository, $notificationLogsRepository);
        
        // Vérifier la configuration SMS
        $smsConfig = $smsService->testSmsConfiguration();
        echo "📋 Configuration SMS :\n";
        foreach ($smsConfig as $key => $value) {
            echo "   - $key: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
        }
        
        if (!empty($phone)) {
            echo "\n📱 Envoi du SMS de test vers $phone...\n";
            $success = $smsService->sendTestSms($phone, "✅ Test TerrainTrack - Notifications SMS activées !");
            
            if ($success) {
                echo "✅ SMS de test envoyé avec succès !\n";
            } else {
                echo "⚠️ SMS en mode simulation (pas de configuration API réelle)\n";
            }
        } else {
            echo "❌ Pas de numéro de téléphone configuré pour l'admin\n";
        }
    }
    
    // 4. Vérifier les préférences finales
    echo "\n📊 Vérification des préférences finales\n";
    echo "======================================\n";
    
    $stmt = $pdo->query("
        SELECT preference_name, preference_value 
        FROM notification_preferences 
        WHERE user_id = (SELECT id FROM users WHERE role = 'admin' LIMIT 1)
        AND preference_name LIKE '%sms%'
        ORDER BY preference_name
    ");
    $finalPrefs = $stmt->fetchAll();
    
    foreach ($finalPrefs as $pref) {
        $status = $pref['preference_value'] ? '✅ Activé' : '❌ Désactivé';
        echo "   - {$pref['preference_name']}: $status\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n🏁 Activation terminée\n";

?>

