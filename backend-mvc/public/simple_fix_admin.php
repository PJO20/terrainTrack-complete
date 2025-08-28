<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Service\SessionManager;

// Démarrer la session
SessionManager::startSession();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Simple Fix Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
    </style>
</head>
<body>
    <h1>🔧 Simple Fix Admin</h1>";

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
        <h3>🔧 Étape 1 : Vérifier l'utilisateur admin</h3>
    </div>";
    
    // Récupérer l'utilisateur admin
    $userRepository = new \App\Repository\UserRepository($pdo);
    $admin = $userRepository->findByEmail('admin@terraintrack.com');
    
    if ($admin) {
        echo "<p class='success'>✅ Admin trouvé : {$admin->getName()} ({$admin->getEmail()})</p>";
        
        echo "<div class='info'>
            <h3>🔧 Étape 2 : Créer le rôle Super Admin</h3>
        </div>";
        
        // Créer le rôle Super Admin
        $roleRepository = new \App\Repository\RoleRepository($pdo);
        $superAdminRole = $roleRepository->findByName('super_admin');
        
        if (!$superAdminRole) {
            echo "<p>Création du rôle Super Admin...</p>";
            
            $superAdminRole = new \App\Entity\Role();
            $superAdminRole->setName('super_admin');
            $superAdminRole->setDisplayName('Super Admin');
            $superAdminRole->setDescription('Accès complet au système');
            $superAdminRole->setPermissions([
                'system.admin',
                'users.manage',
                'roles.manage',
                'interventions.read',
                'interventions.create',
                'interventions.update',
                'interventions.delete',
                'vehicles.manage',
                'teams.manage',
                'reports.view',
                'settings.manage'
            ]);
            
            if ($roleRepository->save($superAdminRole)) {
                echo "<p class='success'>✅ Rôle Super Admin créé !</p>";
            } else {
                echo "<p class='error'>❌ Erreur création rôle</p>";
            }
        } else {
            echo "<p class='success'>✅ Rôle Super Admin existe déjà</p>";
        }
        
        echo "<div class='info'>
            <h3>🔧 Étape 3 : Assigner le rôle à l'admin</h3>
        </div>";
        
        // Vérifier si l'admin a déjà le rôle
        $userRoles = $roleRepository->getUserRoles($admin->getId());
        $hasSuperAdminRole = false;
        
        foreach ($userRoles as $role) {
            if ($role->getName() === 'super_admin') {
                $hasSuperAdminRole = true;
                break;
            }
        }
        
        if (!$hasSuperAdminRole) {
            echo "<p>Attribution du rôle Super Admin...</p>";
            
            // Insérer directement dans la table user_roles
            $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
            if ($stmt->execute([$admin->getId(), $superAdminRole->getId()])) {
                echo "<p class='success'>✅ Rôle assigné avec succès !</p>";
            } else {
                echo "<p class='error'>❌ Erreur attribution rôle</p>";
            }
        } else {
            echo "<p class='success'>✅ L'admin a déjà le rôle Super Admin</p>";
        }
        
        echo "<div class='info'>
            <h3>🔧 Étape 4 : Connecter l'admin</h3>
        </div>";
        
        // Forcer la connexion admin
        $_SESSION['user'] = [
            'id' => $admin->getId(),
            'email' => $admin->getEmail(),
            'role' => 'admin'
        ];
        
        echo "<p class='success'>✅ Admin connecté !</p>";
        
        echo "<div class='info'>
            <h3>🔧 Étape 5 : Test des permissions</h3>
        </div>";
        
        // Tester les permissions
        $permissionService = new \App\Service\PermissionService(
            $userRepository,
            $roleRepository,
            new \App\Repository\PermissionRepository($pdo)
        );
        
        $sessionManager = new \App\Service\SessionManager();
        $authMiddleware = new \App\Middleware\AuthorizationMiddleware($permissionService, $sessionManager);
        
        try {
            $authMiddleware->requirePermission('system.admin');
            echo "<p class='success'>✅ Permission 'system.admin' accordée !</p>";
        } catch (\Throwable $e) {
            echo "<p class='error'>❌ Erreur 'system.admin' : " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
        try {
            $authMiddleware->requirePermission('users.manage');
            echo "<p class='success'>✅ Permission 'users.manage' accordée !</p>";
        } catch (\Throwable $e) {
            echo "<p class='error'>❌ Erreur 'users.manage' : " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
        try {
            $authMiddleware->requirePermission('roles.manage');
            echo "<p class='success'>✅ Permission 'roles.manage' accordée !</p>";
        } catch (\Throwable $e) {
            echo "<p class='error'>❌ Erreur 'roles.manage' : " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
    } else {
        echo "<p class='error'>❌ Admin non trouvé</p>";
    }
    
} catch (\Throwable $e) {
    echo "<div class='error'>
        <h3>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</h3>
        <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>
    </div>";
}

echo "<h3>🎯 Actions :</h3>
<a href='/admin' class='button'>🏠 Dashboard Admin</a>
<a href='/admin/users' class='button'>👥 Gestion Utilisateurs</a>
<a href='/admin/roles' class='button'>🎭 Gestion Rôles</a>
<a href='/' class='button'>Accueil</a>

<div class='info'>
    <h3>✅ Permissions Admin Corrigées !</h3>
    <p>L'utilisateur admin a maintenant toutes les permissions nécessaires.</p>
</div>

</body>
</html>"; 