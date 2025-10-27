<?php
/**
 * Script de nettoyage des paramètres de débogage
 */

require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/Database.php';
require_once __DIR__ . '/src/Repository/SystemSettingsRepository.php';

try {
    echo "🧹 NETTOYAGE DES PARAMÈTRES DE DÉBOGAGE\n";
    echo "======================================\n\n";
    
    // Connexion à la base de données
    $pdo = \App\Service\Database::connect();
    $settingsRepo = new \App\Repository\SystemSettingsRepository($pdo);
    
    // Paramètres de débogage à supprimer
    $debugSettings = ['debug_mode', 'log_level'];
    
    echo "🔍 Recherche des paramètres de débogage...\n";
    
    // Compter les paramètres existants
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM system_settings 
        WHERE setting_key IN ('" . implode("', '", $debugSettings) . "')
    ");
    $stmt->execute();
    $count = $stmt->fetch()['count'];
    
    echo "📊 Paramètres de débogage trouvés: $count\n\n";
    
    if ($count > 0) {
        echo "🗑️ Suppression des paramètres de débogage...\n";
        
        // Supprimer les paramètres de débogage
        foreach ($debugSettings as $setting) {
            $stmt = $pdo->prepare("
                DELETE FROM system_settings 
                WHERE setting_key = ?
            ");
            $result = $stmt->execute([$setting]);
            echo "   $setting: " . ($result ? 'SUPPRIMÉ' : 'ERREUR') . "\n";
        }
        
        echo "\n✅ Nettoyage terminé avec succès\n";
    } else {
        echo "✅ Aucun paramètre de débogage trouvé\n";
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
    
    echo "\n🎯 PARAMÈTRES DE DÉBOGAGE SUPPRIMÉS\n";
    echo "L'interface utilisateur est maintenant plus propre !\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>

