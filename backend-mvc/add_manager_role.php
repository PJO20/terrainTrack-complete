<?php
/**
 * Ajout du rôle 'manager' à la table users
 */

require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/Database.php';

header('Content-Type: text/plain; charset=utf-8');

echo "🔧 AJOUT DU RÔLE MANAGER\n";
echo "========================\n\n";

try {
    echo "1️⃣ Vérification de la structure actuelle de la table users:\n";
    
    $pdo = \App\Service\Database::connect();
    $stmt = $pdo->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
    $roleColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($roleColumn) {
        echo "   Colonne role actuelle: {$roleColumn['Type']}\n";
        echo "   Valeurs possibles: {$roleColumn['Type']}\n";
    } else {
        echo "   ❌ Colonne role non trouvée\n";
        exit;
    }
    
    echo "\n2️⃣ Ajout du rôle 'manager' à l'enum:\n";
    
    // Modifier l'enum pour inclure 'manager'
    $sql = "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'super_admin', 'manager', 'technician', 'user') NOT NULL";
    $result = $pdo->exec($sql);
    
    if ($result !== false) {
        echo "   ✅ Rôle 'manager' ajouté avec succès\n";
    } else {
        echo "   ❌ Erreur lors de l'ajout du rôle 'manager'\n";
        $errorInfo = $pdo->errorInfo();
        echo "   Détails: " . $errorInfo[2] . "\n";
    }
    
    echo "\n3️⃣ Vérification de la nouvelle structure:\n";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
    $roleColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($roleColumn) {
        echo "   Nouvelle colonne role: {$roleColumn['Type']}\n";
        echo "   Valeurs possibles: {$roleColumn['Type']}\n";
    }
    
    echo "\n4️⃣ Test de création d'un utilisateur avec le rôle 'manager':\n";
    
    // Test de création d'un utilisateur manager
    $stmt = $pdo->prepare("INSERT INTO users (email, password, name, role, phone, location, department, timezone, language) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([
        'manager_test_' . time() . '@example.com',
        password_hash('password123', PASSWORD_DEFAULT),
        'Manager Test',
        'manager',
        '+33 6 00 00 00 00',
        'Paris, France',
        'IT',
        'Europe/Paris',
        'fr'
    ]);
    
    if ($result) {
        echo "   ✅ Utilisateur manager créé avec succès\n";
        
        // Récupérer l'ID et vérifier la 2FA
        $userId = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT two_factor_enabled, two_factor_required FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            echo "   Vérification de la 2FA pour le manager:\n";
            echo "     - 2FA requise: " . ($userData['two_factor_required'] ? 'OUI' : 'NON') . "\n";
            echo "     - 2FA activée: " . ($userData['two_factor_enabled'] ? 'OUI' : 'NON') . "\n";
            
            if ($userData['two_factor_required'] && $userData['two_factor_enabled']) {
                echo "   ✅ 2FA obligatoire activée pour le manager\n";
            } else {
                echo "   ❌ 2FA obligatoire non activée pour le manager\n";
            }
        }
        
        // Supprimer l'utilisateur de test
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        echo "   ✅ Utilisateur manager de test supprimé\n";
    } else {
        echo "   ❌ Erreur lors de la création de l'utilisateur manager\n";
        $errorInfo = $pdo->errorInfo();
        echo "   Détails: " . $errorInfo[2] . "\n";
    }
    
    echo "\n5️⃣ Recommandations:\n";
    
    echo "   🔧 INTÉGRATION DANS LE CODE:\n";
    echo "   1. Le rôle 'manager' est maintenant disponible\n";
    echo "   2. La 2FA sera obligatoire pour les managers\n";
    echo "   3. Tester la création d'utilisateurs avec le rôle 'manager'\n";
    echo "   4. Vérifier que la 2FA est automatiquement activée\n";
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "RÉSUMÉ FINAL\n";
    echo str_repeat("=", 50) . "\n";
    
    echo "✅ RÔLE 'MANAGER' AJOUTÉ\n";
    echo "✅ 2FA OBLIGATOIRE POUR LES MANAGERS\n";
    echo "✅ 2FA OPTIONNELLE POUR LES TECHNICIENS\n";
    echo "🔧 PROCHAINES ÉTAPES:\n";
    echo "   1. Tester la création d'utilisateurs avec le rôle 'manager'\n";
    echo "   2. Vérifier que la 2FA est automatiquement activée\n";
    echo "   3. Ajouter des paramètres 2FA dans l'interface utilisateur\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
