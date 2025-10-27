<?php

namespace App\Service;

use PDO;
use App\Service\Database;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class TwoFactorService
{
    private PDO $pdo;
    private string $smtpHost;
    private int $smtpPort;
    private string $smtpUsername;
    private string $smtpPassword;
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        $this->pdo = Database::connect();
        
        // Configuration SMTP Gmail (même que EmailNotificationService)
        $this->smtpHost = 'smtp.gmail.com';
        $this->smtpPort = 587;
        $this->smtpUsername = 'pjorsini20@gmail.com';
        $this->smtpPassword = 'gmqncgtfunpfnkjh';
        $this->fromEmail = 'pjorsini20@gmail.com';
        $this->fromName = 'TerrainTrack';
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
            // Supprimer TOUS les anciens codes pour cet utilisateur
            $stmt = $this->pdo->prepare("DELETE FROM user_2fa WHERE user_id = ?");
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
    /**
     * Envoie un code de vérification par email avec PHPMailer
     */
    public function sendVerificationCode(int $userId, string $email, string $code): bool
    {
        // Toujours logger le code pour debug
        error_log("🔐 Code 2FA pour $email: $code");
        
        try {
            $mail = new PHPMailer(true);
            
            // Configuration SMTP Gmail
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUsername;
            $mail->Password = $this->smtpPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtpPort;
            
            // Désactiver la vérification SSL pour les tests locaux
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Configuration de l'email
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = "🔐 Code de vérification TerrainTrack - $code";
            
            // Corps de l'email HTML
            $mail->Body = $this->getEmailTemplate($code);
            $mail->CharSet = 'UTF-8';
            
            // Envoyer l'email
            $result = $mail->send();
            
            if ($result) {
                error_log("✅ Code 2FA envoyé avec succès à: $email");
            } else {
                error_log("❌ Échec de l'envoi du code 2FA à: $email");
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("❌ Erreur PHPMailer lors de l'envoi du code 2FA: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Template HTML pour l'email de code 2FA
     */
    private function getEmailTemplate(string $code): string
    {
        return "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff;'>
                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;'>
                    <h1 style='color: white; margin: 0; font-size: 28px;'>🔐 TerrainTrack</h1>
                    <p style='color: white; margin: 10px 0 0 0; font-size: 16px;'>Authentification à deux facteurs</p>
                </div>
                
                <div style='padding: 40px 30px;'>
                    <h2 style='color: #333; margin-bottom: 20px;'>Code de vérification</h2>
                    <p style='color: #666; font-size: 16px; line-height: 1.6; margin-bottom: 30px;'>
                        Bonjour,<br><br>
                        Votre code de vérification TerrainTrack est :
                    </p>
                    
                    <div style='background: #f8f9fa; padding: 30px; border-radius: 12px; text-align: center; margin: 30px 0; border: 2px solid #e9ecef;'>
                        <h1 style='color: #2563eb; font-size: 3rem; letter-spacing: 0.3em; margin: 0; font-weight: bold; font-family: monospace;'>$code</h1>
                    </div>
                    
                    <div style='background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin: 20px 0;'>
                        <p style='color: #856404; margin: 0; font-weight: bold;'>⏰ Ce code expire dans 10 minutes.</p>
                    </div>
                    
                    <p style='color: #666; font-size: 14px; line-height: 1.6;'>
                        Si vous n'avez pas demandé ce code, ignorez cet email et vérifiez la sécurité de votre compte.
                    </p>
                </div>
                
                <div style='background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #e9ecef;'>
                    <p style='color: #6c757d; font-size: 12px; margin: 0;'>
                        TerrainTrack - Système de gestion de terrain<br>
                        Email envoyé le " . date('d/m/Y à H:i') . "
                    </p>
                </div>
            </div>
        ";
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
