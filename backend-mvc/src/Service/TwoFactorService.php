<?php

namespace App\Service;

use PDO;
use App\Service\Database;

class TwoFactorService
{
    private PDO $pdo;
    private ?EmailNotificationService $emailService;

    public function __construct(EmailNotificationService $emailService = null)
    {
        $this->emailService = $emailService;
        $this->pdo = Database::connect();
    }

    /**
     * Génère un code OTP à 6 chiffres
     */
    public function generateOtpCode(): string
    {
        return str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Stocke un code OTP pour un utilisateur
     */
    public function storeOtpCode(int $userId, string $code): bool
    {
        try {
            // Supprimer les anciens codes
            $stmt = $this->pdo->prepare("DELETE FROM user_2fa WHERE user_id = ? AND is_enabled = 0");
            $stmt->execute([$userId]);

            // Insérer le nouveau code
            $stmt = $this->pdo->prepare("
                INSERT INTO user_2fa (user_id, secret_key, is_enabled, created_at) 
                VALUES (?, ?, 0, NOW())
            ");
            return $stmt->execute([$userId, $code]);
        } catch (\Exception $e) {
            error_log("Erreur lors du stockage du code OTP: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie un code OTP
     */
    public function verifyOtpCode(int $userId, string $code): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id FROM user_2fa 
                WHERE user_id = ? AND secret_key = ? AND is_enabled = 0 
                AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
            ");
            $stmt->execute([$userId, $code]);
            $result = $stmt->fetch();

            if ($result) {
                // Supprimer le code utilisé
                $stmt = $this->pdo->prepare("DELETE FROM user_2fa WHERE id = ?");
                $stmt->execute([$result['id']]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            error_log("Erreur lors de la vérification du code OTP: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoie un code de vérification par email
     */
    public function sendVerificationCode(int $userId, string $email, string $code): bool
    {
        // Toujours logger le code pour debug
        error_log("🔐 Code 2FA pour $email: $code");
        
        if (!$this->emailService) {
            // Mode test - seulement les logs
            return true;
        }

        try {
            $subject = "🔐 Code de vérification TerrainTrack - $code";
            $message = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #2563eb;'>🔐 Authentification à deux facteurs</h2>
                    <p>Bonjour,</p>
                    <p>Votre code de vérification TerrainTrack est :</p>
                    <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0;'>
                        <h1 style='color: #2563eb; font-size: 2rem; letter-spacing: 0.2em; margin: 0;'>$code</h1>
                    </div>
                    <p><strong>⏰ Ce code expire dans 10 minutes.</strong></p>
                    <p>Si vous n'avez pas demandé ce code, ignorez cet email.</p>
                    <hr style='margin: 20px 0; border: none; border-top: 1px solid #e2e8f0;'>
                    <p style='color: #718096; font-size: 0.9rem;'>
                        TerrainTrack - Système de gestion de terrain<br>
                        Email envoyé le " . date('d/m/Y à H:i') . "
                    </p>
                </div>
            ";

            return $this->emailService->sendEmail($email, $subject, $message);
        } catch (\Exception $e) {
            error_log("Erreur lors de l'envoi du code 2FA: " . $e->getMessage());
            // Retourner true même en cas d'erreur email pour ne pas bloquer la connexion
            return true;
        }
    }

    /**
     * Vérifie si la 2FA est activée pour un utilisateur
     */
    public function isTwoFactorEnabled(int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT two_factor_enabled FROM users WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            return $user ? (bool)$user['two_factor_enabled'] : false;
        } catch (\Exception $e) {
            error_log("Erreur lors de la vérification 2FA: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si la 2FA est obligatoire pour un utilisateur
     */
    public function isTwoFactorRequired(int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT two_factor_required FROM users WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            return $user ? (bool)$user['two_factor_required'] : false;
        } catch (\Exception $e) {
            error_log("Erreur lors de la vérification 2FA obligatoire: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Génère des codes de récupération
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(md5(uniqid()), 0, 8));
        }
        return $codes;
    }

    /**
     * Active la 2FA pour un utilisateur
     */
    public function enableTwoFactor(int $userId, array $backupCodes = []): bool
    {
        try {
            $this->pdo->beginTransaction();

            // Activer la 2FA dans la table users
            $stmt = $this->pdo->prepare("
                UPDATE users SET 
                    two_factor_enabled = TRUE,
                    two_factor_backup_codes = ?
                WHERE id = ?
            ");
            $stmt->execute([json_encode($backupCodes), $userId]);

            // Nettoyer les codes temporaires
            $stmt = $this->pdo->prepare("DELETE FROM user_2fa WHERE user_id = ? AND is_enabled = 0");
            $stmt->execute([$userId]);

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            error_log("Erreur lors de l'activation 2FA: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Désactive la 2FA pour un utilisateur
     */
    public function disableTwoFactor(int $userId): bool
    {
        try {
            $this->pdo->beginTransaction();

            // Désactiver la 2FA dans la table users
            $stmt = $this->pdo->prepare("
                UPDATE users SET 
                    two_factor_enabled = FALSE,
                    two_factor_secret = NULL,
                    two_factor_backup_codes = NULL
                WHERE id = ?
            ");
            $stmt->execute([$userId]);

            // Supprimer tous les codes 2FA
            $stmt = $this->pdo->prepare("DELETE FROM user_2fa WHERE user_id = ?");
            $stmt->execute([$userId]);

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            error_log("Erreur lors de la désactivation 2FA: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie un code de récupération
     */
    public function verifyRecoveryCode(int $userId, string $code): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT two_factor_backup_codes FROM users WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user || !$user['two_factor_backup_codes']) {
                return false;
            }

            $backupCodes = json_decode($user['two_factor_backup_codes'], true);
            $key = array_search($code, $backupCodes);

            if ($key !== false) {
                // Supprimer le code utilisé
                unset($backupCodes[$key]);
                $backupCodes = array_values($backupCodes);

                $stmt = $this->pdo->prepare("
                    UPDATE users SET two_factor_backup_codes = ? WHERE id = ?
                ");
                $stmt->execute([json_encode($backupCodes), $userId]);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            error_log("Erreur lors de la vérification du code de récupération: " . $e->getMessage());
            return false;
        }
    }
}
