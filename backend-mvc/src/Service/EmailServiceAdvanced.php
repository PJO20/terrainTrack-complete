<?php

declare(strict_types=1);

namespace App\Service;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailServiceAdvanced
{
    private array $config;
    private string $smtpHost;
    private int $smtpPort;
    private string $smtpUsername;
    private string $smtpPassword;
    private string $fromEmail;
    private string $fromName;
    private bool $useRealSending;

    public function __construct()
    {
        // Charger la configuration depuis .env
        $this->loadEnvConfiguration();
        
        // Déterminer si on utilise l'envoi réel ou le logging
        $this->useRealSending = $this->isRealSendingConfigured();
    }

    /**
     * Charge la configuration depuis le fichier .env
     */
    private function loadEnvConfiguration(): void
    {
        // Charger le fichier .env
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    [$key, $value] = explode('=', $line, 2);
                    $_ENV[trim($key)] = trim($value);
                }
            }
        }
        
        // Configuration SMTP depuis .env
        $this->smtpHost = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $this->smtpPort = (int)($_ENV['SMTP_PORT'] ?? 587);
        $this->smtpUsername = $_ENV['SMTP_USERNAME'] ?? '';
        $this->smtpPassword = $_ENV['SMTP_PASSWORD'] ?? '';
        $this->fromEmail = $_ENV['FROM_EMAIL'] ?? 'noreply@terraintrack.com';
        $this->fromName = $_ENV['FROM_NAME'] ?? 'TerrainTrack';
    }

    /**
     * Vérifie si l'envoi réel est configuré
     */
    private function isRealSendingConfigured(): bool
    {
        return !empty($this->smtpUsername) && 
               !empty($this->smtpPassword) && 
               $this->smtpUsername !== 'votre-email@gmail.com' &&
               $this->smtpPassword !== 'votre-mot-de-passe-app' &&
               filter_var($this->smtpUsername, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Envoie un email de réinitialisation de mot de passe
     */
    public function sendPasswordResetEmail(string $toEmail, string $toName, string $resetLink): bool
    {
        $subject = 'Réinitialisation de votre mot de passe - TerrainTrack';
        
        $htmlBody = $this->getPasswordResetEmailTemplate($toName, $resetLink);
        $textBody = $this->getPasswordResetEmailTextTemplate($toName, $resetLink);

        return $this->sendEmail($toEmail, $toName, $subject, $htmlBody, $textBody);
    }

    /**
     * Envoie un email générique
     */
    public function sendEmail(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        try {
            if ($this->useRealSending) {
                return $this->sendWithPHPMailer($toEmail, $toName, $subject, $htmlBody, $textBody);
            } else {
                // Mode développement : logger l'email
                $this->logEmail($toEmail, $toName, $subject, $htmlBody);
                return true;
            }

        } catch (\Exception $e) {
            error_log('Erreur EmailServiceAdvanced: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoie un email avec PHPMailer
     */
    private function sendWithPHPMailer(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Configuration du serveur SMTP
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUsername;
            $mail->Password = $this->smtpPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtpPort;

            // Configuration de l'expéditeur
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addReplyTo($this->fromEmail, $this->fromName);

            // Configuration du destinataire
            $mail->addAddress($toEmail, $toName);

            // Configuration du contenu
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody;

            // Envoi de l'email
            $mail->send();
            
            error_log("Email envoyé avec succès à $toEmail");
            return true;

        } catch (Exception $e) {
            error_log("Erreur PHPMailer: {$mail->ErrorInfo}");
            return false;
        }
    }

    /**
     * Log l'email en mode développement
     */
    private function logEmail(string $toEmail, string $toName, string $subject, string $htmlBody): void
    {
        $logDir = __DIR__ . '/../../logs/emails';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $filename = $logDir . '/email_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.html';
        
        $logContent = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Email Log - $subject</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { background: #f5f5f5; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
                .content { border: 1px solid #ddd; padding: 20px; border-radius: 5px; }
                .config { background: #e8f4fd; padding: 10px; border-radius: 5px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>📧 Email Log - Mode Développement</h2>
                <p><strong>À:</strong> $toName &lt;$toEmail&gt;</p>
                <p><strong>Sujet:</strong> $subject</p>
                <p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>
            </div>
            <div class='content'>
                $htmlBody
            </div>
            <div class='config'>
                <h3>🔧 Configuration Email</h3>
                <p><strong>Mode:</strong> " . ($this->useRealSending ? 'Envoi réel (PHPMailer)' : 'Logging (Développement)') . "</p>
                <p><strong>SMTP Host:</strong> $this->smtpHost</p>
                <p><strong>SMTP Port:</strong> $this->smtpPort</p>
                <p><strong>From:</strong> $this->fromName &lt;$this->fromEmail&gt;</p>
                " . ($this->useRealSending ? 
                    "<p><strong>Username:</strong> $this->smtpUsername</p>" : 
                    "<p><strong>Note:</strong> Configurez vos identifiants SMTP dans config/email_config.php pour activer l'envoi réel</p>"
                ) . "
            </div>
        </body>
        </html>
        ";

        file_put_contents($filename, $logContent);
        error_log("📧 Email logué dans: $filename");
    }

    /**
     * Template HTML pour l'email de réinitialisation de mot de passe
     */
    private function getPasswordResetEmailTemplate(string $name, string $resetLink): string
    {
        return "
        <!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Réinitialisation de mot de passe</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2346a9; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .button { display: inline-block; background: #2346a9; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
                .button:hover { background: #1d357a; }
                .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
                .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 4px; margin: 20px 0; border: 1px solid #ffeaa7; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔐 Réinitialisation de mot de passe</h1>
                </div>
                <div class='content'>
                    <h2>Bonjour $name,</h2>
                    <p>Vous avez demandé la réinitialisation de votre mot de passe pour votre compte TerrainTrack.</p>
                    <p>Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe :</p>
                    <p style='text-align: center;'>
                        <a href='$resetLink' class='button'>Réinitialiser mon mot de passe</a>
                    </p>
                    <div class='warning'>
                        <strong>⚠️ Important :</strong> Ce lien est valide pendant 1 heure seulement.
                    </div>
                    <p>Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer cet e-mail en toute sécurité.</p>
                    <p>Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :</p>
                    <p style='word-break: break-all; background: #eee; padding: 10px; border-radius: 4px; font-family: monospace;'>$resetLink</p>
                </div>
                <div class='footer'>
                    <p>Cet e-mail a été envoyé automatiquement, merci de ne pas y répondre.</p>
                    <p>© " . date('Y') . " TerrainTrack - Tous droits réservés</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Template texte pour l'email de réinitialisation de mot de passe
     */
    private function getPasswordResetEmailTextTemplate(string $name, string $resetLink): string
    {
        return "
        Bonjour $name,

        Vous avez demandé la réinitialisation de votre mot de passe pour votre compte TerrainTrack.

        Cliquez sur ce lien pour créer un nouveau mot de passe :
        $resetLink

        ⚠️ IMPORTANT : Ce lien est valide pendant 1 heure seulement.

        Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer cet e-mail en toute sécurité.

        Cet e-mail a été envoyé automatiquement, merci de ne pas y répondre.
        © " . date('Y') . " TerrainTrack - Tous droits réservés
        ";
    }

    /**
     * Retourne le statut de la configuration
     */
    public function getConfigurationStatus(): array
    {
        return [
            'real_sending_enabled' => $this->useRealSending,
            'smtp_host' => $this->smtpHost,
            'smtp_port' => $this->smtpPort,
            'from_email' => $this->fromEmail,
            'from_name' => $this->fromName,
            'username_configured' => !empty($this->smtpUsername) && $this->smtpUsername !== 'votre-email@gmail.com',
            'password_configured' => !empty($this->smtpPassword) && $this->smtpPassword !== 'votre-mot-de-passe-app'
        ];
    }
}
