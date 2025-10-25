<?php
/**
 * Script de vérification automatique de la 2FA pour les administrateurs
 * À exécuter lors de la création d'un nouvel administrateur
 */

require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/Database.php';

function ensureAdmin2FA($userId) {
    try {
        $pdo = \App\Service\Database::connect();
        
        // Vérifier si l'utilisateur est un administrateur
        $stmt = $pdo->prepare("SELECT role, two_factor_required, two_factor_enabled FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && in_array($user['role'], ['admin', 'super_admin'])) {
            // S'assurer que la 2FA est requise
            if (!$user['two_factor_required']) {
                $stmt = $pdo->prepare("UPDATE users SET two_factor_required = 1 WHERE id = ?");
                $stmt->execute([$userId]);
                error_log("2FA requise activée pour l'administrateur ID: $userId");
            }
            
            // Si la 2FA n'est pas encore activée, générer un secret
            if (!$user['two_factor_enabled']) {
                $secret2FA = 'MFRGG43B' . strtoupper(substr(md5($userId . time()), 0, 24));
                $stmt = $pdo->prepare("UPDATE users SET two_factor_enabled = 1, two_factor_secret = ? WHERE id = ?");
                $stmt->execute([$secret2FA, $userId]);
                error_log("2FA activée pour l'administrateur ID: $userId");
            }
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la vérification 2FA pour l'utilisateur ID: $userId - " . $e->getMessage());
    }
}
?>