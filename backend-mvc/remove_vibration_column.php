<?php
/**
 * Script pour supprimer la colonne vibration_notifications
 */

require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/Database.php';

header('Content-Type: text/plain; charset=utf-8');

echo "🗑️ SUPPRESSION COLONNE VIBRATION_NOTIFICATIONS\n";
echo "==============================================\n\n";

try {
    $pdo = \App\Service\Database::connect();
    
    echo "1️⃣ Vérification de l'existence de la colonne:\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM notification_settings LIKE 'vibration_notifications'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        echo "   ✅ Colonne vibration_notifications trouvée\n";
        echo "   Type: " . $column['Type'] . "\n";
        echo "   Null: " . $column['Null'] . "\n";
        echo "   Default: " . $column['Default'] . "\n";
        
        echo "\n2️⃣ Suppression de la colonne:\n";
        $stmt = $pdo->prepare("ALTER TABLE notification_settings DROP COLUMN vibration_notifications");
        $result = $stmt->execute();
        
        if ($result) {
            echo "   ✅ Colonne vibration_notifications supprimée avec succès\n";
        } else {
            echo "   ❌ Échec de la suppression de la colonne\n";
        }
    } else {
        echo "   ❌ Colonne vibration_notifications non trouvée (déjà supprimée?)\n";
    }
    
    echo "\n3️⃣ Vérification de la structure après suppression:\n";
    $stmt = $pdo->query("DESCRIBE notification_settings");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $vibrationFound = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'vibration_notifications') {
            $vibrationFound = true;
        }
        echo "   - " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    if (!$vibrationFound) {
        echo "   ✅ Colonne vibration_notifications supprimée de la structure\n";
    } else {
        echo "   ❌ Colonne vibration_notifications toujours présente\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "RÉSUMÉ\n";
    echo str_repeat("=", 50) . "\n";
    
    if (!$vibrationFound) {
        echo "✅ SUPPRESSION RÉUSSIE\n";
        echo "   - La colonne vibration_notifications a été supprimée\n";
        echo "   - La structure de la table est maintenant cohérente\n";
        echo "   - Les paramètres de notification fonctionneront correctement\n";
    } else {
        echo "❌ SUPPRESSION ÉCHOUÉE\n";
        echo "   - La colonne vibration_notifications est toujours présente\n";
        echo "   - Vérifier les permissions de la base de données\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
