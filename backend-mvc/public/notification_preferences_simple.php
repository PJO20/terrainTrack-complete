<?php
/**
 * Page de préférences de notification - Version moderne
 * TerrainTrack - Interface utilisateur
 */
    // Charger EnvService si disponible
    if (!class_exists('App\Service\EnvService')) {
        require_once __DIR__ . '/../src/Service/EnvService.php';
    }


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
$password = \App\Service\EnvService::get('DB_PASS', 'root');

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

// Test d'envoi d'email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    try {
        $subject = "🧪 Test de notification TerrainTrack";
        $message = "Bonjour " . ($user['name'] ?: 'Utilisateur') . ",\n\n";
        $message .= "Ceci est un email de test pour vérifier que vos notifications fonctionnent correctement.\n\n";
        $message .= "Si vous recevez ce message, la configuration est opérationnelle !\n\n";
        $message .= "Cordialement,\nL'équipe TerrainTrack";
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/plain; charset=UTF-8',
            'From: TerrainTrack <noreply@terraintrack.com>',
            'Reply-To: noreply@terraintrack.com'
        ];
        
        $result = mail($user['email'], $subject, $message, implode("\r\n", $headers));
        
        if ($result) {
            $success = "Email de test envoyé avec succès !";
            
            // Log dans la base de données
            $sql = "INSERT INTO notification_logs (user_id, notification_type, sent_at, status, message) 
                    VALUES (?, 'test_email', NOW(), 'sent', ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_SESSION['user_id'], $subject]);
        } else {
            $error = "Échec de l'envoi de l'email de test.";
        }
        
    } catch (Exception $e) {
        $error = "Erreur lors du test email : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Préférences de Notification - TerrainTrack v<?= date('YmdHis') ?></title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* VERSION <?= time() ?> - FORCE CACHE REFRESH */
        :root {
            --primary: #3b82f6;
            --primary-dark: #1d4ed8;
            --secondary: #6b7280;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --background: #f8fafc;
            --surface: #ffffff;
            --border: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #6b7280;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--background);
            min-height: 100vh;
            padding: 20px;
            line-height: 1.6;
            color: var(--text-primary);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: var(--surface);
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            border: 1px solid var(--border);
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 50%, #06b6d4 100%);
            color: white;
            padding: 48px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .header-content {
            position: relative;
            z-index: 1;
        }
        
        .header h1 {
            font-size: 3rem;
            margin-bottom: 16px;
            font-weight: 700;
            letter-spacing: -0.025em;
        }
        
        .header p {
            font-size: 1.25rem;
            opacity: 0.95;
            font-weight: 400;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .content {
            padding: 48px 40px;
        }
        
        .alert {
            padding: 20px 24px;
            border-radius: 16px;
            margin-bottom: 32px;
            font-weight: 500;
            border: 1px solid;
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 1rem;
        }
        
        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border-color: #bbf7d0;
        }
        
        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border-color: #fecaca;
        }
        
        .preferences-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .preference-card {
            background: #fafbfc;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .preference-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        }
        
        .preference-card:hover {
            border-color: var(--primary);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15);
            transform: translateY(-2px);
        }
        
        .preference-card h3 {
            color: var(--text-primary);
            margin-bottom: 28px;
            font-size: 1.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .form-group {
            margin-bottom: 28px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 12px;
            color: var(--text-primary);
            font-size: 1rem;
        }
        
        .form-group input[type="email"],
        .form-group input[type="tel"] {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--surface);
            font-family: inherit;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            transform: translateY(-1px);
        }
        
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            padding: 16px;
            border-radius: 12px;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .checkbox-group:hover {
            background: #f8fafc;
            transform: translateX(4px);
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 16px;
            transform: scale(1.2);
            accent-color: var(--primary);
            cursor: pointer;
        }
        
        .checkbox-group label {
            margin: 0;
            font-weight: 500;
            cursor: pointer;
            color: var(--text-primary);
            line-height: 1.6;
            flex: 1;
        }
        
        .checkbox-description {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-top: 6px;
            line-height: 1.5;
        }
        
        .btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 16px 32px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 16px;
            margin-bottom: 16px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            font-family: inherit;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(59, 130, 246, 0.4);
        }
        
        .btn-secondary {
            background: var(--secondary);
        }
        
        .btn-secondary:hover {
            background: #4b5563;
            box-shadow: 0 12px 30px rgba(107, 114, 128, 0.4);
        }
        
        .btn-test {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
        }
        
        .btn-test:hover {
            box-shadow: 0 12px 30px rgba(16, 185, 129, 0.4);
        }
        
        .stats {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid var(--border);
            padding: 32px;
            border-radius: 20px;
            margin-bottom: 40px;
        }
        
        .stats h4 {
            margin-bottom: 24px;
            color: var(--text-primary);
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 24px;
        }
        
        .stat-item {
            text-align: center;
            padding: 24px;
            background: var(--surface);
            border-radius: 16px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            transition: all 0.3s ease;
        }
        
        .stat-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
            line-height: 1;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .notifications-history {
            margin-top: 48px;
            padding-top: 40px;
            border-top: 2px solid var(--border);
        }
        
        .notifications-history h3 {
            color: var(--text-primary);
            margin-bottom: 28px;
            font-size: 1.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .notification-item {
            background: #fafbfc;
            border: 1px solid var(--border);
            padding: 24px;
            border-radius: 16px;
            margin-bottom: 16px;
            border-left: 4px solid var(--primary);
            transition: all 0.3s ease;
        }
        
        .notification-item:hover {
            border-color: var(--primary);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.1);
            transform: translateX(4px);
        }
        
        .notification-item .type {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 8px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .notification-item .date {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 8px;
        }
        
        .notification-item .status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .status-sent {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-failed {
            background: #fef2f2;
            color: #991b1b;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 32px;
            padding: 12px 20px;
            border-radius: 10px;
            transition: all 0.3s ease;
            background: #f0f9ff;
            border: 1px solid #e0f2fe;
        }
        
        .back-link:hover {
            background: #e0f2fe;
            text-decoration: none;
            transform: translateX(-4px);
        }
        
        .action-buttons {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 40px;
            padding-top: 32px;
            border-top: 1px solid var(--border);
        }
        
        .config-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 24px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-top: 20px;
        }
        
        .config-enabled {
            background: #dcfce7;
            color: #166534;
        }
        
        .config-disabled {
            background: #fef2f2;
            color: #991b1b;
        }
        
        @media (max-width: 768px) {
            .preferences-grid {
                grid-template-columns: 1fr;
                gap: 32px;
            }
            
            .content {
                padding: 32px 24px;
            }
            
            .header {
                padding: 40px 24px;
            }
            
            .header h1 {
                font-size: 2.5rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <h1>🔔 Préférences de Notification</h1>
                <p>Configurez vos préférences de notification pour rester informé des interventions et entretiens</p>
            </div>
        </div>
        
        <div class="content">
            <a href="/backend-mvc/public/index.php" class="back-link">
                ← Retour au tableau de bord
            </a>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    ✅ <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    ❌ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistiques -->
            <div class="stats">
                <h4>📊 Statistiques de notification</h4>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value"><?= count($notifications) ?></div>
                        <div class="stat-label">Notifications récentes</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $preferences['email_notifications'] ? '✅' : '❌' ?></div>
                        <div class="stat-label">Email activé</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $preferences['sms_notifications'] ? '✅' : '❌' ?></div>
                        <div class="stat-label">SMS activé</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $preferences['maintenance_reminders'] ? '✅' : '❌' ?></div>
                        <div class="stat-label">Rappels activés</div>
                    </div>
                </div>
            </div>
            
            <form method="POST">
                <div class="preferences-grid">
                    <!-- Notifications Email -->
                    <div class="preference-card">
                        <h3>📧 Notifications Email</h3>
                        
                        <div class="form-group">
                            <label for="notification_email">Email de notification :</label>
                            <input type="email" id="notification_email" name="notification_email" 
                                   value="<?= htmlspecialchars($user['email']) ?>" 
                                   placeholder="votre@email.com">
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="email_notifications" name="email_notifications" 
                                   <?= $preferences['email_notifications'] ? 'checked' : '' ?>>
                            <label for="email_notifications">
                                Activer les notifications email
                                <div class="checkbox-description">Recevoir des notifications par email</div>
                            </label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="maintenance_reminders" name="maintenance_reminders" 
                                   <?= $preferences['maintenance_reminders'] ? 'checked' : '' ?>>
                            <label for="maintenance_reminders">
                                Rappels d'entretien programmés
                                <div class="checkbox-description">Recevoir des rappels pour les entretiens programmés</div>
                            </label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="overdue_alerts" name="overdue_alerts" 
                                   <?= $preferences['overdue_alerts'] ? 'checked' : '' ?>>
                            <label for="overdue_alerts">
                                Alertes d'entretiens en retard
                                <div class="checkbox-description">Recevoir des alertes pour les entretiens en retard</div>
                            </label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="intervention_assignments" name="intervention_assignments" 
                                   <?= $preferences['intervention_assignments'] ? 'checked' : '' ?>>
                            <label for="intervention_assignments">
                                Assignations d'interventions
                                <div class="checkbox-description">Être notifié lors de l'assignation d'une intervention</div>
                            </label>
                        </div>
                        
                        <div class="config-status <?= $preferences['email_notifications'] ? 'config-enabled' : 'config-disabled' ?>">
                            <?= $preferences['email_notifications'] ? '✅ Email configuré' : '❌ Email désactivé' ?>
                        </div>
                    </div>
                    
                    <!-- Notifications SMS -->
                    <div class="preference-card">
                        <h3>📱 Notifications SMS</h3>
                        
                        <div class="form-group">
                            <label for="phone">Numéro de téléphone :</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                                   placeholder="+33 6 12 34 56 78">
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="sms_notifications" name="sms_notifications" 
                                   <?= $preferences['sms_notifications'] ? 'checked' : '' ?>>
                            <label for="sms_notifications">
                                Activer les notifications SMS
                                <div class="checkbox-description">Recevoir des notifications par SMS</div>
                            </label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="maintenance_reminders_sms" name="maintenance_reminders_sms" 
                                   <?= $preferences['maintenance_reminders'] ? 'checked' : '' ?>>
                            <label for="maintenance_reminders_sms">
                                Rappels d'entretien par SMS
                                <div class="checkbox-description">Recevoir des rappels SMS pour les entretiens</div>
                            </label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="overdue_alerts_sms" name="overdue_alerts_sms" 
                                   <?= $preferences['overdue_alerts'] ? 'checked' : '' ?>>
                            <label for="overdue_alerts_sms">
                                Alertes en retard par SMS
                                <div class="checkbox-description">Recevoir des alertes SMS pour les entretiens en retard</div>
                            </label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="intervention_assignments_sms" name="intervention_assignments_sms" 
                                   <?= $preferences['intervention_assignments'] ? 'checked' : '' ?>>
                            <label for="intervention_assignments_sms">
                                Assignations par SMS
                                <div class="checkbox-description">Être notifié par SMS lors de l'assignation</div>
                            </label>
                        </div>
                        
                        <div class="config-status <?= $preferences['sms_notifications'] ? 'config-enabled' : 'config-disabled' ?>">
                            <?= $preferences['sms_notifications'] ? '✅ SMS configuré' : '❌ SMS désactivé' ?>
                        </div>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button type="submit" name="update_preferences" class="btn">
                        💾 Sauvegarder les préférences
                    </button>
                    
                    <button type="submit" name="test_email" class="btn btn-test">
                        📧 Tester l'email
                    </button>
                    
                    <a href="/backend-mvc/public/index.php" class="btn btn-secondary">
                        🏠 Retour au tableau de bord
                    </a>
                </div>
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