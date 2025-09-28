<?php
/**
 * Script simple pour tester la sauvegarde des préférences
 * Accessible directement via l'URL
 */

// Démarrer la session
session_start();

// Inclure les dépendances
require_once __DIR__ . '/../vendor/autoload.php';

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
    
    // Simuler les données POST (pour le test)
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
    
    $userId = 7; // ID de momo@gmail.com
    
    echo "<h2>🧪 Test de sauvegarde des préférences</h2>";
    echo "<p><strong>Utilisateur ID:</strong> $userId</p>";
    
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
    
    echo "<h3>📋 Préférences à sauvegarder :</h3>";
    echo "<ul>";
    foreach ($preferences as $key => $value) {
        echo "<li><strong>$key:</strong> " . ($value ? 'Oui' : 'Non') . "</li>";
    }
    echo "</ul>";
    
    $success = $preferencesRepository->save($preferences);
    
    if ($success) {
        echo "<p style='color: green;'>✅ Préférences sauvegardées avec succès !</p>";
    } else {
        echo "<p style='color: red;'>❌ Échec de la sauvegarde des préférences</p>";
    }
    
    // 2. Mettre à jour les informations de contact
    echo "<h3>📞 Mise à jour des informations de contact :</h3>";
    
    $updateData = [];
    
    if (isset($_POST['notification_email']) && !empty($_POST['notification_email'])) {
        $updateData['notification_email'] = $_POST['notification_email'];
        echo "<p>✅ Email de notification : " . $_POST['notification_email'] . "</p>";
    }
    
    if (isset($_POST['phone']) && !empty($_POST['phone'])) {
        $updateData['phone'] = $_POST['phone'];
        echo "<p>✅ Téléphone : " . $_POST['phone'] . "</p>";
    }
    
    if (isset($_POST['notification_sms'])) {
        $updateData['notification_sms'] = isset($_POST['notification_sms']);
        echo "<p>✅ Notification SMS : " . (isset($_POST['notification_sms']) ? 'Oui' : 'Non') . "</p>";
    }
    
    if (!empty($updateData)) {
        $userUpdateSuccess = $userRepository->update($userId, $updateData);
        
        if ($userUpdateSuccess) {
            echo "<p style='color: green;'>✅ Informations de contact mises à jour avec succès !</p>";
        } else {
            echo "<p style='color: red;'>❌ Échec de la mise à jour des informations de contact</p>";
        }
    }
    
    // 3. Vérifier les résultats
    echo "<h3>🔍 Vérification des résultats :</h3>";
    
    $user = $userRepository->findById($userId);
    $updatedPreferences = $preferencesRepository->findByUserId($userId);
    
    echo "<p><strong>Utilisateur après mise à jour :</strong></p>";
    echo "<ul>";
    echo "<li>Email : " . $user->getEmail() . "</li>";
    echo "<li>Notification Email : " . ($user->getNotificationEmail() ?? 'Non défini') . "</li>";
    echo "<li>Téléphone : " . ($user->getPhone() ?? 'Non défini') . "</li>";
    echo "<li>Notification SMS : " . ($user->getNotificationSms() ? 'Oui' : 'Non') . "</li>";
    echo "</ul>";
    
    echo "<p><strong>Préférences après mise à jour :</strong></p>";
    echo "<ul>";
    if ($updatedPreferences) {
        foreach ($updatedPreferences as $key => $value) {
            if (is_bool($value)) {
                echo "<li><strong>$key:</strong> " . ($value ? 'Oui' : 'Non') . "</li>";
            } else {
                echo "<li><strong>$key:</strong> $value</li>";
            }
        }
    }
    echo "</ul>";
    
    echo "<br><br>";
    echo "<a href='/notifications/preferences' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>← Retour aux préférences</a>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Erreur</h2>";
    echo "<p><strong>Message :</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Fichier :</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
    echo "<br><br>";
    echo "<a href='/notifications/preferences' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>← Retour aux préférences</a>";
}
?>
