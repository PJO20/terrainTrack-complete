<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\SessionManager;

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
}

