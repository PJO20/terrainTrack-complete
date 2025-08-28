<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Service\SessionManager;

// Démarrer la session
SessionManager::startSession();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Interventions (Bypass)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
        .intervention { background: white; border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>📋 Liste des Interventions (Bypass)</h1>";

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
    
    // Forcer la connexion admin
    $userRepository = new \App\Repository\UserRepository($pdo);
    $admin = $userRepository->findByEmail('admin@terraintrack.com');
    
    if ($admin) {
        $_SESSION['user'] = [
            'id' => $admin->getId(),
            'email' => $admin->getEmail(),
            'role' => 'admin'
        ];
        
        echo "<div class='info'>
            <h3>✅ Admin connecté :</h3>
            <p><strong>ID :</strong> {$admin->getId()}</p>
            <p><strong>Email :</strong> {$admin->getEmail()}</p>
            <p><strong>Nom :</strong> {$admin->getName()}</p>
        </div>";
        
        // Récupérer les interventions directement
        $interventionRepository = new \App\Repository\InterventionRepository($pdo);
        $interventions = $interventionRepository->findAllFiltered();
        
        echo "<div class='info'>
            <h3>📊 Interventions trouvées : " . count($interventions) . "</h3>
        </div>";
        
        if (!empty($interventions)) {
            foreach ($interventions as $intervention) {
                echo "<div class='intervention'>
                    <h4>Intervention #{$intervention->getId()}</h4>
                    <p><strong>Titre :</strong> {$intervention->getTitle()}</p>
                    <p><strong>Description :</strong> {$intervention->getDescription()}</p>
                    <p><strong>Statut :</strong> {$intervention->getStatus()}</p>
                    <p><strong>Technicien :</strong> {$intervention->getTechnicien()}</p>
                    <p><strong>Date de création :</strong> {$intervention->getCreatedAt()}</p>
                    <p><strong>Priorité :</strong> {$intervention->getPriority()}</p>
                    <p><strong>Type :</strong> {$intervention->getType()}</p>
                </div>";
            }
        } else {
            echo "<div class='info'>
                <p>Aucune intervention trouvée dans la base de données.</p>
            </div>";
        }
        
        // Tester les permissions
        $roleRepository = new \App\Repository\RoleRepository($pdo);
        $permissionService = new \App\Service\PermissionService($userRepository, $roleRepository, new \App\Repository\PermissionRepository($pdo));
        
        echo "<div class='info'>
            <h3>🔐 Test des permissions :</h3>";
        
        $permissions = ['interventions.read', 'interventions.create', 'users.manage'];
        foreach ($permissions as $permission) {
            $hasPermission = $permissionService->hasPermission($admin, $permission);
            $status = $hasPermission ? '✅' : '❌';
            $color = $hasPermission ? 'success' : 'error';
            echo "<p class='$color'>$status $permission</p>";
        }
        echo "</div>";
        
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
<a href='/force_login.php' class='button'>Forcer Connexion Admin</a>
<a href='/intervention/list' class='button'>Interventions (via route)</a>
<a href='/test_interventions_direct.php' class='button'>Test Direct</a>
<a href='/' class='button'>Accueil</a>

</body>
</html>"; 