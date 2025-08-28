<?php

require_once __DIR__ . '/../vendor/autoload.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Change Admin Password</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type='text'], input[type='password'] { width: 300px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        input[type='submit'] { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>🔐 Change Admin Password</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO(
            'mysql:host=localhost;dbname=terraintrack;charset=utf8mb4',
            'root',
            'root',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        
        $newPassword = $_POST['new_password'] ?? '';
        
        if (strlen($newPassword) < 8) {
            echo "<p class='error'>❌ Le mot de passe doit contenir au moins 8 caractères</p>";
        } else {
            // Mettre à jour le mot de passe admin
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@terraintrack.com'");
            
            if ($stmt->execute([$hashedPassword])) {
                echo "<div class='success'>
                    <h3>✅ Mot de passe admin mis à jour !</h3>
                    <p><strong>Nouveaux identifiants :</strong></p>
                    <p>Email: <code>admin@terraintrack.com</code></p>
                    <p>Mot de passe: <code>$newPassword</code></p>
                </div>";
                
                echo "<h3>🎯 Actions :</h3>
                <a href='/login' class='button'>🔐 Se connecter</a>
                <a href='/fix_all_permissions.php' class='button'>🔧 Fix Permissions</a>
                <a href='/' class='button'>Accueil</a>";
                
                exit;
            } else {
                echo "<p class='error'>❌ Erreur lors de la mise à jour du mot de passe</p>";
            }
        }
        
    } catch (\Throwable $e) {
        echo "<div class='error'>
            <h3>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</h3>
        </div>";
    }
}

echo "<div class='info'>
    <h3>🔐 Changer le mot de passe admin</h3>
    <p>Le mot de passe actuel <code>admin123</code> est détecté comme compromis par Google.</p>
    <p>Choisissez un nouveau mot de passe plus sécurisé :</p>
</div>

<form method='POST'>
    <div class='form-group'>
        <label for='new_password'>Nouveau mot de passe (min 8 caractères) :</label>
        <input type='password' id='new_password' name='new_password' required minlength='8' placeholder='Ex: TerrainTrack2024!'>
    </div>
    
    <div class='form-group'>
        <input type='submit' value='🔐 Changer le mot de passe'>
    </div>
</form>

<div class='info'>
    <h3>💡 Suggestions de mots de passe sécurisés :</h3>
    <ul>
        <li><code>TerrainTrack2024!</code></li>
        <li><code>AdminSecure#2024</code></li>
        <li><code>TerrainMVC@2024</code></li>
        <li><code>AdminTerrain2024!</code></li>
    </ul>
</div>

<h3>🎯 Actions alternatives :</h3>
<a href='/fix_all_permissions.php' class='button'>🔧 Fix All Permissions (avec admin123)</a>
<a href='/login' class='button'>🔐 Se connecter avec admin123</a>
<a href='/' class='button'>Accueil</a>

</body>
</html>"; 