<?php
/**
 * Script ultra-simple pour sauvegarder les préférences
 */

// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated'] || !isset($_SESSION['user'])) {
    echo "❌ Vous devez être connecté";
    exit;
}

// Si ce n'est pas une requête POST, afficher un message
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "❌ Méthode non autorisée";
    exit;
}

// Inclure les dépendances
require_once __DIR__ . '/../vendor/autoload.php';

try {
    // Connexion à la base de données
    $host = 'localhost';
    $port = '8889';
    $dbname = 'exemple';
    $username = 'root';
    // Charger EnvService si disponible
    if (!class_exists('App\Service\EnvService')) {
        require_once __DIR__ . '/../src/Service/EnvService.php';
    }
    $password = \App\Service\EnvService::get('DB_PASS', 'root');
    
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Créer les repositories
    $userRepository = new \App\Repository\UserRepository($pdo);
    $preferencesRepository = new \App\Repository\NotificationPreferencesRepository($pdo);
    
    $userId = $_SESSION['user']['id'];
    
    // Sauvegarder les préférences
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
    
    // Mettre à jour les informations de contact
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
    
    if ($success && $userUpdateSuccess) {
        // Redirection JavaScript pour éviter les problèmes de headers
        echo '<script>window.location.href = "/notifications/preferences?success=1";</script>';
        echo '<meta http-equiv="refresh" content="0;url=/notifications/preferences?success=1">';
        echo '✅ Préférences sauvegardées ! Redirection...';
    } else {
        echo '<script>window.location.href = "/notifications/preferences?error=1";</script>';
        echo '<meta http-equiv="refresh" content="0;url=/notifications/preferences?error=1">';
        echo '❌ Erreur lors de la sauvegarde ! Redirection...';
    }
    
} catch (Exception $e) {
    echo '<script>window.location.href = "/notifications/preferences?error=1";</script>';
    echo '<meta http-equiv="refresh" content="0;url=/notifications/preferences?error=1">';
    echo '❌ Erreur : ' . $e->getMessage();
}
?>
