<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\SessionManager;
use App\Service\Database;
use App\Repository\UserRepository;
use PDO;

/**
 * Contrôleur pour la gestion des paramètres de sécurité
 */
class SecurityController
{
    /**
     * Met à jour le délai d'expiration de session
     */
    public function updateSessionTimeout(): void
    {
        SessionManager::start();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
            exit;
        }
        
        $timeoutMinutes = (int)($_POST['session_timeout'] ?? 30);
        
        // Validation simple du timeout
        if ($timeoutMinutes < 15 || $timeoutMinutes > 480) { // Min 15 min, Max 8 heures
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Délai invalide. Doit être entre 15 et 480 minutes.']);
            exit;
        }
        
        if (SessionManager::updateUserSessionTimeout($timeoutMinutes)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Délai d\'expiration de session mis à jour.']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour du délai d\'expiration.']);
        }
        exit;
    }
    
    /**
     * Gère les actions 2FA (activation/désactivation)
     */
    public function handle2FA(): void
    {
        // Désactiver l'affichage des erreurs pour les réponses JSON
        $oldErrorReporting = error_reporting(0);
        $oldDisplayErrors = ini_set('display_errors', 0);
        
        // Nettoyer tout output précédent
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Définir le Content-Type pour les réponses JSON
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            // Vérifier que c'est une requête POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
                return;
            }

            // Vérifier l'authentification
            if (!SessionManager::isAuthenticated()) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Utilisateur non authentifié']);
                return;
            }

            // Récupérer l'utilisateur connecté
            $user = SessionManager::getUser();
            if (!$user) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Session invalide']);
                return;
            }

            $userId = $user['id'];
            $userRole = $user['role'];
            $action = $_POST['action'] ?? 'enable';

            // Vérifier si l'utilisateur peut modifier la 2FA
            if (in_array($userRole, ['admin', 'super_admin', 'manager'])) {
                // Pour les rôles avec 2FA obligatoire, on ne peut que vérifier l'état
                echo json_encode([
                    'success' => true,
                    'message' => '2FA obligatoire pour votre rôle',
                    'two_factor_enabled' => true,
                    'two_factor_required' => true,
                    'can_disable' => false
                ]);
                return;
            }

            // Pour les techniciens et autres, permettre l'activation/désactivation
            $pdo = Database::connect();

            switch ($action) {
                case 'enable':
                    // Activer la 2FA
                    $secret2FA = 'MFRGG43B' . strtoupper(substr(md5($userId . time()), 0, 24));
                    
                    $stmt = $pdo->prepare("UPDATE users SET two_factor_enabled = 1, two_factor_secret = ? WHERE id = ?");
                    $result = $stmt->execute([$secret2FA, $userId]);
                    
                    if ($result) {
                        echo json_encode([
                            'success' => true,
                            'message' => '2FA activée avec succès',
                            'two_factor_enabled' => true,
                            'two_factor_required' => false,
                            'can_disable' => true
                        ]);
                    } else {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'activation de la 2FA']);
                    }
                    break;

                case 'disable':
                    // Désactiver la 2FA
                    $stmt = $pdo->prepare("UPDATE users SET two_factor_enabled = 0, two_factor_secret = NULL WHERE id = ?");
                    $result = $stmt->execute([$userId]);
                    
                    if ($result) {
                        echo json_encode([
                            'success' => true,
                            'message' => '2FA désactivée avec succès',
                            'two_factor_enabled' => false,
                            'two_factor_required' => false,
                            'can_disable' => true
                        ]);
                    } else {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Erreur lors de la désactivation de la 2FA']);
                    }
                    break;

                case 'status':
                    // Récupérer l'état de la 2FA
                    $stmt = $pdo->prepare("SELECT two_factor_enabled, two_factor_required FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user2FA = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user2FA) {
                        echo json_encode([
                            'success' => true,
                            'two_factor_enabled' => (bool)$user2FA['two_factor_enabled'],
                            'two_factor_required' => (bool)$user2FA['two_factor_required'],
                            'can_disable' => !$user2FA['two_factor_required']
                        ]);
                    } else {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération de l\'état 2FA']);
                    }
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
                    break;
            }
        } catch (\Exception $e) {
            error_log("Erreur handle2FA : " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Erreur serveur : ' . $e->getMessage()
            ]);
        } finally {
            // Restaurer les paramètres d'erreur
            error_reporting($oldErrorReporting);
            ini_set('display_errors', $oldDisplayErrors);
        }
    }
}

