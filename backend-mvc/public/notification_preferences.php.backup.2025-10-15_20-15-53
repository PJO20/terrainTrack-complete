<?php
/**
 * Page de préférences de notification
 * TerrainTrack - Interface utilisateur
 */

session_start();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: /backend-mvc/public/index.php?page=login');
    exit;
}

// Configuration de la base de données
$host = 'localhost';
$port = '8889';
$dbname = 'exemple';
$username = 'root';
$password = 'root';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Récupérer les informations de l'utilisateur
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception("Utilisateur non trouvé");
    }
    
    // Récupérer les préférences de notification
    $sql = "SELECT * FROM notification_preferences WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    $preferences = $stmt->fetch();
    
    // Si pas de préférences, créer des préférences par défaut
    if (!$preferences) {
        $sql = "INSERT INTO notification_preferences (user_id, email_notifications, sms_notifications, maintenance_reminders, overdue_alerts, intervention_assignments, created_at) 
                VALUES (?, 1, 1, 1, 1, 1, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['user_id']]);
        
        // Récupérer les nouvelles préférences
        $sql = "SELECT * FROM notification_preferences WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['user_id']]);
        $preferences = $stmt->fetch();
    }
    
    // Récupérer l'historique des notifications
    $sql = "SELECT * FROM notification_logs WHERE user_id = ? ORDER BY sent_at DESC LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Erreur : " . $e->getMessage();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_preferences'])) {
    try {
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
        $maintenance_reminders = isset($_POST['maintenance_reminders']) ? 1 : 0;
        $overdue_alerts = isset($_POST['overdue_alerts']) ? 1 : 0;
        $intervention_assignments = isset($_POST['intervention_assignments']) ? 1 : 0;
        
        $sql = "UPDATE notification_preferences SET 
                email_notifications = ?, 
                sms_notifications = ?, 
                maintenance_reminders = ?, 
                overdue_alerts = ?, 
                intervention_assignments = ?,
                updated_at = NOW()
                WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $email_notifications, 
            $sms_notifications, 
            $maintenance_reminders, 
            $overdue_alerts, 
            $intervention_assignments,
            $_SESSION['user_id']
        ]);
        
        $success = "Préférences mises à jour avec succès !";
        
        // Recharger les préférences
        $sql = "SELECT * FROM notification_preferences WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['user_id']]);
        $preferences = $stmt->fetch();
        
    } catch (Exception $e) {
        $error = "Erreur lors de la mise à jour : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Préférences de Notification - TerrainTrack</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
            font-size: 1.1em;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.2);
        }
        
        .checkbox-group label {
            margin: 0;
            font-weight: 500;
            cursor: pointer;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 10px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .notifications-history {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #eee;
        }
        
        .notifications-history h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.5em;
        }
        
        .notification-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #667eea;
        }
        
        .notification-item .type {
            font-weight: 600;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .notification-item .date {
            color: #666;
            font-size: 0.9em;
        }
        
        .notification-item .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 600;
            margin-top: 5px;
        }
        
        .status-sent {
            background: #d4edda;
            color: #155724;
        }
        
        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }
        
        .back-link {
            display: inline-block;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔔 Préférences de Notification</h1>
            <p>Gérez vos préférences de notification TerrainTrack</p>
        </div>
        
        <div class="content">
            <a href="/backend-mvc/public/index.php" class="back-link">← Retour au tableau de bord</a>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>📧 Notifications Email</label>
                    <div class="checkbox-group">
                        <input type="checkbox" id="email_notifications" name="email_notifications" 
                               <?= $preferences['email_notifications'] ? 'checked' : '' ?>>
                        <label for="email_notifications">Recevoir les notifications par email</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>📱 Notifications SMS</label>
                    <div class="checkbox-group">
                        <input type="checkbox" id="sms_notifications" name="sms_notifications" 
                               <?= $preferences['sms_notifications'] ? 'checked' : '' ?>>
                        <label for="sms_notifications">Recevoir les notifications par SMS</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>🔧 Types de Notifications</label>
                    <div class="checkbox-group">
                        <input type="checkbox" id="maintenance_reminders" name="maintenance_reminders" 
                               <?= $preferences['maintenance_reminders'] ? 'checked' : '' ?>>
                        <label for="maintenance_reminders">Rappels d'entretien programmés</label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="overdue_alerts" name="overdue_alerts" 
                               <?= $preferences['overdue_alerts'] ? 'checked' : '' ?>>
                        <label for="overdue_alerts">Alertes d'entretiens en retard</label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="intervention_assignments" name="intervention_assignments" 
                               <?= $preferences['intervention_assignments'] ? 'checked' : '' ?>>
                        <label for="intervention_assignments">Assignations d'interventions</label>
                    </div>
                </div>
                
                <button type="submit" name="update_preferences" class="btn">
                    💾 Sauvegarder les préférences
                </button>
                
                <a href="/backend-mvc/public/index.php" class="btn btn-secondary">
                    🏠 Retour au tableau de bord
                </a>
            </form>
            
            <?php if (!empty($notifications)): ?>
            <div class="notifications-history">
                <h3>📋 Historique des Notifications</h3>
                <?php foreach ($notifications as $notification): ?>
                <div class="notification-item">
                    <div class="type"><?= htmlspecialchars($notification['notification_type']) ?></div>
                    <div class="date"><?= date('d/m/Y H:i', strtotime($notification['sent_at'])) ?></div>
                    <div class="status status-<?= $notification['status'] ?>">
                        <?= $notification['status'] === 'sent' ? '✅ Envoyé' : '❌ Échec' ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>