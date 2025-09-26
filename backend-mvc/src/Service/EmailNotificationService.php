<?php

namespace App\Service;

use App\Repository\UserRepository;
use App\Repository\NotificationLogsRepository;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailNotificationService
{
    private UserRepository $userRepository;
    private NotificationLogsRepository $notificationLogsRepository;
    private string $smtpHost;
    private string $smtpPort;
    private string $smtpUsername;
    private string $smtpPassword;
    private string $fromEmail;
    private string $fromName;
    private bool $smtpEnabled;

    public function __construct(
        UserRepository $userRepository,
        NotificationLogsRepository $notificationLogsRepository
    ) {
        $this->userRepository = $userRepository;
        $this->notificationLogsRepository = $notificationLogsRepository;
        
        // Charger la configuration depuis les variables d'environnement
        $this->smtpHost = $_ENV['SMTP_HOST'] ?? 'localhost';
        $this->smtpPort = $_ENV['SMTP_PORT'] ?? '587';
        $this->smtpUsername = $_ENV['SMTP_USERNAME'] ?? '';
        $this->smtpPassword = $_ENV['SMTP_PASSWORD'] ?? '';
        $this->fromEmail = $_ENV['FROM_EMAIL'] ?? 'noreply@terraintrack.com';
        $this->fromName = $_ENV['FROM_NAME'] ?? 'TerrainTrack';
        $this->smtpEnabled = !empty($this->smtpUsername) && !empty($this->smtpPassword);
    }

    /**
     * Envoie une notification d'assignation d'intervention
     */
    public function sendInterventionAssignmentNotification(
        int $technicianId,
        string $interventionTitle,
        string $interventionDescription,
        string $interventionDate,
        string $interventionLocation
    ): bool {
        $technician = $this->userRepository->findById($technicianId);
        
        if (!$technician) {
            error_log("Technicien ID $technicianId non trouvé pour l'assignation d'intervention");
            return false;
        }

        // Convertir l'objet User en tableau si nécessaire
        $technicianData = is_object($technician) ? [
            'id' => $technician->getId(),
            'name' => $technician->getName(),
            'email' => $technician->getEmail()
        ] : $technician;

        $subject = "🔧 Nouvelle intervention assignée - {$interventionTitle}";
        $body = $this->generateInterventionAssignmentEmail(
            $technicianData,
            $interventionTitle,
            $interventionDescription,
            $interventionDate,
            $interventionLocation
        );

        $success = $this->sendEmail($technicianData['email'], $subject, $body);
        
        // Logger la notification
        $this->logNotification(
            $technicianId,
            'email',
            $subject,
            $body,
            $success ? 'sent' : 'failed'
        );

        return $success;
    }

    /**
     * Envoie un rappel d'entretien
     */
    public function sendMaintenanceReminderNotification(
        int $technicianId,
        string $vehicleName,
        string $maintenanceType,
        string $dueDate,
        string $priority = 'medium'
    ): bool {
        $technician = $this->userRepository->findById($technicianId);
        
        if (!$technician) {
            error_log("Technicien ID $technicianId non trouvé pour le rappel d'entretien");
            return false;
        }

        // Convertir l'objet User en tableau si nécessaire
        $technicianData = is_object($technician) ? [
            'id' => $technician->getId(),
            'name' => $technician->getName(),
            'email' => $technician->getEmail()
        ] : $technician;

        $subject = "🔧 Rappel d'entretien - {$vehicleName}";
        $body = $this->generateMaintenanceReminderEmail(
            $technicianData,
            $vehicleName,
            $maintenanceType,
            $dueDate,
            $priority
        );

        $success = $this->sendEmail($technicianData['email'], $subject, $body);
        
        // Logger la notification
        $this->logNotification(
            $technicianId,
            'email',
            $subject,
            $body,
            $success ? 'sent' : 'failed'
        );

        return $success;
    }

    /**
     * Envoie une notification d'alerte critique
     */
    public function sendCriticalAlertNotification(
        int $technicianId,
        string $alertType,
        string $alertMessage,
        string $vehicleName = null
    ): bool {
        $technician = $this->userRepository->findById($technicianId);
        
        if (!$technician) {
            error_log("Technicien ID $technicianId non trouvé pour l'alerte critique");
            return false;
        }

        // Convertir l'objet User en tableau si nécessaire
        $technicianData = is_object($technician) ? [
            'id' => $technician->getId(),
            'name' => $technician->getName(),
            'email' => $technician->getEmail()
        ] : $technician;

        $subject = "🚨 ALERTE CRITIQUE - {$alertType}";
        $body = $this->generateCriticalAlertEmail(
            $technicianData,
            $alertType,
            $alertMessage,
            $vehicleName
        );

        $success = $this->sendEmail($technicianData['email'], $subject, $body);
        
        // Logger la notification
        $this->logNotification(
            $technicianId,
            'email',
            $subject,
            $body,
            $success ? 'sent' : 'failed'
        );

        return $success;
    }

    /**
     * Envoie un email de test
     */
    public function sendTestEmail(string $to, string $subject = "Test TerrainTrack"): bool
    {
        $body = $this->generateTestEmail();
        return $this->sendEmail($to, $subject, $body);
    }

    /**
     * Génère le contenu HTML de l'email d'assignation d'intervention
     */
    private function generateInterventionAssignmentEmail(
        array $technician,
        string $title,
        string $description,
        string $date,
        string $location
    ): string {
        $technicianName = $technician['name'] ?? 'Technicien';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Nouvelle intervention assignée</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2563eb; color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
                .info-box { background: white; padding: 25px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #2563eb; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .button { display: inline-block; background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; font-weight: bold; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
                .priority { display: inline-block; background: #f59e0b; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔧 Nouvelle Intervention Assignée</h1>
                    <span class='priority'>URGENT</span>
                </div>
                <div class='content'>
                    <p>Bonjour <strong>{$technicianName}</strong>,</p>
                    
                    <p>Une nouvelle intervention vous a été assignée :</p>
                    
                    <div class='info-box'>
                        <h3 style='margin-top: 0; color: #2563eb;'>{$title}</h3>
                        <p><strong>📝 Description :</strong> {$description}</p>
                        <p><strong>📅 Date :</strong> {$date}</p>
                        <p><strong>📍 Lieu :</strong> {$location}</p>
                    </div>
                    
                    <p>Veuillez vous connecter à TerrainTrack pour plus de détails et confirmer votre disponibilité.</p>
                    
                    <a href='http://localhost:8888/exemple/backend-mvc/public/intervention/list' class='button'>
                        Voir l'intervention
                    </a>
                </div>
                <div class='footer'>
                    <p><strong>TerrainTrack</strong> - Système de gestion des interventions</p>
                    <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Génère le contenu HTML de l'email de rappel d'entretien
     */
    private function generateMaintenanceReminderEmail(
        array $technician,
        string $vehicleName,
        string $maintenanceType,
        string $dueDate,
        string $priority
    ): string {
        $technicianName = $technician['name'] ?? 'Technicien';
        $priorityColor = $priority === 'high' ? '#dc2626' : ($priority === 'medium' ? '#f59e0b' : '#10b981');
        $priorityText = $priority === 'high' ? 'URGENT' : ($priority === 'medium' ? 'IMPORTANT' : 'NORMAL');

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Rappel d'entretien</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: {$priorityColor}; color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
                .info-box { background: white; padding: 25px; margin: 20px 0; border-radius: 8px; border-left: 4px solid {$priorityColor}; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .priority-badge { display: inline-block; background: {$priorityColor}; color: white; padding: 6px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; }
                .button { display: inline-block; background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; font-weight: bold; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔧 Rappel d'Entretien</h1>
                    <span class='priority-badge'>{$priorityText}</span>
                </div>
                <div class='content'>
                    <p>Bonjour <strong>{$technicianName}</strong>,</p>
                    
                    <p>Un entretien est prévu pour le véhicule suivant :</p>
                    
                    <div class='info-box'>
                        <h3 style='margin-top: 0; color: {$priorityColor};'>🚗 {$vehicleName}</h3>
                        <p><strong>🔧 Type d'entretien :</strong> {$maintenanceType}</p>
                        <p><strong>📅 Date d'échéance :</strong> {$dueDate}</p>
                        <p><strong>⚡ Priorité :</strong> <span style='color: {$priorityColor}; font-weight: bold;'>{$priorityText}</span></p>
                    </div>
                    
                    <p>Veuillez planifier cet entretien dans les plus brefs délais.</p>
                    
                    <a href='http://localhost:8888/exemple/backend-mvc/public/vehicles' class='button'>
                        Voir les véhicules
                    </a>
                </div>
                <div class='footer'>
                    <p><strong>TerrainTrack</strong> - Système de gestion des véhicules</p>
                    <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Génère le contenu HTML de l'email d'alerte critique
     */
    private function generateCriticalAlertEmail(
        array $technician,
        string $alertType,
        string $alertMessage,
        string $vehicleName = null
    ): string {
        $technicianName = $technician['name'] ?? 'Technicien';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Alerte critique</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc2626; color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #fef2f2; padding: 30px; border-radius: 0 0 8px 8px; }
                .alert-box { background: white; padding: 25px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #dc2626; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .button { display: inline-block; background: #dc2626; color: white; padding: 15px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; font-weight: bold; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🚨 ALERTE CRITIQUE</h1>
                </div>
                <div class='content'>
                    <p>Bonjour <strong>{$technicianName}</strong>,</p>
                    
                    <p>Une alerte critique a été détectée :</p>
                    
                    <div class='alert-box'>
                        <h3 style='margin-top: 0; color: #dc2626;'>⚠️ {$alertType}</h3>
                        <p><strong>📢 Message :</strong> {$alertMessage}</p>
                        " . ($vehicleName ? "<p><strong>🚗 Véhicule concerné :</strong> {$vehicleName}</p>" : "") . "
                    </div>
                    
                    <p><strong>Action requise immédiatement !</strong></p>
                    
                    <a href='http://localhost:8888/exemple/backend-mvc/public/dashboard' class='button'>
                        Accéder au tableau de bord
                    </a>
                </div>
                <div class='footer'>
                    <p><strong>TerrainTrack</strong> - Système d'alerte</p>
                    <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Génère un email de test
     */
    private function generateTestEmail(): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Test TerrainTrack</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #10b981; color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f0fdf4; padding: 30px; border-radius: 0 0 8px 8px; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✅ Test de Configuration Email</h1>
                </div>
                <div class='content'>
                    <p>Félicitations ! Le système d'email de TerrainTrack fonctionne correctement.</p>
                    <p><strong>Configuration :</strong></p>
                    <ul>
                        <li>Serveur SMTP : {$this->smtpHost}:{$this->smtpPort}</li>
                        <li>Email d'envoi : {$this->fromEmail}</li>
                        <li>Nom d'envoi : {$this->fromName}</li>
                    </ul>
                </div>
                <div class='footer'>
                    <p><strong>TerrainTrack</strong> - Système de notifications</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Envoie un email via la fonction mail() PHP native
     */
    private function sendEmail(string $to, string $subject, string $body): bool
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
            'X-Mailer: PHP/' . phpversion()
        ];

        // Utiliser la fonction mail() PHP native (fonctionne avec MAMP)
        error_log("EMAIL SENDING - To: {$to}, Subject: {$subject}");
        
        $result = mail($to, $subject, $body, implode("\r\n", $headers));
        
        if ($result) {
            error_log("EMAIL SENT SUCCESSFULLY to: {$to}");
        } else {
            error_log("EMAIL FAILED to send to: {$to}");
        }
        
        return $result;
    }
    
    /**
     * Charge les variables d'environnement depuis le fichier .env
     */
    private function loadEnvironmentVariables(): void
    {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    list($key, $value) = explode('=', $line, 2);
                    $_ENV[trim($key)] = trim($value);
                }
            }
        }
        
        // Mettre à jour les propriétés avec les nouvelles valeurs
        $this->smtpHost = $_ENV['SMTP_HOST'] ?? $this->smtpHost;
        $this->smtpPort = $_ENV['SMTP_PORT'] ?? $this->smtpPort;
        $this->smtpUsername = $_ENV['SMTP_USERNAME'] ?? $this->smtpUsername;
        $this->smtpPassword = $_ENV['SMTP_PASSWORD'] ?? $this->smtpPassword;
        $this->fromEmail = $_ENV['FROM_EMAIL'] ?? $this->fromEmail;
        $this->fromName = $_ENV['FROM_NAME'] ?? $this->fromName;
        $this->smtpEnabled = !empty($this->smtpUsername) && !empty($this->smtpPassword);
    }

    /**
     * Enregistre la notification dans les logs
     */
    private function logNotification(
        int $userId,
        string $type,
        string $subject,
        string $message,
        string $status
    ): void {
        try {
            $this->notificationLogsRepository->create([
                'user_id' => $userId,
                'notification_type' => $type,
                'subject' => $subject,
                'message' => $message,
                'status' => $status
            ]);
        } catch (\Exception $e) {
            error_log("Erreur lors de l'enregistrement du log de notification: " . $e->getMessage());
        }
    }

    /**
     * Teste la configuration SMTP
     */
    public function testSmtpConfiguration(): array
    {
        return [
            'smtp_host' => $this->smtpHost,
            'smtp_port' => $this->smtpPort,
            'smtp_username' => $this->smtpUsername,
            'from_email' => $this->fromEmail,
            'from_name' => $this->fromName,
            'smtp_enabled' => $this->smtpEnabled,
            'configured' => $this->smtpEnabled
        ];
    }

    /**
     * Vérifie si l'utilisateur a activé les notifications email
     */
    public function isEmailNotificationEnabled(int $userId): bool
    {
        try {
            $preferences = $this->userRepository->getNotificationPreferences($userId);
            return $preferences['email_notifications'] ?? true;
        } catch (\Exception $e) {
            error_log("Erreur lors de la vérification des préférences email: " . $e->getMessage());
            return true; // Par défaut, activé
        }
    }
}
