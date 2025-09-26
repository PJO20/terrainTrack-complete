<?php
/**
 * Interface utilisateur pour les préférences de notification
 */

session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Charger l'autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Configuration directe de la base de données MAMP
$dbHost = 'localhost';
$dbName = 'exemple';
$dbUser = 'root';
$dbPass = 'root';
$dbPort = 8889;

try {
    // Connexion à la base de données
    $db = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    // Récupérer les informations de l'utilisateur
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        die("Utilisateur non trouvé");
    }
    
    // Récupérer les préférences de notification
    $stmt = $db->prepare("SELECT * FROM notification_preferences WHERE user_id = ?");
    $stmt->execute([$userId]);
    $preferences = $stmt->fetch();
    
    // Si pas de préférences, créer des préférences par défaut
    if (!$preferences) {
        $stmt = $db->prepare("
            INSERT INTO notification_preferences 
            (user_id, email_notifications, sms_notifications, intervention_assignments, 
             maintenance_reminders, critical_alerts, reminder_frequency_days, created_at, updated_at)
            VALUES (?, 1, 0, 1, 1, 1, 7, NOW(), NOW())
        ");
        $stmt->execute([$userId]);
        
        $preferences = [
            'user_id' => $userId,
            'email_notifications' => 1,
            'sms_notifications' => 0,
            'intervention_assignments' => 1,
            'maintenance_reminders' => 1,
            'critical_alerts' => 1,
            'reminder_frequency_days' => 7
        ];
    }
    
    // Traitement du formulaire
    if ($_POST) {
        $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
        $smsNotifications = isset($_POST['sms_notifications']) ? 1 : 0;
        $interventionAssignments = isset($_POST['intervention_assignments']) ? 1 : 0;
        $maintenanceReminders = isset($_POST['maintenance_reminders']) ? 1 : 0;
        $criticalAlerts = isset($_POST['critical_alerts']) ? 1 : 0;
        $reminderFrequencyDays = (int)$_POST['reminder_frequency_days'];
        
        $stmt = $db->prepare("
            UPDATE notification_preferences 
            SET email_notifications = ?, sms_notifications = ?, intervention_assignments = ?, 
                maintenance_reminders = ?, critical_alerts = ?, reminder_frequency_days = ?, 
                updated_at = NOW()
            WHERE user_id = ?
        ");
        
        $success = $stmt->execute([
            $emailNotifications, $smsNotifications, $interventionAssignments,
            $maintenanceReminders, $criticalAlerts, $reminderFrequencyDays, $userId
        ]);
        
        if ($success) {
            $message = "✅ Préférences mises à jour avec succès !";
            $messageType = "success";
            
            // Recharger les préférences
            $stmt = $db->prepare("SELECT * FROM notification_preferences WHERE user_id = ?");
            $stmt->execute([$userId]);
            $preferences = $stmt->fetch();
        } else {
            $message = "❌ Erreur lors de la mise à jour des préférences";
            $messageType = "error";
        }
    }
    
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
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
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 30px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 12px;
            accent-color: #10b981;
        }
        
        .checkbox-group label {
            margin: 0;
            font-weight: 500;
            color: #6b7280;
            cursor: pointer;
        }
        
        .select-group {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .select-group select {
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            background: white;
            color: #374151;
            min-width: 100px;
        }
        
        .select-group select:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        
        .btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
        }
        
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .message.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .info-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .info-box h3 {
            color: #1e40af;
            margin-bottom: 10px;
        }
        
        .info-box p {
            color: #1e40af;
            line-height: 1.6;
        }
        
        .back-link {
            display: inline-block;
            color: #6b7280;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .back-link:hover {
            color: #10b981;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔔 Préférences de Notification</h1>
            <p>Configurez vos préférences de notification pour TerrainTrack</p>
        </div>
        
        <div class="content">
            <a href="/dashboard.php" class="back-link">← Retour au tableau de bord</a>
            
            <?php if (isset($message)): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <h3>ℹ️ Informations</h3>
                <p>Configurez ici les types de notifications que vous souhaitez recevoir. Les notifications sont envoyées par email à l'adresse : <strong><?php echo htmlspecialchars($user['email']); ?></strong></p>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label>📧 Notifications par email</label>
                    <div class="checkbox-group">
                        <input type="checkbox" id="email_notifications" name="email_notifications" 
                               <?php echo $preferences['email_notifications'] ? 'checked' : ''; ?>>
                        <label for="email_notifications">Activer les notifications par email</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>📱 Types de notifications</label>
                    <div class="checkbox-group">
                        <input type="checkbox" id="intervention_assignments" name="intervention_assignments" 
                               <?php echo $preferences['intervention_assignments'] ? 'checked' : ''; ?>>
                        <label for="intervention_assignments">Assignations d'interventions</label>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="maintenance_reminders" name="maintenance_reminders" 
                               <?php echo $preferences['maintenance_reminders'] ? 'checked' : ''; ?>>
                        <label for="maintenance_reminders">Rappels d'entretien programmés</label>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="critical_alerts" name="critical_alerts" 
                               <?php echo $preferences['critical_alerts'] ? 'checked' : ''; ?>>
                        <label for="critical_alerts">Alertes pour entretiens en retard</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>⏰ Fréquence des rappels</label>
                    <div class="select-group">
                        <label for="reminder_frequency_days">Rappeler les entretiens :</label>
                        <select id="reminder_frequency_days" name="reminder_frequency_days">
                            <option value="1" <?php echo $preferences['reminder_frequency_days'] == 1 ? 'selected' : ''; ?>>1 jour avant</option>
                            <option value="3" <?php echo $preferences['reminder_frequency_days'] == 3 ? 'selected' : ''; ?>>3 jours avant</option>
                            <option value="7" <?php echo $preferences['reminder_frequency_days'] == 7 ? 'selected' : ''; ?>>7 jours avant</option>
                            <option value="14" <?php echo $preferences['reminder_frequency_days'] == 14 ? 'selected' : ''; ?>>14 jours avant</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn">
                    💾 Sauvegarder les préférences
                </button>
            </form>
        </div>
    </div>
</body>
</html>
