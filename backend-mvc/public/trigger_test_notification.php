<?php
/**
 * Script pour déclencher des notifications de test avec sons
 */

// Charger les dépendances
require_once __DIR__ . '/../src/Service/EnvService.php';
require_once __DIR__ . '/../src/Service/Database.php';
require_once __DIR__ . '/../src/Service/SessionManager.php';

// Démarrer la session
session_start();

// Vérifier la connexion
if (!SessionManager::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

// Récupérer les paramètres
$type = $_GET['type'] ?? 'info';
$title = $_GET['title'] ?? 'Notification de test';
$message = $_GET['message'] ?? 'Ceci est une notification de test';

// Valider le type
$allowedTypes = ['info', 'warning', 'success', 'error'];
if (!in_array($type, $allowedTypes)) {
    $type = 'info';
}

try {
    // Connexion à la base de données
    $pdo = \App\Service\Database::connect();
    
    // Récupérer l'utilisateur actuel
    $currentUser = SessionManager::getCurrentUser();
    $userId = $currentUser['id'];
    
    // Insérer la notification dans la base de données
    $stmt = $pdo->prepare("
        INSERT INTO notifications (title, description, type, type_class, icon, recipient_id, priority, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $typeClass = $type;
    $icon = 'bx-info-circle';
    $priority = 'medium';
    
    switch ($type) {
        case 'warning':
            $icon = 'bx-error';
            $priority = 'high';
            break;
        case 'success':
            $icon = 'bx-check-circle';
            $priority = 'low';
            break;
        case 'error':
            $icon = 'bx-x-circle';
            $priority = 'critical';
            break;
    }
    
    $stmt->execute([
        $title,
        $message,
        ucfirst($type),
        $typeClass,
        $icon,
        $userId,
        $priority
    ]);
    
    $notificationId = $pdo->lastInsertId();
    
    // Log de l'action
    error_log("Notification de test créée: ID $notificationId, Type: $type, User: $userId");
    
    echo json_encode([
        'success' => true,
        'message' => 'Notification créée avec succès',
        'notification' => [
            'id' => $notificationId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Erreur lors de la création de la notification: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la création de la notification: ' . $e->getMessage()
    ]);
}
?>

