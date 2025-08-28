<?php

require_once __DIR__ . '/../vendor/autoload.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Debug Password Change</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
        .debug { background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
    <h1>🔍 Debug Password Change</h1>";

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
    
    echo "<div class='info'>
        <h3>🔍 Étape 1 : Vérifier l'utilisateur admin</h3>
    </div>";
    
    // Vérifier l'utilisateur admin
    $stmt = $pdo->prepare("SELECT id, email, username, is_admin, is_active, created_at FROM users WHERE email = 'admin@terraintrack.com'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p class='success'>✅ Admin trouvé :</p>";
        echo "<div class='debug'>";
        echo json_encode($admin, JSON_PRETTY_PRINT);
        echo "</div>";
    } else {
        echo "<p class='error'>❌ Admin non trouvé !</p>";
        exit;
    }
    
    echo "<div class='info'>
        <h3>🔍 Étape 2 : Tester les mots de passe</h3>
    </div>";
    
    // Tester différents mots de passe
    $passwordsToTest = [
        'admin123',
        'TerrainTrack2024!',
        'AdminSecure#2024',
        'TerrainMVC@2024'
    ];
    
    $stmt = $pdo->prepare("SELECT password FROM users WHERE email = 'admin@terraintrack.com'");
    $stmt->execute();
    $user = $stmt->fetch();
    $currentHash = $user['password'];
    
    echo "<p>Hash actuel : <code>" . substr($currentHash, 0, 20) . "...</code></p>";
    
    foreach ($passwordsToTest as $password) {
        if (password_verify($password, $currentHash)) {
            echo "<p class='success'>✅ Mot de passe valide : <code>$password</code></p>";
        } else {
            echo "<p class='warning'>❌ Mot de passe invalide : <code>$password</code></p>";
        }
    }
    
    echo "<div class='info'>
        <h3>🔍 Étape 3 : Forcer la mise à jour du mot de passe</h3>
    </div>";
    
    // Forcer la mise à jour avec un nouveau mot de passe
    $newPassword = 'TerrainTrack2024!';
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@terraintrack.com'");
    
    if ($stmt->execute([$hashedPassword])) {
        echo "<p class='success'>✅ Mot de passe mis à jour avec succès !</p>";
        
        // Vérifier que ça fonctionne
        $stmt = $pdo->prepare("SELECT password FROM users WHERE email = 'admin@terraintrack.com'");
        $stmt->execute();
        $user = $stmt->fetch();
        $newHash = $user['password'];
        
        if (password_verify($newPassword, $newHash)) {
            echo "<p class='success'>✅ Vérification réussie !</p>";
        } else {
            echo "<p class='error'>❌ Erreur de vérification</p>";
        }
        
        echo "<div class='success'>
            <h3>🎯 Nouveaux identifiants :</h3>
            <p>Email: <code>admin@terraintrack.com</code></p>
            <p>Mot de passe: <code>$newPassword</code></p>
        </div>";
        
    } else {
        echo "<p class='error'>❌ Erreur lors de la mise à jour</p>";
    }
    
    echo "<div class='info'>
        <h3>🔍 Étape 4 : Vérifier les permissions</h3>
    </div>";
    
    // Vérifier les rôles et permissions
    $stmt = $pdo->prepare("
        SELECT r.name as role_name, r.permissions 
        FROM user_roles ur 
        JOIN roles r ON ur.role_id = r.id 
        WHERE ur.user_id = ?
    ");
    $stmt->execute([$admin['id']]);
    $roles = $stmt->fetchAll();
    
    if ($roles) {
        echo "<p class='success'>✅ Rôles trouvés :</p>";
        foreach ($roles as $role) {
            echo "<div class='debug'>";
            echo "Rôle: " . $role['role_name'] . "<br>";
            echo "Permissions: " . $role['permissions'];
            echo "</div>";
        }
    } else {
        echo "<p class='warning'>⚠️ Aucun rôle trouvé pour l'admin</p>";
    }
    
} catch (\Throwable $e) {
    echo "<div class='error'>
        <h3>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</h3>
        <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>
    </div>";
}

echo "<h3>🎯 Actions :</h3>
<a href='/login' class='button'>🔐 Se connecter</a>
<a href='/fix_all_permissions.php' class='button'>🔧 Fix Permissions</a>
<a href='/intervention/list' class='button'>🔧 Interventions</a>
<a href='/' class='button'>Accueil</a>

<div class='info'>
    <h3>✅ Diagnostic terminé !</h3>
    <p>Utilisez les nouveaux identifiants pour vous connecter.</p>
</div>

</body>
</html>"; 