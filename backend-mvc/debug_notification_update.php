<?php
/**
 * Debug détaillé de la mise à jour des notifications
 */

require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/Database.php';

header('Content-Type: text/plain; charset=utf-8');

echo "🔍 DEBUG DÉTAILLÉ MISE À JOUR NOTIFICATIONS\n";
echo "==========================================\n\n";

try {
    $pdo = \App\Service\Database::connect();
    $userId = 7;
    
    echo "1️⃣ État initial:\n";
    $stmt = $pdo->prepare("SELECT email_notifications FROM notification_settings WHERE user_id = ?");
    $stmt->execute([$userId]);
    $initial = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Email notifications: " . ($initial['email_notifications'] ? 'ACTIVÉ' : 'DÉSACTIVÉ') . "\n";
    
    echo "\n2️⃣ Test de mise à jour directe (désactiver):\n";
    $stmt = $pdo->prepare("UPDATE notification_settings SET email_notifications = 0 WHERE user_id = ?");
    $result = $stmt->execute([$userId]);
    echo "   Résultat: " . ($result ? 'SUCCÈS' : 'ÉCHEC') . "\n";
    echo "   Lignes affectées: " . $stmt->rowCount() . "\n";
    
    echo "\n3️⃣ Vérification après mise à jour directe:\n";
    $stmt = $pdo->prepare("SELECT email_notifications FROM notification_settings WHERE user_id = ?");
    $stmt->execute([$userId]);
    $afterUpdate = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Email notifications: " . ($afterUpdate['email_notifications'] ? 'ACTIVÉ' : 'DÉSACTIVÉ') . "\n";
    
    echo "\n4️⃣ Test de réactivation directe:\n";
    $stmt = $pdo->prepare("UPDATE notification_settings SET email_notifications = 1 WHERE user_id = ?");
    $result = $stmt->execute([$userId]);
    echo "   Résultat: " . ($result ? 'SUCCÈS' : 'ÉCHEC') . "\n";
    echo "   Lignes affectées: " . $stmt->rowCount() . "\n";
    
    echo "\n5️⃣ Vérification finale:\n";
    $stmt = $pdo->prepare("SELECT email_notifications FROM notification_settings WHERE user_id = ?");
    $stmt->execute([$userId]);
    $final = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Email notifications: " . ($final['email_notifications'] ? 'ACTIVÉ' : 'DÉSACTIVÉ') . "\n";
    
    echo "\n6️⃣ Test avec le repository:\n";
    require_once __DIR__ . '/src/Repository/NotificationSettingsRepository.php';
    $repository = new \App\Repository\NotificationSettingsRepository($pdo);
    
    $testData = [
        'email_notifications' => false, // Désactiver
        'push_notifications' => true,
        'desktop_notifications' => true,
        'sound_notifications' => true,
        'vehicle_alerts' => true,
        'maintenance_reminders' => true,
        'intervention_updates' => true,
        'team_notifications' => true,
        'system_alerts' => true,
        'report_generation' => false,
        'notification_frequency' => 'realtime',
        'quiet_hours_enabled' => true,
        'quiet_hours_start' => '22:00:00',
        'quiet_hours_end' => '07:00:00'
    ];
    
    echo "   Données à envoyer:\n";
    foreach ($testData as $key => $value) {
        echo "     $key: " . ($value ? 'true' : 'false') . "\n";
    }
    
    $updateResult = $repository->updateNotifications($userId, $testData);
    echo "   Résultat repository: " . ($updateResult ? 'SUCCÈS' : 'ÉCHEC') . "\n";
    
    echo "\n7️⃣ Vérification après repository:\n";
    $stmt = $pdo->prepare("SELECT email_notifications FROM notification_settings WHERE user_id = ?");
    $stmt->execute([$userId]);
    $afterRepo = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Email notifications: " . ($afterRepo['email_notifications'] ? 'ACTIVÉ' : 'DÉSACTIVÉ') . "\n";
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "RÉSUMÉ DU DEBUG\n";
    echo str_repeat("=", 50) . "\n";
    
    if ($afterRepo['email_notifications'] == 0) {
        echo "✅ PERSISTANCE FONCTIONNELLE\n";
        echo "   - Les mises à jour directes fonctionnent\n";
        echo "   - Le repository fonctionne correctement\n";
        echo "   - Les paramètres sont correctement persistés\n";
    } else {
        echo "❌ PROBLÈME DE PERSISTANCE\n";
        echo "   - Vérifier les logs d'erreur\n";
        echo "   - Vérifier les permissions de la base\n";
        echo "   - Vérifier la structure de la table\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>

