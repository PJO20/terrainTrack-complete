<?php
/**
 * Script de rappels automatiques - Version simplifiée
 * TerrainTrack - Système de notifications
 */

echo "🔄 Démarrage du script de rappels automatiques...\n";

// Configuration directe de la base de données
$host = 'localhost';
$port = '8889';
$dbname = 'exemple';
$username = 'root';
$password = 'root';

try {
    // Connexion à la base de données
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Connexion à la base de données réussie !\n";
    
    // 1. Vérifier les entretiens à venir (7 jours)
    echo "\n📅 Vérification des entretiens à venir...\n";
    $sql = "SELECT * FROM maintenance_schedules 
            WHERE due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
            AND status = 'scheduled'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $upcoming = $stmt->fetchAll();
    
    echo "   - Entretiens à venir : " . count($upcoming) . "\n";
    
    // 2. Vérifier les entretiens en retard
    echo "\n⚠️ Vérification des entretiens en retard...\n";
    $sql = "SELECT * FROM maintenance_schedules 
            WHERE due_date < NOW() AND status = 'scheduled'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $overdue = $stmt->fetchAll();
    
    echo "   - Entretiens en retard : " . count($overdue) . "\n";
    
    // 3. Récupérer les utilisateurs avec notifications activées
    echo "\n👥 Récupération des utilisateurs...\n";
    $sql = "SELECT id, name, email, notification_email FROM users 
            WHERE is_active = 1 AND notification_email = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "   - Utilisateurs avec notifications : " . count($users) . "\n";
    
    // 4. Envoyer les rappels d'entretien à venir
    if (!empty($upcoming) && !empty($users)) {
        echo "\n📧 Envoi des rappels d'entretien à venir...\n";
        
        foreach ($upcoming as $maintenance) {
            foreach ($users as $user) {
                $subject = "🔔 Rappel d'entretien - " . $maintenance['vehicle_name'];
                $message = "Bonjour " . $user['name'] . ",\n\n";
                $message .= "Un entretien est programmé pour le véhicule : " . $maintenance['vehicle_name'] . "\n";
                $message .= "Date prévue : " . $maintenance['due_date'] . "\n";
                $message .= "Type : " . $maintenance['maintenance_type'] . "\n\n";
                $message .= "Cordialement,\nL'équipe TerrainTrack";
                
                // Envoyer l'email
                $headers = [
                    'MIME-Version: 1.0',
                    'Content-type: text/plain; charset=UTF-8',
                    'From: TerrainTrack <noreply@terraintrack.com>',
                    'Reply-To: noreply@terraintrack.com'
                ];
                
                $result = mail($user['email'], $subject, $message, implode("\r\n", $headers));
                
                if ($result) {
                    echo "   ✅ Rappel envoyé à " . $user['email'] . " pour " . $maintenance['vehicle_name'] . "\n";
                    
                    // Log dans la base de données
                    $logSql = "INSERT INTO notification_logs (user_id, notification_type, sent_at, status, message) 
                              VALUES (?, 'maintenance_reminder', NOW(), 'sent', ?)";
                    $logStmt = $pdo->prepare($logSql);
                    $logStmt->execute([$user['id'], $subject]);
                } else {
                    echo "   ❌ Échec envoi à " . $user['email'] . "\n";
                }
            }
        }
    }
    
    // 5. Envoyer les alertes d'entretiens en retard
    if (!empty($overdue) && !empty($users)) {
        echo "\n🚨 Envoi des alertes d'entretiens en retard...\n";
        
        foreach ($overdue as $maintenance) {
            foreach ($users as $user) {
                $subject = "🚨 ALERTE - Entretien en retard - " . $maintenance['vehicle_name'];
                $message = "ALERTE " . $user['name'] . ",\n\n";
                $message .= "L'entretien du véhicule " . $maintenance['vehicle_name'] . " est EN RETARD !\n";
                $message .= "Date prévue : " . $maintenance['due_date'] . "\n";
                $message .= "Type : " . $maintenance['maintenance_type'] . "\n";
                $message .= "Retard de : " . (new DateTime())->diff(new DateTime($maintenance['due_date']))->days . " jours\n\n";
                $message .= "Action requise immédiatement !\n\n";
                $message .= "Cordialement,\nL'équipe TerrainTrack";
                
                // Envoyer l'email
                $headers = [
                    'MIME-Version: 1.0',
                    'Content-type: text/plain; charset=UTF-8',
                    'From: TerrainTrack <noreply@terraintrack.com>',
                    'Reply-To: noreply@terraintrack.com'
                ];
                
                $result = mail($user['email'], $subject, $message, implode("\r\n", $headers));
                
                if ($result) {
                    echo "   ✅ Alerte envoyée à " . $user['email'] . " pour " . $maintenance['vehicle_name'] . "\n";
                    
                    // Log dans la base de données
                    $logSql = "INSERT INTO notification_logs (user_id, notification_type, sent_at, status, message) 
                              VALUES (?, 'maintenance_overdue', NOW(), 'sent', ?)";
                    $logStmt = $pdo->prepare($logSql);
                    $logStmt->execute([$user['id'], $subject]);
                } else {
                    echo "   ❌ Échec envoi à " . $user['email'] . "\n";
                }
            }
        }
    }
    
    echo "\n🎉 Script de rappels terminé avec succès !\n";
    echo "📊 Résumé :\n";
    echo "   - Entretiens à venir : " . count($upcoming) . "\n";
    echo "   - Entretiens en retard : " . count($overdue) . "\n";
    echo "   - Utilisateurs notifiés : " . count($users) . "\n";
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}

