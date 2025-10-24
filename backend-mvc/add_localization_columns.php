<?php

require_once 'vendor/autoload.php';

echo "🔧 Ajout des colonnes de localisation manquantes\n";
echo "===============================================\n\n";

try {
    // Connexion PDO directe
    $host = "localhost";
    $dbname = "exemple";
    $username = "root";
    $password = EnvService::get('DB_PASS', 'root');
    $port = 8889;
    
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "✅ Connexion à la base de données réussie\n\n";
    
    // Vérifier les colonnes existantes
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Colonnes à ajouter
    $newColumns = [
        'date_format' => "ADD COLUMN date_format VARCHAR(20) DEFAULT 'DD/MM/YYYY'",
        'time_format' => "ADD COLUMN time_format VARCHAR(10) DEFAULT '24'",
        'auto_save' => "ADD COLUMN auto_save BOOLEAN DEFAULT TRUE"
    ];
    
    echo "🔧 Ajout des colonnes manquantes...\n";
    echo "===================================\n";
    
    foreach ($newColumns as $column => $sql) {
        if (!in_array($column, $columns)) {
            $pdo->exec("ALTER TABLE users $sql");
            echo "✅ Colonne $column ajoutée\n";
        } else {
            echo "ℹ️ Colonne $column existe déjà - ignorée\n";
        }
    }
    
    echo "\n📋 Vérification de la nouvelle structure :\n";
    echo "==========================================\n";
    
    $stmt = $pdo->query("DESCRIBE users");
    $allColumns = $stmt->fetchAll();
    
    $localizationColumns = ['language', 'timezone', 'date_format', 'time_format', 'auto_save'];
    
    foreach ($allColumns as $column) {
        $columnName = $column['Field'];
        if (in_array($columnName, $localizationColumns)) {
            echo "✅ {$columnName} ({$column['Type']}) - Default: {$column['Default']}\n";
        }
    }
    
    echo "\n📊 Test d'insertion de valeurs par défaut pour l'admin :\n";
    echo "======================================================\n";
    
    $stmt = $pdo->prepare("
        UPDATE users 
        SET date_format = COALESCE(date_format, 'DD/MM/YYYY'),
            time_format = COALESCE(time_format, '24'),
            auto_save = COALESCE(auto_save, TRUE)
        WHERE role = 'admin'
    ");
    $stmt->execute();
    
    echo "✅ Valeurs par défaut mises à jour pour les admins\n";
    
    // Vérifier les valeurs
    $stmt = $pdo->query("
        SELECT id, email, name, language, timezone, date_format, time_format, auto_save 
        FROM users 
        WHERE role = 'admin' 
        LIMIT 1
    ");
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "\n👤 Admin: {$admin['name']} ({$admin['email']})\n";
        echo "   - language: {$admin['language']}\n";
        echo "   - timezone: {$admin['timezone']}\n";
        echo "   - date_format: {$admin['date_format']}\n";
        echo "   - time_format: {$admin['time_format']}\n";
        echo "   - auto_save: " . ($admin['auto_save'] ? 'TRUE' : 'FALSE') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n🏁 Ajout terminé\n";

?>

