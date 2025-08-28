<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Service\SessionManager;

// Démarrer la session
SessionManager::startSession();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Debug Permissions</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .debug { background: #e8f4f8; padding: 15px; border-radius: 5px; margin: 20px 0; font-family: monospace; }
    </style>
</head>
<body>
    <h1>🔍 Debug Permissions</h1>";

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
        <h3>📋 Session actuelle :</h3>
        <p><strong>Session ID :</strong> " . session_id() . "</p>
        <p><strong>Utilisateur connecté :</strong> " . (isset($_SESSION['user']) ? 'Oui' : 'Non') . "</p>";
    
    if (isset($_SESSION['user'])) {
        echo "<p><strong>User ID :</strong> {$_SESSION['user']['id']}</p>";
        echo "<p><strong>User Email :</strong> {$_SESSION['user']['email']}</p>";
        echo "<p><strong>User Role :</strong> {$_SESSION['user']['role']}</p>";
    }
    echo "</div>";
    
    $userRepository = new \App\Repository\UserRepository($pdo);
    $roleRepository = new \App\Repository\RoleRepository($pdo);
    $permissionRepository = new \App\Repository\PermissionRepository($pdo);
    $permissionService = new \App\Service\PermissionService($userRepository, $roleRepository, $permissionRepository);
    
    // Test avec l'admin
    $admin = $userRepository->findByEmail('admin@terraintrack.com');
    
    if ($admin) {
        echo "<div class='info'>
            <h3>👤 Admin trouvé :</h3>
            <p><strong>ID :</strong> {$admin->getId()}</p>
            <p><strong>Email :</strong> {$admin->getEmail()}</p>
            <p><strong>Nom :</strong> {$admin->getName()}</p>
            <p><strong>Admin :</strong> " . ($admin->isAdmin() ? 'Oui' : 'Non') . "</p>
            <p><strong>Rôles :</strong> " . implode(', ', array_map(function($role) { return $role->getName(); }, $admin->getRoles())) . "</p>
        </div>";
        
        // Tester les permissions de l'admin
        echo "<div class='info'>
            <h3>🔐 Test des permissions admin :</h3>";
        
        $permissions = ['interventions.read', 'interventions.create', 'users.manage'];
        foreach ($permissions as $permission) {
            $hasPermission = $permissionService->hasPermission($admin, $permission);
            $status = $hasPermission ? '✅' : '❌';
            $color = $hasPermission ? 'success' : 'error';
            echo "<p class='$color'>$status $permission</p>";
        }
        echo "</div>";
        
        // Tester avec l'utilisateur connecté
        if (isset($_SESSION['user'])) {
            $currentUser = $userRepository->findById($_SESSION['user']['id']);
            
            if ($currentUser) {
                echo "<div class='info'>
                    <h3>👤 Utilisateur connecté :</h3>
                    <p><strong>ID :</strong> {$currentUser->getId()}</p>
                    <p><strong>Email :</strong> {$currentUser->getEmail()}</p>
                    <p><strong>Nom :</strong> {$currentUser->getName()}</p>
                    <p><strong>Admin :</strong> " . ($currentUser->isAdmin() ? 'Oui' : 'Non') . "</p>
                    <p><strong>Rôles :</strong> " . implode(', ', array_map(function($role) { return $role->getName(); }, $currentUser->getRoles())) . "</p>
                </div>";
                
                echo "<div class='info'>
                    <h3>🔐 Test des permissions utilisateur connecté :</h3>";
                
                foreach ($permissions as $permission) {
                    $hasPermission = $permissionService->hasPermission($currentUser, $permission);
                    $status = $hasPermission ? '✅' : '❌';
                    $color = $hasPermission ? 'success' : 'error';
                    echo "<p class='$color'>$status $permission</p>";
                }
                echo "</div>";
                
                // Debug détaillé des rôles et permissions
                echo "<div class='debug'>
                    <h3>🔍 Debug détaillé des rôles :</h3>";
                
                foreach ($currentUser->getRoles() as $role) {
                    echo "<p><strong>Rôle :</strong> {$role->getName()}</p>";
                    echo "<p><strong>Permissions :</strong> " . json_encode($role->getPermissions()) . "</p>";
                }
                echo "</div>";
                
            } else {
                echo "<div class='error'>
                    <h3>❌ Utilisateur connecté non trouvé en base</h3>
                </div>";
            }
        }
        
        // Forcer la connexion admin si nécessaire
        if (!isset($_SESSION['user']) || $_SESSION['user']['email'] !== 'admin@terraintrack.com') {
            echo "<div class='info'>
                <h3>🔄 Forçage de la connexion admin...</h3>";
            
            $_SESSION['user'] = [
                'id' => $admin->getId(),
                'email' => $admin->getEmail(),
                'role' => 'admin'
            ];
            
            echo "<p>✅ Admin connecté !</p>
            <p><strong>ID :</strong> {$admin->getId()}</p>
            <p><strong>Email :</strong> {$admin->getEmail()}</p>
            </div>";
        }
        
    } else {
        echo "<div class='error'>
            <h3>❌ Admin non trouvé</h3>
        </div>";
    }
    
} catch (\Throwable $e) {
    echo "<div class='error'>
        <h3>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</h3>
        <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>
    </div>";
}

echo "<h3>🎯 Actions :</h3>
<a href='/force_login.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px;'>Forcer Connexion Admin</a>
<a href='/intervention/list' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px;'>Tester Interventions</a>
<a href='/' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px;'>Accueil</a>

</body>
</html>"; 