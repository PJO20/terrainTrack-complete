<?php
/**
 * API pour la gestion des permissions
 * Synchronise les modifications de la matrice avec la base de données
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Service/Database.php';
require_once __DIR__ . '/../../src/Service/SessionManager.php';
require_once __DIR__ . '/../../src/Repository/RoleRepository.php';
require_once __DIR__ . '/../../src/Repository/PermissionRepository.php';

use App\Service\Database;
use App\Service\SessionManager;
use App\Repository\RoleRepository;
use App\Repository\PermissionRepository;

// Configuration des headers pour API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Vérification de la session
SessionManager::requireLogin();
$currentUser = SessionManager::getCurrentUser();

if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

// Vérification des permissions admin
if (!isset($currentUser['is_admin']) || $currentUser['is_admin'] != 1) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé - Admin requis']);
    exit;
}

try {
    $pdo = Database::connect();
    $roleRepo = new RoleRepository($pdo);
    $permissionRepo = new PermissionRepository($pdo);
    
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    switch ($method) {
        case 'GET':
            handleGet($roleRepo, $permissionRepo, $action);
            break;
        case 'POST':
            handlePost($roleRepo, $permissionRepo, $action);
            break;
        case 'PUT':
            handlePut($roleRepo, $permissionRepo, $action);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}

/**
 * Gestion des requêtes GET
 */
function handleGet($roleRepo, $permissionRepo, $action) {
    switch ($action) {
        case 'roles':
            $roles = $roleRepo->findAll();
            echo json_encode(['roles' => $roles]);
            break;
            
        case 'permissions':
            $permissions = $permissionRepo->findAll();
            echo json_encode(['permissions' => $permissions]);
            break;
            
        case 'matrix':
            $roles = $roleRepo->findAll();
            $permissions = $permissionRepo->findAll();
            
            // Construire la matrice depuis la table role_permissions
            $matrix = [];
            $pdo = Database::connect();
            
            foreach ($roles as $role) {
                $stmt = $pdo->prepare("SELECT permission FROM role_permissions WHERE role_id = ?");
                $stmt->execute([$role['id']]);
                $rolePermissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $matrix[$role['id']] = $rolePermissions;
            }
            
            echo json_encode([
                'roles' => $roles,
                'permissions' => $permissions,
                'matrix' => $matrix
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Action non reconnue']);
    }
}

/**
 * Gestion des requêtes POST
 */
function handlePost($roleRepo, $permissionRepo, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'toggle-permission':
            togglePermission($roleRepo, $input);
            break;
            
        case 'save-matrix':
            saveMatrix($roleRepo, $input);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Action non reconnue']);
    }
}

/**
 * Gestion des requêtes PUT
 */
function handlePut($roleRepo, $permissionRepo, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'update-role':
            updateRole($roleRepo, $input);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Action non reconnue']);
    }
}

/**
 * Bascule une permission pour un rôle
 */
function togglePermission($roleRepo, $data) {
    if (!isset($data['role_id']) || !isset($data['permission']) || !isset($data['enabled'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Données manquantes']);
        return;
    }
    
    $roleId = (int)$data['role_id'];
    $permission = $data['permission'];
    $enabled = (bool)$data['enabled'];
    
    try {
        $pdo = Database::connect();
        
        // Récupérer le rôle
        $role = $roleRepo->findById($roleId);
        if (!$role) {
            http_response_code(404);
            echo json_encode(['error' => 'Rôle non trouvé']);
            return;
        }
        
        if ($enabled) {
            // Ajouter la permission dans la table role_permissions
            $stmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission) VALUES (?, ?)");
            $success = $stmt->execute([$roleId, $permission]);
        } else {
            // Retirer la permission de la table role_permissions
            $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ? AND permission = ?");
            $success = $stmt->execute([$roleId, $permission]);
        }
        
        if ($success) {
            // Mettre à jour la colonne permissions JSON pour la compatibilité
            $stmt = $pdo->prepare("SELECT permission FROM role_permissions WHERE role_id = ?");
            $stmt->execute([$roleId]);
            $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $updateStmt = $pdo->prepare("UPDATE roles SET permissions = ?, updated_at = NOW() WHERE id = ?");
            $updateStmt->execute([json_encode($permissions), $roleId]);
            
            // Log de l'action
            error_log("Permission $permission " . ($enabled ? 'accordée' : 'retirée') . " pour le rôle {$role['name']} par l'utilisateur " . ($_SESSION['user']['email'] ?? 'unknown'));
            
            echo json_encode([
                'success' => true,
                'message' => 'Permission mise à jour',
                'role_id' => $roleId,
                'permission' => $permission,
                'enabled' => $enabled
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la mise à jour']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur: ' . $e->getMessage()]);
    }
}

/**
 * Sauvegarde toute la matrice de permissions
 */
function saveMatrix($roleRepo, $data) {
    if (!isset($data['matrix'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Matrice manquante']);
        return;
    }
    
    $matrix = $data['matrix'];
    $success = true;
    $errors = [];
    
    try {
        $pdo = Database::connect();
        
        foreach ($matrix as $roleId => $permissions) {
            // Supprimer toutes les permissions existantes pour ce rôle
            $deleteStmt = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
            $deleteStmt->execute([$roleId]);
            
            // Ajouter les nouvelles permissions
            if (!empty($permissions)) {
                $insertStmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission) VALUES (?, ?)");
                foreach ($permissions as $permission) {
                    $insertStmt->execute([$roleId, $permission]);
                }
            }
            
            // Mettre à jour la colonne JSON pour la compatibilité
            $updateStmt = $pdo->prepare("UPDATE roles SET permissions = ?, updated_at = NOW() WHERE id = ?");
            $updateStmt->execute([json_encode($permissions), $roleId]);
        }
        
        if ($success) {
            error_log("Matrice de permissions sauvegardée par l'utilisateur " . ($_SESSION['user']['email'] ?? 'unknown'));
            
            echo json_encode([
                'success' => true,
                'message' => 'Matrice sauvegardée avec succès',
                'updated_roles' => count($matrix)
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erreurs lors de la sauvegarde', 'details' => $errors]);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur: ' . $e->getMessage()]);
    }
}

/**
 * Met à jour un rôle
 */
function updateRole($roleRepo, $data) {
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID du rôle manquant']);
        return;
    }
    
    $roleId = (int)$data['id'];
    unset($data['id']); // Retirer l'ID des données à mettre à jour
    
    $data['updated_at'] = date('Y-m-d H:i:s');
    
    try {
        $success = $roleRepo->update($roleId, $data);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Rôle mis à jour'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la mise à jour']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur: ' . $e->getMessage()]);
    }
}
?>
