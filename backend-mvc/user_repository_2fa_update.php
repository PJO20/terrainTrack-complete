<?php
/**
 * Mise à jour de UserRepository pour la logique 2FA par rôle
 */

// Dans UserRepository::save(), remplacer la logique actuelle par:
if ($result) {
    $userId = $this->pdo->lastInsertId();
    $this->ensure2FAByRole($userId, $user->getRole());
}

// Ajouter cette méthode dans UserRepository:
private function ensure2FAByRole(int $userId, string $role): void
{
    try {
        // Rôles avec 2FA obligatoire
        $requiredRoles = ['admin', 'super_admin', 'manager'];
        
        if (in_array($role, $requiredRoles)) {
            // 2FA obligatoire
            $stmt = $this->pdo->prepare("UPDATE users SET two_factor_required = 1 WHERE id = ?");
            $stmt->execute([$userId]);
            
            // Activer automatiquement si pas encore fait
            $stmt = $this->pdo->prepare("SELECT two_factor_enabled FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user['two_factor_enabled']) {
                $secret2FA = 'MFRGG43B' . strtoupper(substr(md5($userId . time()), 0, 24));
                $stmt = $this->pdo->prepare("UPDATE users SET two_factor_enabled = 1, two_factor_secret = ? WHERE id = ?");
                $stmt->execute([$secret2FA, $userId]);
                error_log("2FA obligatoire activée pour $role ID: $userId");
            }
        } else {
            // 2FA optionnelle
            $stmt = $this->pdo->prepare("UPDATE users SET two_factor_required = 0 WHERE id = ?");
            $stmt->execute([$userId]);
            error_log("2FA optionnelle pour $role ID: $userId");
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la configuration 2FA pour l'utilisateur ID: $userId - " . $e->getMessage());
    }
}