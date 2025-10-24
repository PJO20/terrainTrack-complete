<?php

namespace App\Repository;

use PDO;

class NotificationSettingsRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Récupère les paramètres de notifications d'un utilisateur
     */
    public function findByUserId(int $userId): ?array
    {
        $query = "SELECT * FROM notification_settings WHERE user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Met à jour les paramètres de notifications d'un utilisateur
     */
    public function updateNotifications(int $userId, array $data): bool
    {
        try {
            $query = "UPDATE notification_settings SET 
                        email_notifications = :email_notifications,
                        push_notifications = :push_notifications,
                        sms_notifications = :sms_notifications,
                        desktop_notifications = :desktop_notifications,
                        sound_notifications = :sound_notifications,
                        vehicle_alerts = :vehicle_alerts,
                        maintenance_reminders = :maintenance_reminders,
                        intervention_updates = :intervention_updates,
                        team_notifications = :team_notifications,
                        system_alerts = :system_alerts,
                        report_generation = :report_generation,
                        notification_frequency = :notification_frequency,
                        quiet_hours_enabled = :quiet_hours_enabled,
                        quiet_hours_start = :quiet_hours_start,
                        quiet_hours_end = :quiet_hours_end,
                        updated_at = CURRENT_TIMESTAMP
                      WHERE user_id = :user_id";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                'user_id' => $userId,
                'email_notifications' => $data['email_notifications'] ? 1 : 0,
                'push_notifications' => $data['push_notifications'] ? 1 : 0,
                'sms_notifications' => isset($data['sms_notifications']) && $data['sms_notifications'] ? 1 : 0,
                'desktop_notifications' => $data['desktop_notifications'] ? 1 : 0,
                'sound_notifications' => $data['sound_notifications'] ? 1 : 0,
                'vehicle_alerts' => $data['vehicle_alerts'] ? 1 : 0,
                'maintenance_reminders' => $data['maintenance_reminders'] ? 1 : 0,
                'intervention_updates' => $data['intervention_updates'] ? 1 : 0,
                'team_notifications' => $data['team_notifications'] ? 1 : 0,
                'system_alerts' => $data['system_alerts'] ? 1 : 0,
                'report_generation' => $data['report_generation'] ? 1 : 0,
                'notification_frequency' => $data['notification_frequency'] ?? 'realtime',
                'quiet_hours_enabled' => $data['quiet_hours_enabled'] ? 1 : 0,
                'quiet_hours_start' => $data['quiet_hours_start'] ?? '22:00:00',
                'quiet_hours_end' => $data['quiet_hours_end'] ?? '07:00:00'
            ]);
            
            if ($result && $stmt->rowCount() > 0) {
                error_log("Paramètres notifications utilisateur ID $userId mis à jour avec succès");
                return true;
            }
            
            // Si aucune ligne n'a été affectée, créer un nouvel enregistrement
            if ($stmt->rowCount() === 0) {
                return $this->createNotificationSettings($userId, $data);
            }
            
            return false;
        } catch (\PDOException $e) {
            error_log("Erreur dans NotificationSettingsRepository::updateNotifications : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crée de nouveaux paramètres de notifications
     */
    private function createNotificationSettings(int $userId, array $data): bool
    {
        try {
            $query = "INSERT INTO notification_settings (
                        user_id, email_notifications, push_notifications, sms_notifications,
                        desktop_notifications, sound_notifications,
                        vehicle_alerts, maintenance_reminders, intervention_updates,
                        team_notifications, system_alerts, report_generation,
                        notification_frequency, quiet_hours_enabled, quiet_hours_start, quiet_hours_end
                      ) VALUES (
                        :user_id, :email_notifications, :push_notifications, :sms_notifications,
                        :desktop_notifications, :sound_notifications,
                        :vehicle_alerts, :maintenance_reminders, :intervention_updates,
                        :team_notifications, :system_alerts, :report_generation,
                        :notification_frequency, :quiet_hours_enabled, :quiet_hours_start, :quiet_hours_end
                      )";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                'user_id' => $userId,
                'email_notifications' => $data['email_notifications'] ? 1 : 0,
                'push_notifications' => $data['push_notifications'] ? 1 : 0,
                'sms_notifications' => isset($data['sms_notifications']) && $data['sms_notifications'] ? 1 : 0,
                'desktop_notifications' => $data['desktop_notifications'] ? 1 : 0,
                'sound_notifications' => $data['sound_notifications'] ? 1 : 0,
                'vehicle_alerts' => $data['vehicle_alerts'] ? 1 : 0,
                'maintenance_reminders' => $data['maintenance_reminders'] ? 1 : 0,
                'intervention_updates' => $data['intervention_updates'] ? 1 : 0,
                'team_notifications' => $data['team_notifications'] ? 1 : 0,
                'system_alerts' => $data['system_alerts'] ? 1 : 0,
                'report_generation' => $data['report_generation'] ? 1 : 0,
                'notification_frequency' => $data['notification_frequency'] ?? 'realtime',
                'quiet_hours_enabled' => $data['quiet_hours_enabled'] ? 1 : 0,
                'quiet_hours_start' => $data['quiet_hours_start'] ?? '22:00:00',
                'quiet_hours_end' => $data['quiet_hours_end'] ?? '07:00:00'
            ]);
            
            if ($result) {
                error_log("Nouveaux paramètres notifications utilisateur ID $userId créés avec succès");
                return true;
            }
            
            return false;
        } catch (\PDOException $e) {
            error_log("Erreur dans NotificationSettingsRepository::createNotificationSettings : " . $e->getMessage());
            return false;
        }
    }
} 