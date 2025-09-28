<?php
/**
 * Debug du formulaire de préférences
 */

echo "🔍 Debug du formulaire de préférences\n";
echo "===================================\n\n";

// Démarrer la session
session_start();

// Simuler une session utilisateur
$_SESSION['user_id'] = 7;
$_SESSION['user_email'] = 'momo@gmail.com';
$_SESSION['user_name'] = 'Momo';
$_SESSION['user_role'] = 'admin';

echo "✅ Session simulée pour momo@gmail.com\n";

// Simuler les données POST du formulaire
$_POST = [
    'notification_email' => 'pjorsini20@gmail.com',
    'email_notifications' => '1',
    'sms_notifications' => '0',
    'intervention_assignments' => '1',
    'maintenance_reminders' => '1',
    'critical_alerts' => '1',
    'reminder_frequency_days' => '7',
    'phone' => '+33 6 12 34 56 78',
    'notification_sms' => '0'
];

echo "📋 Données POST simulées:\n";
foreach ($_POST as $key => $value) {
    echo "  - $key: $value\n";
}

// Inclure les dépendances
require_once __DIR__ . '/vendor/autoload.php';

try {
    // Créer les services nécessaires
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
    
    // Créer les repositories
    $userRepository = new \App\Repository\UserRepository($pdo);
    $preferencesRepository = new \App\Repository\NotificationPreferencesRepository($pdo);
    
    echo "\n✅ Services créés avec succès\n";
    
    // Simuler la logique du contrôleur
    $userId = 7;
    
    echo "\n📝 Test de la logique de sauvegarde...\n";
    
    // 1. Sauvegarder les préférences
    $preferences = [
        'user_id' => $userId,
        'email_notifications' => isset($_POST['email_notifications']),
        'sms_notifications' => isset($_POST['sms_notifications']),
        'intervention_assignments' => isset($_POST['intervention_assignments']),
        'maintenance_reminders' => isset($_POST['maintenance_reminders']),
        'critical_alerts' => isset($_POST['critical_alerts']),
        'reminder_frequency_days' => (int)($_POST['reminder_frequency_days'] ?? 7)
    ];
    
    echo "📋 Préférences à sauvegarder:\n";
    foreach ($preferences as $key => $value) {
        echo "  - $key: " . ($value ? 'Oui' : 'Non') . "\n";
    }
    
    $success = $preferencesRepository->save($preferences);
    
    if ($success) {
        echo "✅ Préférences sauvegardées avec succès !\n";
    } else {
        echo "❌ Échec de la sauvegarde des préférences\n";
    }
    
    // 2. Mettre à jour les informations de contact
    echo "\n📝 Test de la mise à jour des informations de contact...\n";
    
    $updateData = [];
    
    if (isset($_POST['notification_email']) && !empty($_POST['notification_email'])) {
        $updateData['notification_email'] = $_POST['notification_email'];
        echo "✅ Email de notification à mettre à jour: " . $_POST['notification_email'] . "\n";
    }
    
    if (isset($_POST['phone']) && !empty($_POST['phone'])) {
        $updateData['phone'] = $_POST['phone'];
        echo "✅ Téléphone à mettre à jour: " . $_POST['phone'] . "\n";
    }
    
    if (isset($_POST['notification_sms'])) {
        $updateData['notification_sms'] = isset($_POST['notification_sms']);
        echo "✅ Notification SMS à mettre à jour: " . (isset($_POST['notification_sms']) ? 'Oui' : 'Non') . "\n";
    }
    
    if (!empty($updateData)) {
        echo "📋 Données à mettre à jour dans la table users:\n";
        foreach ($updateData as $key => $value) {
            echo "  - $key: $value\n";
        }
        
        $userUpdateSuccess = $userRepository->update($userId, $updateData);
        
        if ($userUpdateSuccess) {
            echo "✅ Informations de contact mises à jour avec succès !\n";
        } else {
            echo "❌ Échec de la mise à jour des informations de contact\n";
        }
    } else {
        echo "⚠️ Aucune information de contact à mettre à jour\n";
    }
    
    // 3. Vérifier les résultats
    echo "\n🔍 Vérification des résultats...\n";
    
    $user = $userRepository->findById($userId);
    $updatedPreferences = $preferencesRepository->findByUserId($userId);
    
    echo "👤 Utilisateur après mise à jour:\n";
    echo "  - Email: " . $user->getEmail() . "\n";
    echo "  - Notification Email: " . ($user->getNotificationEmail() ?? 'Non défini') . "\n";
    echo "  - Téléphone: " . ($user->getPhone() ?? 'Non défini') . "\n";
    echo "  - Notification SMS: " . ($user->getNotificationSms() ? 'Oui' : 'Non') . "\n";
    
    echo "\n📋 Préférences après mise à jour:\n";
    if ($updatedPreferences) {
        foreach ($updatedPreferences as $key => $value) {
            if (is_bool($value)) {
                echo "  - $key: " . ($value ? 'Oui' : 'Non') . "\n";
            } else {
                echo "  - $key: $value\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "📍 Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n🔍 Test terminé !\n";
?>
