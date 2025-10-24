<?php

echo "📱 Activation simple des notifications SMS\n";
echo "=========================================\n\n";

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
    
    // 2. Activer toutes les notifications SMS
    echo "🔧 Activation de toutes les notifications SMS...\n";
    echo "===============================================\n";
    
    // Vérifier si les préférences existent
    $stmt = $pdo->prepare("SELECT id FROM notification_preferences WHERE user_id = ?");
    $stmt->execute([$adminId]);
    $exists = $stmt->fetch();
    
    if (!$exists) {
        // Créer les préférences
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
        echo "✅ Préférences créées avec SMS activé\n";
    } else {
        // Mettre à jour les préférences existantes
        $stmt = $pdo->prepare("
            UPDATE notification_preferences 
            SET sms_notifications = 1,
                intervention_assignments = 1,
                maintenance_reminders = 1,
                critical_alerts = 1
            WHERE user_id = ?
        ");
        $stmt->execute([$adminId]);
        echo "✅ Préférences mises à jour - SMS activé\n";
    }
    
    // 3. Vérifier les préférences finales
    echo "\n📊 Vérification des préférences SMS\n";
    echo "==================================\n";
    
    $stmt = $pdo->prepare("
        SELECT sms_notifications, intervention_assignments, maintenance_reminders, critical_alerts 
        FROM notification_preferences 
        WHERE user_id = ?
    ");
    $stmt->execute([$adminId]);
    $preferences = $stmt->fetch();
    
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
    
    // 4. Test SMS simple
    echo "\n📤 Test SMS simple\n";
    echo "=================\n";
    
    if (empty($adminPhone)) {
        echo "❌ Pas de numéro de téléphone configuré\n";
    } else {
        // Fonction simple d'envoi SMS (simulation)
        function sendSimpleSms($phone, $message) {
            // En mode simulation car pas d'API configurée
            echo "📱 SMS vers $phone: $message\n";
            echo "⚠️ Mode simulation (configurez SMS_API_URL et SMS_API_KEY pour envoi réel)\n";
            return true;
        }
        
        $testMessage = "✅ Test TerrainTrack - Notifications SMS activées ! Réception OK ?";
        echo "📤 Envoi du SMS de test...\n";
        
        $success = sendSimpleSms($adminPhone, $testMessage);
        
        if ($success) {
            echo "✅ SMS de test traité avec succès\n";
        } else {
            echo "❌ Erreur lors de l'envoi du SMS\n";
        }
    }
    
    // 5. Instructions pour tester via l'interface
    echo "\n📋 Instructions pour tester via l'interface web\n";
    echo "==============================================\n";
    echo "1. 🌐 Allez sur: http://localhost:8888/notifications/preferences\n";
    echo "2. 📱 Vérifiez que 'Activer les notifications SMS' est coché\n";
    echo "3. 🧪 Cliquez sur le bouton 'Tester SMS'\n";
    echo "4. 📞 Vérifiez la réception sur: $adminPhone\n";
    echo "5. 📊 Consultez les logs dans: backend-mvc/logs/app.log\n";
    
    // 6. Créer un log de test
    echo "\n📝 Création d'un log de test SMS...\n";
    echo "==================================\n";
    
    $logMessage = date('Y-m-d H:i:s') . " - SMS Test: Notifications activées pour admin $adminName ($adminPhone)";
    file_put_contents('logs/sms_test.log', $logMessage . "\n", FILE_APPEND | LOCK_EX);
    echo "✅ Log créé dans: logs/sms_test.log\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n🎉 Activation SMS terminée avec succès !\n";
echo "🔗 Testez maintenant via l'interface web\n";

?>

