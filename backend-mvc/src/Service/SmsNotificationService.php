<?php

namespace App\Service;

use App\Repository\UserRepository;
use App\Repository\NotificationLogsRepository;

class SmsNotificationService
{
    private UserRepository $userRepository;
    private NotificationLogsRepository $notificationLogsRepository;
    private string $smsApiUrl;
    private string $smsApiKey;
    private string $smsSender;
    private bool $smsEnabled;

    public function __construct(
        UserRepository $userRepository,
        NotificationLogsRepository $notificationLogsRepository
    ) {
        $this->userRepository = $userRepository;
        $this->notificationLogsRepository = $notificationLogsRepository;
        
        // Charger la configuration depuis les variables d'environnement
        $this->smsApiUrl = $_ENV['SMS_API_URL'] ?? '';
        $this->smsApiKey = $_ENV['SMS_API_KEY'] ?? '';
        $this->smsSender = $_ENV['SMS_SENDER'] ?? 'TerrainTrack';
        $this->smsEnabled = !empty($this->smsApiUrl) && !empty($this->smsApiKey);
    }

    /**
     * Envoie une notification SMS d'assignation d'intervention
     */
    public function sendInterventionAssignmentSms(
        int $technicianId,
        string $interventionTitle,
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
            'phone' => $technician->getPhone() ?? ''
        ] : $technician;

        if (empty($technicianData['phone'])) {
            error_log("Technicien ID $technicianId n'a pas de numéro de téléphone");
            return false;
        }

        $message = $this->generateInterventionAssignmentSms(
            $interventionTitle,
            $interventionDate,
            $interventionLocation
        );

        $success = $this->sendSms($technicianData['phone'], $message);
        
        // Logger la notification
        $this->logNotification(
            $technicianId,
            'sms',
            'Nouvelle intervention assignée',
            $message,
            $success ? 'sent' : 'failed'
        );

