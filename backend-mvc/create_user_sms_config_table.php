<?php

echo "🔧 Création de la configuration SMS par utilisateur\n";
echo "=================================================\n\n";

try {
    // Connexion PDO
    $host = "localhost";
    $dbname = "exemple";
    $username = "root";
    $password = EnvService::get('DB_PASS', 'root');
    $port = 8889;
    
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✅ Connexion à la base de données réussie\n\n";
    
    // Créer la table de configuration SMS par utilisateur
    $createTable = "
        CREATE TABLE IF NOT EXISTS user_sms_config (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            provider VARCHAR(50) NOT NULL DEFAULT 'twilio',
            api_url VARCHAR(255),
            api_key VARCHAR(255),
            api_secret VARCHAR(255),
            sender_number VARCHAR(50),
            is_active BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_sms (user_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";
    
    $pdo->exec($createTable);
    echo "✅ Table user_sms_config créée\n";
    
    // Ajouter des colonnes à la table users pour les préférences SMS
    $alterUsers = "
        ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS sms_provider VARCHAR(50) DEFAULT 'system',
        ADD COLUMN IF NOT EXISTS use_personal_sms BOOLEAN DEFAULT FALSE
    ";
    
    try {
        $pdo->exec($alterUsers);
        echo "✅ Colonnes SMS ajoutées à la table users\n";
    } catch (Exception $e) {
        echo "ℹ️ Colonnes SMS déjà présentes dans users\n";
    }
    
    // Exemple de configuration pour l'admin
    echo "\n📋 Exemple de configuration pour l'admin\n";
    echo "=======================================\n";
    
    $exampleConfig = [
        'user_id' => 7, // Admin
        'provider' => 'twilio',
        'api_url' => 'https://api.twilio.com/2010-04-01/Accounts/ACCOUNT_SID/Messages.json',
        'api_key' => 'VOTRE_AUTH_TOKEN',
        'api_secret' => 'VOTRE_ACCOUNT_SID',
        'sender_number' => '+33123456789',
        'is_active' => false // Désactivé par défaut
    ];
    
    echo "Configuration exemple :\n";
    foreach ($exampleConfig as $key => $value) {
        echo "   - $key: $value\n";
    }
    
    echo "\n📝 Interface de configuration nécessaire\n";
    echo "=======================================\n";
    echo "Il faudrait créer une page dans les paramètres pour :\n";
    echo "1. Choisir le provider SMS (Twilio, OVH, etc.)\n";
    echo "2. Saisir les clés API\n";
    echo "3. Tester la configuration\n";
    echo "4. Activer/désactiver\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n🏁 Configuration terminée\n";

?>

