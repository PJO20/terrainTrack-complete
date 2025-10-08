<?php
/**
 * API 2FA directe
 */

require_once '../../vendor/autoload.php';

use App\Service\SessionManager;
use App\Service\TwoFactorService;

// Headers JSON
header('Content-Type: application/json');

try {
    // Démarrer la session
    SessionManager::start();
    
    // Vérifier l'authentification
    if (!SessionManager::isAuthenticated()) {
        echo json_encode(['success' => false, 'error' => 'Non authentifié']);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
        exit;
    }
    
    $user = SessionManager::getUser();
    $userId = $user['id'];
    
    // Déterminer l'action
    $action = $_GET['action'] ?? 'enable';
    
    switch ($action) {
        case 'enable':
            // Créer le service 2FA
            $twoFactorService = new TwoFactorService();
            
            // Générer et stocker un code OTP
            $code = $twoFactorService->generateOtpCode();
            $result = $twoFactorService->storeOtpCode($userId, $code);
            
            if ($result) {
                // Envoyer le code (en mode test, il sera dans les logs)
                $twoFactorService->sendVerificationCode($userId, $user['email'], $code);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Code de vérification envoyé par email',
                    'debug_code' => $code // Pour les tests
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Erreur lors de la génération du code']);
            }
            break;
            
        case 'verify':
            $code = $_POST['code'] ?? '';
            
            if (empty($code)) {
                echo json_encode(['success' => false, 'error' => 'Code requis']);
                exit;
            }
            
            $twoFactorService = new TwoFactorService();
            
            if ($twoFactorService->verifyOtpCode($userId, $code)) {
                // Générer des codes de récupération
                $backupCodes = $twoFactorService->generateRecoveryCodes();
                
                // Activer la 2FA
                if ($twoFactorService->enableTwoFactor($userId, $backupCodes)) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Authentification à deux facteurs activée',
                        'backup_codes' => $backupCodes
                    ]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'activation']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Code invalide ou expiré']);
            }
            break;
            
        case 'disable':
            $twoFactorService = new TwoFactorService();
            
            // Vérifier si la 2FA est obligatoire
            if ($twoFactorService->isTwoFactorRequired($userId)) {
                echo json_encode(['success' => false, 'error' => 'La 2FA est obligatoire pour votre rôle']);
                exit;
            }
            
            if ($twoFactorService->disableTwoFactor($userId)) {
                echo json_encode(['success' => true, 'message' => 'Authentification à deux facteurs désactivée']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Erreur lors de la désactivation']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Erreur API 2FA: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>