        return $success;
    }

    /**
     * Envoie un rappel SMS d'entretien
     */
    public function sendMaintenanceReminderSms(
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
            'phone' => $technician->getPhone() ?? ''
        ] : $technician;

        if (empty($technicianData['phone'])) {
            error_log("Technicien ID $technicianId n'a pas de numéro de téléphone");
            return false;
        }

        $message = $this->generateMaintenanceReminderSms(
            $vehicleName,
            $maintenanceType,
            $dueDate,
            $priority
        );

        $success = $this->sendSms($technicianData['phone'], $message);
        
        // Logger la notification
        $this->logNotification(
            $technicianId,
            'sms',
            'Rappel d\'entretien',
            $message,
            $success ? 'sent' : 'failed'
        );

        return $success;
    }

    /**
     * Envoie une alerte SMS critique
     */
    public function sendCriticalAlertSms(
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
            'phone' => $technician->getPhone() ?? ''
        ] : $technician;

        if (empty($technicianData['phone'])) {
            error_log("Technicien ID $technicianId n'a pas de numéro de téléphone");
            return false;
        }

        $message = $this->generateCriticalAlertSms(
            $alertType,
            $alertMessage,
            $vehicleName
        );

        $success = $this->sendSms($technicianData['phone'], $message);
        
        // Logger la notification
        $this->logNotification(
            $technicianId,
            'sms',
            'Alerte critique',
            $message,
            $success ? 'sent' : 'failed'
        );

        return $success;
    }

    /**
     * Envoie un SMS de test
     */
    public function sendTestSms(string $phone, string $message = "Test TerrainTrack"): bool
    {
        $testMessage = "✅ Test TerrainTrack - Configuration SMS OK";
        return $this->sendSms($phone, $testMessage);
    }

    /**
     * Génère le message SMS d'assignation d'intervention
     */
    private function generateInterventionAssignmentSms(
        string $title,
        string $date,
        string $location
    ): string {
        return "🔧 NOUVELLE INTERVENTION\n" .
               "📋 {$title}\n" .
               "📅 {$date}\n" .
               "📍 {$location}\n" .
               "🌐 TerrainTrack";
    }

    /**
     * Génère le message SMS de rappel d'entretien
     */
    private function generateMaintenanceReminderSms(
        string $vehicleName,
        string $maintenanceType,
        string $dueDate,
        string $priority
    ): string {
        $priorityEmoji = $priority === 'high' ? '🚨' : ($priority === 'medium' ? '⚠️' : 'ℹ️');
        
        return "{$priorityEmoji} RAPPEL ENTRETIEN\n" .
               "🚗 {$vehicleName}\n" .
               "🔧 {$maintenanceType}\n" .
               "📅 Échéance: {$dueDate}\n" .
               "🌐 TerrainTrack";
    }

    /**
     * Génère le message SMS d'alerte critique
     */
    private function generateCriticalAlertSms(
        string $alertType,
        string $alertMessage,
        string $vehicleName = null
    ): string {
        $message = "🚨 ALERTE CRITIQUE\n" .
                  "⚠️ {$alertType}\n" .
                  "📢 {$alertMessage}";
        
        if ($vehicleName) {
            $message .= "\n🚗 {$vehicleName}";
        }
        
        $message .= "\n🌐 TerrainTrack";
        
        return $message;
    }

    /**
     * Envoie un SMS via l'API
     */
    private function sendSms(string $phone, string $message): bool
    {
        if (!$this->smsEnabled) {
            error_log("SMS non configuré - simulation d'envoi vers {$phone}");
            return true; // Simulation
        }

        try {
            // Formatage du numéro de téléphone
            $phone = $this->formatPhoneNumber($phone);
            
            // Préparation des données pour l'API
            $data = [
                'to' => $phone,
                'message' => $message,
                'sender' => $this->smsSender
            ];

            // Envoi via cURL (exemple avec une API générique)
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->smsApiUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->smsApiKey
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                error_log("SMS envoyé avec succès vers {$phone}");
                return true;
            } else {
                error_log("Erreur envoi SMS vers {$phone}: HTTP {$httpCode} - {$response}");
                return false;
            }

        } catch (\Exception $e) {
            error_log("Exception lors de l'envoi SMS vers {$phone}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Formate le numéro de téléphone
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Supprimer tous les caractères non numériques
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Ajouter l'indicatif pays si nécessaire
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            $phone = '33' . substr($phone, 1);
        }
        
        return '+' . $phone;
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
            error_log("Erreur lors de l'enregistrement du log de notification SMS: " . $e->getMessage());
        }
    }

    /**
     * Teste la configuration SMS
     */
    public function testSmsConfiguration(): array
    {
        return [
            'sms_api_url' => $this->smsApiUrl,
            'sms_sender' => $this->smsSender,
            'sms_enabled' => $this->smsEnabled,
            'configured' => $this->smsEnabled
        ];
    }

    /**
     * Vérifie si l'utilisateur a activé les notifications SMS
     */
    public function isSmsNotificationEnabled(int $userId): bool
    {
        try {
            $preferences = $this->userRepository->getNotificationPreferences($userId);
            return $preferences['sms_notifications'] ?? false;
        } catch (\Exception $e) {
            error_log("Erreur lors de la vérification des préférences SMS: " . $e->getMessage());
            return false; // Par défaut, désactivé
        }
    }

    /**
     * Valide le format d'un numéro de téléphone
     */
    public function validatePhoneNumber(string $phone): bool
    {
        // Supprimer tous les caractères non numériques
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        
        // Vérifier la longueur (entre 10 et 15 chiffres)
        return strlen($cleanPhone) >= 10 && strlen($cleanPhone) <= 15;
    }

    /**
     * Récupère les statistiques d'envoi SMS
     */
    public function getSmsStats(int $userId = null): array
    {
        try {
            return $this->notificationLogsRepository->getStatsByType('sms', $userId);
        } catch (\Exception $e) {
            error_log("Erreur lors de la récupération des stats SMS: " . $e->getMessage());
            return [];
        }
    }
}
