<?php
/**
 * Debug de la sauvegarde de sécurité
 */

require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/Database.php';

try {
    echo "🔍 DEBUG SAUVEGARDE SÉCURITÉ\n";
    echo "============================\n\n";
    
    // Connexion à la base de données
    $pdo = \App\Service\Database::connect();
    
    // Test direct de la requête SQL
    echo "1️⃣ Test direct de la requête SQL:\n";
    
    $userId = 7;
    $timeoutMinutes = 60;
    
    $sql = "UPDATE users SET session_timeout = :timeout WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    
    echo "   SQL: $sql\n";
    echo "   Paramètres: timeout=$timeoutMinutes, id=$userId\n";
    
    $result = $stmt->execute([
        'timeout' => $timeoutMinutes,
        'id' => $userId
    ]);
    
    echo "   Résultat execute(): " . ($result ? 'SUCCÈS' : 'ÉCHEC') . "\n";
    
    if (!$result) {
        $errorInfo = $stmt->errorInfo();
        echo "   Erreur SQL: " . $errorInfo[2] . "\n";
    }
    
    // Vérifier le nombre de lignes affectées
    $rowCount = $stmt->rowCount();
    echo "   Lignes affectées: $rowCount\n";
    
    // Vérifier la valeur actuelle
    $stmt = $pdo->prepare("SELECT session_timeout FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $currentTimeout = $stmt->fetchColumn();
    echo "   Valeur actuelle en base: $currentTimeout minutes\n";
    
    // Test avec une valeur différente
    echo "\n2️⃣ Test avec une valeur différente:\n";
    
    $newTimeout = 120;
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        'timeout' => $newTimeout,
        'id' => $userId
    ]);
    
    echo "   Nouvelle valeur: $newTimeout minutes\n";
    echo "   Résultat: " . ($result ? 'SUCCÈS' : 'ÉCHEC') . "\n";
    echo "   Lignes affectées: " . $stmt->rowCount() . "\n";
    
    // Vérifier la nouvelle valeur
    $stmt = $pdo->prepare("SELECT session_timeout FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $updatedTimeout = $stmt->fetchColumn();
    echo "   Valeur mise à jour: $updatedTimeout minutes\n";
    
    if ($updatedTimeout == $newTimeout) {
        echo "✅ SUCCÈS: La requête SQL fonctionne correctement\n";
    } else {
        echo "❌ ÉCHEC: La requête SQL ne fonctionne pas\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>

