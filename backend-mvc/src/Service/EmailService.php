<?php

declare(strict_types=1);

namespace App\Service;

class EmailService
{
    private string $smtpHost;
    private int $smtpPort;
    private string $smtpUsername;
    private string $smtpPassword;
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        $this->smtpHost = $_ENV['SMTP_HOST'] ?? 'localhost';
        $this->smtpPort = (int)($_ENV['SMTP_PORT'] ?? 587);
        $this->smtpUsername = $_ENV['SMTP_USERNAME'] ?? '';
        $this->smtpPassword = $_ENV['SMTP_PASSWORD'] ?? '';
        $this->fromEmail = $_ENV['FROM_EMAIL'] ?? 'noreply@terraintrack.com';
        $this->fromName = $_ENV['FROM_NAME'] ?? 'TerrainTrack';
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
     * Envoie un email de bienvenue
     */
    public function sendWelcomeEmail(string $toEmail, string $toName): bool
    {
        $subject = 'Bienvenue sur TerrainTrack !';
        
        $htmlBody = $this->getWelcomeEmailTemplate($toName);
        $textBody = $this->getWelcomeEmailTextTemplate($toName);

        return $this->sendEmail($toEmail, $toName, $subject, $htmlBody, $textBody);
    }

    /**
     * Envoie un email générique
     */
    public function sendEmail(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        try {
            // Pour le développement, on simule l'envoi d'email
            // En production, vous devriez utiliser PHPMailer ou SwiftMailer
            
            if (($_ENV['APP_ENV'] ?? 'development') === 'development') {
                // En mode développement, on log l'email au lieu de l'envoyer
                $this->logEmail($toEmail, $toName, $subject, $htmlBody);
                return true;
            }

            // Configuration pour l'envoi réel d'email
            $headers = [
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
                'Reply-To: ' . $this->fromEmail,
                'X-Mailer: PHP/' . phpversion()
            ];

            $success = mail($toEmail, $subject, $htmlBody, implode("\r\n", $headers));
            
            if (!$success) {
                error_log("Erreur lors de l'envoi de l'email à $toEmail");
            }
            
            return $success;

        } catch (\Exception $e) {
            error_log('Erreur EmailService: ' . $e->getMessage());
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
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>Email Log</h2>
                <p><strong>À:</strong> $toName &lt;$toEmail&gt;</p>
                <p><strong>Sujet:</strong> $subject</p>
                <p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>
            </div>
            <div class='content'>
                $htmlBody
            </div>
        </body>
        </html>
        ";

        file_put_contents($filename, $logContent);
        error_log("Email logué dans: $filename");
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
                    <p><strong>Ce lien est valide pendant 1 heure.</strong></p>
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

        Ce lien est valide pendant 1 heure.

        Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer cet e-mail en toute sécurité.

        Cet e-mail a été envoyé automatiquement, merci de ne pas y répondre.
        © " . date('Y') . " TerrainTrack - Tous droits réservés
        ";
    }

    /**
     * Template HTML pour l'email de bienvenue
     */
    private function getWelcomeEmailTemplate(string $name): string
    {
        return "
        <!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Bienvenue sur TerrainTrack</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2346a9; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .button { display: inline-block; background: #2346a9; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🚛 Bienvenue sur TerrainTrack !</h1>
                </div>
                <div class='content'>
                    <h2>Bonjour $name,</h2>
                    <p>Félicitations ! Votre compte TerrainTrack a été créé avec succès.</p>
                    <p>Vous pouvez maintenant accéder à toutes les fonctionnalités de notre plateforme de gestion de véhicules et d'interventions.</p>
                    <p style='text-align: center;'>
                        <a href='http://localhost:8888/login' class='button'>Se connecter</a>
                    </p>
                    <p>Si vous avez des questions, n'hésitez pas à nous contacter.</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " TerrainTrack - Tous droits réservés</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Template texte pour l'email de bienvenue
     */
    private function getWelcomeEmailTextTemplate(string $name): string
    {
        return "
        Bonjour $name,

        Félicitations ! Votre compte TerrainTrack a été créé avec succès.

        Vous pouvez maintenant accéder à toutes les fonctionnalités de notre plateforme de gestion de véhicules et d'interventions.

        Connectez-vous ici : http://localhost:8888/login

        Si vous avez des questions, n'hésitez pas à nous contacter.

        © " . date('Y') . " TerrainTrack - Tous droits réservés
        ";
    }
}
