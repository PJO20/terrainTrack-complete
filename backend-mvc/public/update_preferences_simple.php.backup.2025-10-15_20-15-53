<?php
/**
 * Script simple pour mettre à jour les préférences
 * Récupère les données du formulaire et les sauvegarde
 */

// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated'] || !isset($_SESSION['user'])) {
    header('Location: /login');
    exit;
}

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
    
    $userId = $_SESSION['user']['id'];
    
    // Debug : vérifier que l'utilisateur est bien identifié
    error_log("Mise à jour des préférences pour l'utilisateur ID: $userId (" . $_SESSION['user']['email'] . ")");
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        
        $success = $preferencesRepository->save($preferences);
        
        // 2. Mettre à jour les informations de contact
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
        
        $userUpdateSuccess = true;
        if (!empty($updateData)) {
            $userUpdateSuccess = $userRepository->update($userId, $updateData);
        }
        
        // Rediriger avec un message de succès ou d'erreur
        if ($success && $userUpdateSuccess) {
            header('Location: /notifications/preferences?success=1');
        } else {
            header('Location: /notifications/preferences?error=1');
        }
        exit;
    }
    
    // Si ce n'est pas une requête POST, rediriger vers les préférences
    header('Location: /notifications/preferences');
    exit;
    
} catch (Exception $e) {
    error_log("Erreur lors de la mise à jour des préférences: " . $e->getMessage());
    header('Location: /notifications/preferences?error=1');
    exit;
}
?>
