<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Service\SessionManager;

// Démarrer la session
SessionManager::startSession();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Debug Admin Access</title>
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
    <h1>🔍 Debug Admin Access</h1>";

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
        <h3>🔧 Étape 1 : Vérifier la session actuelle</h3>
    </div>";
    
    echo "<div class='debug'>";
    echo "Session actuelle :<br>";
    if (isset($_SESSION['user'])) {
        echo "✅ Utilisateur connecté : " . json_encode($_SESSION['user'], JSON_PRETTY_PRINT);
    } else {
        echo "❌ Aucun utilisateur connecté";
    }
    echo "</div>";
    
    echo "<div class='info'>
        <h3>🔧 Étape 2 : Vérifier l'utilisateur admin dans la base</h3>
    </div>";
    
    // Vérifier la structure de la table users
    $stmt = $pdo->query("DESCRIBE users");
    $userColumns = $stmt->fetchAll();
    $userColumnNames = array_column($userColumns, 'Field');
    
    echo "<p>Colonnes disponibles dans 'users' : " . implode(', ', $userColumnNames) . "</p>";
    
    // Trouver l'admin
    $selectColumns = [];
    if (in_array('id', $userColumnNames)) $selectColumns[] = 'id';
    if (in_array('email', $userColumnNames)) $selectColumns[] = 'email';
    if (in_array('name', $userColumnNames)) $selectColumns[] = 'name';
    if (in_array('username', $userColumnNames)) $selectColumns[] = 'username';
    
    $query = "SELECT " . implode(', ', $selectColumns) . " FROM users WHERE email = 'admin@terraintrack.com'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p class='success'>✅ Admin trouvé :</p>";
        foreach ($admin as $key => $value) {
            echo "<p>• $key: $value</p>";
        }
        
        echo "<div class='info'>
            <h3>🔧 Étape 3 : Vérifier les rôles de l'admin</h3>
        </div>";
        
        // Vérifier les rôles de l'admin
        $stmt = $pdo->prepare("
            SELECT r.* 
            FROM roles r
            INNER JOIN user_roles ur ON r.id = ur.role_id
            WHERE ur.user_id = ?
        ");
        $stmt->execute([$admin['id']]);
        $userRoles = $stmt->fetchAll();
        
        if ($userRoles) {
            echo "<p class='success'>✅ Rôles trouvés :</p>";
            foreach ($userRoles as $role) {
                echo "<p>• {$role['display_name']} ({$role['name']}) - Permissions: {$role['permissions']}</p>";
            }
        } else {
            echo "<p class='error'>❌ Aucun rôle assigné à l'admin</p>";
        }
        
        echo "<div class='info'>
            <h3>🔧 Étape 4 : Créer et assigner le rôle Super Admin</h3>
        </div>";
        
        // Créer le rôle Super Admin s'il n'existe pas
        $stmt = $pdo->prepare("SELECT * FROM roles WHERE name = 'super_admin'");
        $stmt->execute();
        $superAdminRole = $stmt->fetch();
        
        if (!$superAdminRole) {
            echo "<p>Création du rôle Super Admin...</p>";
            
            $stmt = $pdo->prepare("
                INSERT INTO roles (name, display_name, description, permissions, is_active, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $permissions = json_encode([
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
            
            if ($stmt->execute(['super_admin', 'Super Admin', 'Accès complet au système', $permissions, 1])) {
                $superAdminRoleId = $pdo->lastInsertId();
                echo "<p class='success'>✅ Rôle Super Admin créé avec l'ID: $superAdminRoleId</p>";
            } else {
                echo "<p class='error'>❌ Erreur lors de la création du rôle</p>";
            }
        } else {
            $superAdminRoleId = $superAdminRole['id'];
            echo "<p class='success'>✅ Rôle Super Admin existe déjà avec l'ID: $superAdminRoleId</p>";
        }
        
        // Assigner le rôle à l'admin
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_roles WHERE user_id = ? AND role_id = ?");
        $stmt->execute([$admin['id'], $superAdminRoleId]);
        $hasRole = $stmt->fetch()['count'] > 0;
        
        if (!$hasRole) {
            echo "<p>Attribution du rôle Super Admin...</p>";
            
            $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
            if ($stmt->execute([$admin['id'], $superAdminRoleId])) {
                echo "<p class='success'>✅ Rôle Super Admin assigné avec succès !</p>";
            } else {
                echo "<p class='error'>❌ Erreur lors de l'attribution du rôle</p>";
            }
        } else {
            echo "<p class='success'>✅ L'admin a déjà le rôle Super Admin</p>";
        }
        
        echo "<div class='info'>
            <h3>🔧 Étape 5 : Connecter l'admin</h3>
        </div>";
        
        // Forcer la connexion admin
        $_SESSION['user'] = [
            'id' => $admin['id'],
            'email' => $admin['email'],
            'role' => 'admin'
        ];
        
        echo "<p class='success'>✅ Admin connecté !</p>";
        
        echo "<div class='debug'>";
        echo "Session après connexion :<br>";
        echo json_encode($_SESSION['user'], JSON_PRETTY_PRINT);
        echo "</div>";
        
        echo "<div class='info'>
            <h3>🔧 Étape 6 : Test des permissions</h3>
        </div>";
        
        // Tester les permissions
        $userRepository = new \App\Repository\UserRepository($pdo);
        $roleRepository = new \App\Repository\RoleRepository($pdo);
        $permissionService = new \App\Service\PermissionService(
            $userRepository,
            $roleRepository,
            new \App\Repository\PermissionRepository($pdo)
        );
        
        $sessionManager = new \App\Service\SessionManager();
        $authMiddleware = new \App\Middleware\AuthorizationMiddleware($permissionService, $sessionManager);
        
        $permissionsToTest = ['system.admin', 'users.manage', 'roles.manage'];
        
        foreach ($permissionsToTest as $permission) {
            try {
                $authMiddleware->requirePermission($permission);
                echo "<p class='success'>✅ Permission '$permission' accordée !</p>";
            } catch (\Throwable $e) {
                echo "<p class='error'>❌ Erreur '$permission' : " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
        
    } else {
        echo "<p class='error'>❌ Admin non trouvé</p>";
        
        // Afficher tous les utilisateurs
        echo "<div class='info'>
            <h3>🔧 Tous les utilisateurs dans la base :</h3>
        </div>";
        
        $query = "SELECT " . implode(', ', $selectColumns) . " FROM users";
        $stmt = $pdo->query($query);
        while ($user = $stmt->fetch()) {
            echo "<p>• ";
            foreach ($user as $key => $value) {
                echo "$key: $value ";
            }
            echo "</p>";
        }
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
    <h3>✅ Debug Admin Access Terminé !</h3>
    <p>Vérifiez les résultats ci-dessus et testez l'accès admin.</p>
</div>

</body>
</html>"; 