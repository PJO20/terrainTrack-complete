<?php
/**
 * Script de diagnostic des permissions admin
 */

// Charger les dépendances
require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/Database.php';
require_once __DIR__ . '/src/Service/SessionManager.php';
require_once __DIR__ . '/src/Repository/UserRepository.php';
require_once __DIR__ . '/src/Repository/RoleRepository.php';
require_once __DIR__ . '/src/Repository/PermissionRepository.php';
require_once __DIR__ . '/src/Service/PermissionService.php';
require_once __DIR__ . '/src/Entity/User.php';
require_once __DIR__ . '/src/Entity/Role.php';
require_once __DIR__ . '/src/Entity/Permission.php';

echo "🔍 DIAGNOSTIC DES PERMISSIONS ADMIN\n";
echo "===================================\n\n";

try {
    // Connexion à la base de données
    $pdo = \App\Service\Database::connect();
    echo "✅ Connexion à la base de données réussie\n";

    // Récupérer l'utilisateur admin
    $userRepo = new \App\Repository\UserRepository();
    $adminUser = $userRepo->findByEmail('momo@gmail.com');
    
    if (!$adminUser) {
        echo "❌ Utilisateur admin non trouvé\n";
        exit;
    }
    
    echo "✅ Utilisateur admin trouvé: " . $adminUser->getEmail() . "\n";
    echo "   ID: " . $adminUser->getId() . "\n";
    echo "   Rôle: " . $adminUser->getRole() . "\n";
    echo "   Admin: " . ($adminUser->isAdmin() ? 'OUI' : 'NON') . "\n";
    echo "   Super Admin: " . ($adminUser->isSuperAdmin() ? 'OUI' : 'NON') . "\n\n";

    // Vérifier les permissions
    $permissionService = new \App\Service\PermissionService(
        $userRepo,
        new \App\Repository\RoleRepository($pdo),
        new \App\Repository\PermissionRepository($pdo)
    );

    echo "🔍 VÉRIFICATION DES PERMISSIONS:\n";
    echo "-------------------------------\n";

    $testPermissions = [
        'interventions.create',
        'interventions.read',
        'interventions.update',
        'interventions.delete',
        'interventions.manage',
        'system.access',
        'users.manage'
    ];

    foreach ($testPermissions as $permission) {
        $hasPermission = $permissionService->hasPermission($adminUser, $permission);
        echo ($hasPermission ? '✅' : '❌') . " $permission\n";
    }

    echo "\n🔍 PERMISSIONS UTILISATEUR:\n";
    echo "---------------------------\n";
    $userPermissions = $permissionService->getUserPermissions($adminUser);
    foreach ($userPermissions as $permission) {
        echo "✅ $permission\n";
    }

    echo "\n🔍 RÔLES UTILISATEUR:\n";
    echo "--------------------\n";
    $userRoles = $adminUser->getRoles();
    if (empty($userRoles)) {
        echo "❌ Aucun rôle assigné\n";
    } else {
        foreach ($userRoles as $role) {
            echo "✅ " . $role->getName() . " (" . $role->getDisplayName() . ")\n";
        }
    }

    echo "\n🔧 CORRECTION AUTOMATIQUE:\n";
    echo "--------------------------\n";

    // Vérifier si l'utilisateur a le rôle admin
    $roleRepo = new \App\Repository\RoleRepository($pdo);
    $adminRole = $roleRepo->findByName('admin');
    
    if (!$adminRole) {
        echo "❌ Rôle admin non trouvé dans la base de données\n";
        echo "🔧 Création du rôle admin...\n";
        
        // Créer le rôle admin
        $adminRole = new \App\Entity\Role();
        $adminRole->setName('admin');
        $adminRole->setDisplayName('Administrateur');
        $adminRole->setDescription('Gestion complète du système');
        
        // Assigner les permissions par défaut
        $defaultAdminPermissions = [
            'system.access', 'system.settings',
            'users.access', 'users.read', 'users.create', 'users.update', 'users.manage',
            'roles.access', 'roles.read', 'roles.create', 'roles.update', 'roles.delete',
            'vehicles.access', 'vehicles.read', 'vehicles.create', 'vehicles.update', 'vehicles.delete', 'vehicles.manage',
            'interventions.access', 'interventions.read', 'interventions.create', 'interventions.update', 'interventions.delete', 'interventions.manage',
            'teams.access', 'teams.read', 'teams.create', 'teams.update', 'teams.delete', 'teams.manage',
            'map.access', 'map.view', 'map.edit',
            'notifications.access', 'notifications.read', 'notifications.create', 'notifications.update', 'notifications.delete', 'notifications.manage',
            'reports.access', 'reports.read', 'reports.create',
            'dashboard.access', 'dashboard.view'
        ];
        
        foreach ($defaultAdminPermissions as $permissionName) {
            $permission = new \App\Entity\Permission();
            $permission->setName($permissionName);
            $permission->setDisplayName(ucfirst(str_replace('.', ' ', $permissionName)));
            $adminRole->addPermission($permission);
        }
        
        $roleRepo->save($adminRole);
        echo "✅ Rôle admin créé avec " . count($defaultAdminPermissions) . " permissions\n";
    } else {
        echo "✅ Rôle admin trouvé: " . $adminRole->getDisplayName() . "\n";
    }

    // Assigner le rôle admin à l'utilisateur
    if (!$adminUser->hasRole('admin')) {
        echo "🔧 Assignation du rôle admin à l'utilisateur...\n";
        $adminUser->addRole($adminRole);
        $userRepo->updateUserRoles($adminUser);
        echo "✅ Rôle admin assigné à l'utilisateur\n";
    } else {
        echo "✅ Utilisateur a déjà le rôle admin\n";
    }

    // Vérifier les permissions après correction
    echo "\n🔍 VÉRIFICATION APRÈS CORRECTION:\n";
    echo "---------------------------------\n";
    
    foreach ($testPermissions as $permission) {
        $hasPermission = $permissionService->hasPermission($adminUser, $permission);
        echo ($hasPermission ? '✅' : '❌') . " $permission\n";
    }

    echo "\n✅ DIAGNOSTIC TERMINÉ\n";
    echo "L'utilisateur admin devrait maintenant avoir accès aux interventions.\n";

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
