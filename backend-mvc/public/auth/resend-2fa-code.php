<?php
/**
 * Renvoyer un code 2FA
 */

require_once '../../vendor/autoload.php';

use App\Service\TwoFactorService;

header('Content-Type: application/json');

session_start();

try {
    // Vérifier qu'il y a un utilisateur en attente
    if (!isset($_SESSION['pending_2fa_user'])) {
        echo json_encode(['success' => false, 'error' => 'Session invalide']);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
        exit;
    }
    
    $user = $_SESSION['pending_2fa_user'];
    
    // Générer un nouveau code
    $twoFactorService = new TwoFactorService();
    $code = $twoFactorService->generateOtpCode();
    $twoFactorService->storeOtpCode($user['id'], $code);
    
    // Envoyer le code
    $emailToUse = $user['notification_email'] ?? $user['email'];
    $twoFactorService->sendVerificationCode($user['id'], $emailToUse, $code);
    
    echo json_encode([
        'success' => true,
        'message' => 'Nouveau code envoyé'
    ]);
    
} catch (Exception $e) {
    error_log("Erreur renvoi code 2FA: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de l\'envoi du code'
    ]);
}
?>
