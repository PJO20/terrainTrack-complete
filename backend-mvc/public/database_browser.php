<?php

require_once __DIR__ . '/../vendor/autoload.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Browser</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .sql { background: #f8f8f8; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🗄️ Database Browser</h1>";

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
        <h3>🔧 Tables disponibles</h3>
    </div>";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<table>
        <tr><th>Table</th><th>Actions</th></tr>";
    
    foreach ($tables as $table) {
        echo "<tr>
            <td>$table</td>
            <td>
                <a href='?action=view&table=$table' class='button'>Voir</a>
                <a href='?action=structure&table=$table' class='button'>Structure</a>
            </td>
        </tr>";
    }
    
    echo "</table>";
    
    // Afficher le contenu d'une table spécifique
    if (isset($_GET['action']) && isset($_GET['table'])) {
        $action = $_GET['action'];
        $table = $_GET['table'];
        
        echo "<div class='info'>
            <h3>🔧 Table : $table</h3>
        </div>";
        
        if ($action === 'structure') {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll();
            
            echo "<table>
                <tr><th>Colonne</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
            
            foreach ($columns as $column) {
                echo "<tr>
                    <td>{$column['Field']}</td>
                    <td>{$column['Type']}</td>
                    <td>{$column['Null']}</td>
                    <td>{$column['Key']}</td>
                    <td>{$column['Default']}</td>
                    <td>{$column['Extra']}</td>
                </tr>";
            }
            
            echo "</table>";
            
        } elseif ($action === 'view') {
            $stmt = $pdo->query("SELECT * FROM $table LIMIT 20");
            $rows = $stmt->fetchAll();
            
            if ($rows) {
                echo "<table><tr>";
                
                // En-têtes
                foreach (array_keys($rows[0]) as $header) {
                    echo "<th>$header</th>";
                }
                echo "</tr>";
                
                // Données
                foreach ($rows as $row) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                    }
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p class='error'>Aucune donnée dans la table $table</p>";
            }
        }
    }
    
    // Actions spéciales
    echo "<div class='info'>
        <h3>🔧 Actions spéciales</h3>
    </div>";
    
    echo "<a href='?action=fix_admin' class='button'>🔧 Fix Admin Permissions</a>
    <a href='?action=check_roles' class='button'>🎭 Check Roles</a>
    <a href='?action=check_users' class='button'>👥 Check Users</a>";
    
    if (isset($_GET['action'])) {
        $action = $_GET['action'];
        
        if ($action === 'fix_admin') {
            echo "<div class='info'>
                <h3>🔧 Fix Admin Permissions</h3>
            </div>";
            
            // Créer le rôle Super Admin
            $stmt = $pdo->prepare("SELECT * FROM roles WHERE name = 'super_admin'");
            $stmt->execute();
            $superAdminRole = $stmt->fetch();
            
            if (!$superAdminRole) {
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
                }
            } else {
                $superAdminRoleId = $superAdminRole['id'];
                echo "<p class='success'>✅ Rôle Super Admin existe déjà avec l'ID: $superAdminRoleId</p>";
            }
            
            // Trouver l'admin
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'admin@terraintrack.com'");
            $stmt->execute();
            $admin = $stmt->fetch();
            
            if ($admin) {
                // Assigner le rôle
                $stmt = $pdo->prepare("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)");
                if ($stmt->execute([$admin['id'], $superAdminRoleId])) {
                    echo "<p class='success'>✅ Rôle Super Admin assigné à l'admin !</p>";
                }
            }
            
        } elseif ($action === 'check_roles') {
            echo "<div class='info'>
                <h3>🎭 Rôles dans la base</h3>
            </div>";
            
            $stmt = $pdo->query("SELECT * FROM roles");
            $roles = $stmt->fetchAll();
            
            echo "<table>
                <tr><th>ID</th><th>Name</th><th>Display Name</th><th>Description</th><th>Permissions</th></tr>";
            
            foreach ($roles as $role) {
                echo "<tr>
                    <td>{$role['id']}</td>
                    <td>{$role['name']}</td>
                    <td>{$role['display_name']}</td>
                    <td>{$role['description']}</td>
                    <td>{$role['permissions']}</td>
                </tr>";
            }
            
            echo "</table>";
            
        } elseif ($action === 'check_users') {
            echo "<div class='info'>
                <h3>👥 Utilisateurs dans la base</h3>
            </div>";
            
            $stmt = $pdo->query("SELECT * FROM users");
            $users = $stmt->fetchAll();
            
            if ($users) {
                echo "<table><tr>";
                
                // En-têtes
                foreach (array_keys($users[0]) as $header) {
                    echo "<th>$header</th>";
                }
                echo "</tr>";
                
                // Données
                foreach ($users as $user) {
                    echo "<tr>";
                    foreach ($user as $value) {
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                    }
                    echo "</tr>";
                }
                
                echo "</table>";
            }
        }
    }
    
} catch (\Throwable $e) {
    echo "<div class='error'>
        <h3>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</h3>
        <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>
    </div>";
}

echo "<h3>🎯 Actions :</h3>
<a href='/debug_admin_access.php' class='button'>🔍 Debug Admin Access</a>
<a href='/admin' class='button'>🏠 Dashboard Admin</a>
<a href='/' class='button'>Accueil</a>

</body>
</html>"; 