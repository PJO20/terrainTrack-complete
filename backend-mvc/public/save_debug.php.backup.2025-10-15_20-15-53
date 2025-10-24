<?php
/**
 * Script de debug pour voir exactement ce qui est envoyé
 */

// Démarrer la session
session_start();

echo "<h2>🔍 Debug de la sauvegarde</h2>";

// Vérifier la session
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated'] || !isset($_SESSION['user'])) {
    echo "<p style='color: red;'>❌ Vous n'êtes pas connecté</p>";
    exit;
}

echo "<p style='color: green;'>✅ Connecté en tant que : " . $_SESSION['user']['name'] . " (" . $_SESSION['user']['email'] . ")</p>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>📋 Données POST reçues :</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<h3>📊 Analyse des champs :</h3>";
    echo "<ul>";
    echo "<li>notification_email : " . ($_POST['notification_email'] ?? 'Non défini') . "</li>";
    echo "<li>email_notifications : " . (isset($_POST['email_notifications']) ? 'Coché' : 'Non coché') . "</li>";
    echo "<li>intervention_assignments : " . (isset($_POST['intervention_assignments']) ? 'Coché' : 'Non coché') . "</li>";
    echo "<li>maintenance_reminders : " . (isset($_POST['maintenance_reminders']) ? 'Coché' : 'Non coché') . "</li>";
    echo "<li>critical_alerts : " . (isset($_POST['critical_alerts']) ? 'Coché' : 'Non coché') . "</li>";
    echo "<li>sms_notifications : " . (isset($_POST['sms_notifications']) ? 'Coché' : 'Non coché') . "</li>";
    echo "<li>phone : " . ($_POST['phone'] ?? 'Non défini') . "</li>";
    echo "</ul>";
    
    // Maintenant essayer de sauvegarder
    require_once __DIR__ . '/../vendor/autoload.php';
    
    try {
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
        
        $userRepository = new \App\Repository\UserRepository($pdo);
        $preferencesRepository = new \App\Repository\NotificationPreferencesRepository($pdo);
        
        $userId = $_SESSION['user']['id'];
        
        echo "<h3>💾 Tentative de sauvegarde :</h3>";
        
        $preferences = [
            'user_id' => $userId,
            'email_notifications' => isset($_POST['email_notifications']),
            'sms_notifications' => isset($_POST['sms_notifications']),
            'intervention_assignments' => isset($_POST['intervention_assignments']),
            'maintenance_reminders' => isset($_POST['maintenance_reminders']),
            'critical_alerts' => isset($_POST['critical_alerts']),
            'reminder_frequency_days' => (int)($_POST['reminder_frequency_days'] ?? 7)
        ];
        
        echo "<p>Préférences à sauvegarder :</p>";
        echo "<pre>";
        print_r($preferences);
        echo "</pre>";
        
        $success = $preferencesRepository->save($preferences);
        
        if ($success) {
            echo "<p style='color: green;'>✅ Préférences sauvegardées avec succès !</p>";
            
            // Maintenant sauvegarder les informations de contact
            echo "<h3>📞 Sauvegarde des informations de contact :</h3>";
            
            $updateData = [];
            if (isset($_POST['notification_email']) && !empty($_POST['notification_email'])) {
                $updateData['notification_email'] = $_POST['notification_email'];
            }
            if (isset($_POST['phone']) && !empty($_POST['phone'])) {
                $updateData['phone'] = $_POST['phone'];
            }
            if (isset($_POST['notification_sms'])) {
                $updateData['notification_sms'] = isset($_POST['notification_sms']);
            }
            
            if (!empty($updateData)) {
                echo "<p>Données à mettre à jour :</p>";
                echo "<pre>";
                print_r($updateData);
                echo "</pre>";
                
                $userUpdateSuccess = $userRepository->update($userId, $updateData);
                
                if ($userUpdateSuccess) {
                    echo "<p style='color: green;'>✅ Informations de contact mises à jour !</p>";
                } else {
                    echo "<p style='color: red;'>❌ Échec de la mise à jour des informations de contact</p>";
                }
            } else {
                echo "<p>Aucune information de contact à mettre à jour</p>";
            }
            
            // Redirection après 3 secondes
            echo "<p>Redirection dans 3 secondes...</p>";
            echo '<script>setTimeout(function(){ window.location.href = "/notifications/preferences?success=1"; }, 3000);</script>';
        } else {
            echo "<p style='color: red;'>❌ Échec de la sauvegarde</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erreur : " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p>Ce script doit être appelé en POST</p>";
}

echo "<br><br><a href='/notifications/preferences'>← Retour aux préférences</a>";
?>
