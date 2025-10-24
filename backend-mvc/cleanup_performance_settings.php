<?php
/**
 * Script de nettoyage des paramètres de performance
 */

require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/Database.php';
require_once __DIR__ . '/src/Repository/SystemSettingsRepository.php';

try {
    echo "🧹 NETTOYAGE DES PARAMÈTRES DE PERFORMANCE\n";
    echo "=========================================\n\n";
    
    // Connexion à la base de données
    $pdo = \App\Service\Database::connect();
    $settingsRepo = new \App\Repository\SystemSettingsRepository($pdo);
    
    // Paramètres de performance à supprimer
    $performanceSettings = ['performance_mode', 'data_compression'];
    
    echo "🔍 Recherche des paramètres de performance...\n";
    
    // Compter les paramètres existants
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM system_settings 
        WHERE setting_key IN ('" . implode("', '", $performanceSettings) . "')
    ");
    $stmt->execute();
    $count = $stmt->fetch()['count'];
    
    echo "📊 Paramètres de performance trouvés: $count\n\n";
    
    if ($count > 0) {
        echo "🗑️ Suppression des paramètres de performance...\n";
        
        // Supprimer les paramètres de performance
        foreach ($performanceSettings as $setting) {
            $stmt = $pdo->prepare("
                DELETE FROM system_settings 
                WHERE setting_key = ?
            ");
            $result = $stmt->execute([$setting]);
            echo "   $setting: " . ($result ? 'SUPPRIMÉ' : 'ERREUR') . "\n";
        }
        
        echo "\n✅ Nettoyage terminé avec succès\n";
    } else {
        echo "✅ Aucun paramètre de performance trouvé\n";
    }
    
    // Vérifier les paramètres restants
    echo "\n📋 Paramètres système restants:\n";
    $stmt = $pdo->query("
        SELECT setting_key, COUNT(*) as count
        FROM system_settings 
        GROUP BY setting_key
        ORDER BY setting_key
    ");
    
    while ($row = $stmt->fetch()) {
        echo "   {$row['setting_key']}: {$row['count']} utilisateurs\n";
    }
    
    echo "\n🎯 PARAMÈTRES DE PERFORMANCE SUPPRIMÉS\n";
    echo "L'interface utilisateur est maintenant plus simple et claire !\n";
    
    echo "\n📊 INTERFACE FINALE:\n";
    echo "Paramètres système\n";
    echo "├── Performance\n";
    echo "│   ├── Sauvegarde automatique ✅\n";
    echo "│   └── Cache activé ✅\n";
    echo "└── Mode hors ligne\n";
    echo "    └── Activer le mode hors ligne ✅\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
