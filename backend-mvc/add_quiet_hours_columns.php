<?php
/**
 * Script pour ajouter les colonnes des heures silencieuses
 */

require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/Database.php';

try {
    echo "🔇 AJOUT DES COLONNES HEURES SILENCIEUSES\n";
    echo "=========================================\n\n";
    
    $pdo = \App\Service\Database::connect();
    
    // Vérifier si les colonnes existent déjà
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    
    $hasQuietHours = in_array('quiet_hours_enabled', $columns);
    $hasQuietStart = in_array('quiet_hours_start', $columns);
    $hasQuietEnd = in_array('quiet_hours_end', $columns);
    
    echo "État actuel:\n";
    echo "- quiet_hours_enabled: " . ($hasQuietHours ? 'EXISTE' : 'MANQUANTE') . "\n";
    echo "- quiet_hours_start: " . ($hasQuietStart ? 'EXISTE' : 'MANQUANTE') . "\n";
    echo "- quiet_hours_end: " . ($hasQuietEnd ? 'EXISTE' : 'MANQUANTE') . "\n\n";
    
    // Ajouter les colonnes manquantes
    if (!$hasQuietHours) {
        echo "Ajout de quiet_hours_enabled...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN quiet_hours_enabled BOOLEAN DEFAULT FALSE");
        echo "✅ Colonne quiet_hours_enabled ajoutée\n";
    }
    
    if (!$hasQuietStart) {
        echo "Ajout de quiet_hours_start...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN quiet_hours_start TIME DEFAULT '22:00:00'");
        echo "✅ Colonne quiet_hours_start ajoutée\n";
    }
    
    if (!$hasQuietEnd) {
        echo "Ajout de quiet_hours_end...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN quiet_hours_end TIME DEFAULT '07:00:00'");
        echo "✅ Colonne quiet_hours_end ajoutée\n";
    }
    
    echo "\n🎯 COLONNES AJOUTÉES AVEC SUCCÈS !\n";
    echo "La fonctionnalité des heures silencieuses est maintenant opérationnelle.\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>

