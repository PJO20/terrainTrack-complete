<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Service\AutoSaveService;
use App\Service\SessionManager;

header('Content-Type: application/json');

// Vérifier l'authentification
$currentUser = SessionManager::getCurrentUser();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$userId = $currentUser['id'];

try {
    switch ($method) {
        case 'POST':
            // Sauvegarder des données de formulaire
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['form_id']) || !isset($input['data'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Données manquantes']);
                exit;
            }

            $formId = $input['form_id'];
            $formData = $input['data'];

            $success = AutoSaveService::saveFormData($formId, $formData, $userId);
            
            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Données sauvegardées automatiquement',
                    'timestamp' => time()
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde']);
            }
            break;

        case 'GET':
            // Récupérer des données sauvegardées
            $formId = $_GET['form_id'] ?? '';
            
            if (empty($formId)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de formulaire manquant']);
                exit;
            }

            $savedData = AutoSaveService::getFormData($formId, $userId);
            
            if ($savedData !== null) {
                echo json_encode([
                    'success' => true,
                    'data' => $savedData,
                    'has_data' => true
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'data' => null,
                    'has_data' => false
                ]);
            }
            break;

        case 'DELETE':
            // Supprimer des données sauvegardées
            $input = json_decode(file_get_contents('php://input'), true);
            $formId = $input['form_id'] ?? '';
            
            if (empty($formId)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de formulaire manquant']);
                exit;
            }

            $success = AutoSaveService::clearFormData($formId, $userId);
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Données supprimées']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
            }
            break;

        case 'PUT':
            // Mettre à jour les paramètres d'auto-save
            $input = json_decode(file_get_contents('php://input'), true);
            $enabled = $input['enabled'] ?? null;
            
            if ($enabled === null) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Paramètre enabled manquant']);
                exit;
            }

            $success = AutoSaveService::setAutoSaveEnabled($userId, (bool)$enabled);
            
            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Paramètres d\'auto-save mis à jour',
                    'enabled' => (bool)$enabled
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            break;
    }
} catch (\Exception $e) {
    error_log("Erreur API auto-save: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur interne du serveur']);
}

