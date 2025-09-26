<?php

namespace App\Repository;

class NotificationPreferencesRepository
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère les préférences de notification d'un utilisateur
     */
    public function findByUserId(int $userId): ?array
    {
        $sql = "SELECT * FROM notification_preferences WHERE user_id = :user_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Crée ou met à jour les préférences de notification
     */
    public function save(array $preferences): bool
    {
        $sql = "INSERT INTO notification_preferences 
                (user_id, email_notifications, sms_notifications, intervention_assignments, 
                 maintenance_reminders, critical_alerts, reminder_frequency_days) 
                VALUES (:user_id, :email_notifications, :sms_notifications, :intervention_assignments, 
                        :maintenance_reminders, :critical_alerts, :reminder_frequency_days)
                ON DUPLICATE KEY UPDATE 
                email_notifications = VALUES(email_notifications),
                sms_notifications = VALUES(sms_notifications),
                intervention_assignments = VALUES(intervention_assignments),
                maintenance_reminders = VALUES(maintenance_reminders),
                critical_alerts = VALUES(critical_alerts),
                reminder_frequency_days = VALUES(reminder_frequency_days),
                updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([
            'user_id' => $preferences['user_id'],
            'email_notifications' => $preferences['email_notifications'] ? 1 : 0,
            'sms_notifications' => $preferences['sms_notifications'] ? 1 : 0,
            'intervention_assignments' => $preferences['intervention_assignments'] ? 1 : 0,
            'maintenance_reminders' => $preferences['maintenance_reminders'] ? 1 : 0,
            'critical_alerts' => $preferences['critical_alerts'] ? 1 : 0,
            'reminder_frequency_days' => $preferences['reminder_frequency_days'] ?? 7
        ]);
    }

    /**
     * Met à jour une préférence spécifique
     */
    public function updatePreference(int $userId, string $preference, bool $value): bool
    {
        $allowedPreferences = [
            'email_notifications',
            'sms_notifications',
            'intervention_assignments',
            'maintenance_reminders',
            'critical_alerts'
        ];

        if (!in_array($preference, $allowedPreferences)) {
            throw new \InvalidArgumentException("Préférence invalide: {$preference}");
        }

        $sql = "INSERT INTO notification_preferences (user_id, {$preference}) 
                VALUES (:user_id, :value)
                ON DUPLICATE KEY UPDATE 
                {$preference} = :value,
                updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([
            'user_id' => $userId,
            'value' => $value ? 1 : 0
        ]);
    }

    /**
     * Met à jour la fréquence des rappels
     */
    public function updateReminderFrequency(int $userId, int $days): bool
    {
        $sql = "INSERT INTO notification_preferences (user_id, reminder_frequency_days) 
                VALUES (:user_id, :days)
                ON DUPLICATE KEY UPDATE 
                reminder_frequency_days = :days,
                updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([
            'user_id' => $userId,
            'days' => $days
        ]);
    }

    /**
     * Récupère tous les utilisateurs avec notifications email activées
     */
    public function getUsersWithEmailNotifications(): array
    {
        $sql = "SELECT u.id, u.name, u.email, u.notification_email, np.*
                FROM users u
                INNER JOIN notification_preferences np ON u.id = np.user_id
                WHERE np.email_notifications = 1
                AND (u.notification_email IS NOT NULL AND u.notification_email != '')";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Récupère tous les utilisateurs avec notifications SMS activées
     */
    public function getUsersWithSmsNotifications(): array
    {
        $sql = "SELECT u.id, u.name, u.phone, np.*
                FROM users u
                INNER JOIN notification_preferences np ON u.id = np.user_id
                WHERE np.sms_notifications = 1
                AND (u.phone IS NOT NULL AND u.phone != '')";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les utilisateurs pour un type de notification spécifique
     */
    public function getUsersForNotificationType(string $type): array
    {
        $allowedTypes = [
            'intervention_assignments',
            'maintenance_reminders',
            'critical_alerts'
        ];

        if (!in_array($type, $allowedTypes)) {
            throw new \InvalidArgumentException("Type de notification invalide: {$type}");
        }

        $sql = "SELECT u.id, u.name, u.email, u.phone, u.notification_email, np.*
                FROM users u
                INNER JOIN notification_preferences np ON u.id = np.user_id
                WHERE np.{$type} = 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les statistiques des préférences
     */
    public function getPreferencesStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_users,
                    SUM(email_notifications) as email_enabled,
                    SUM(sms_notifications) as sms_enabled,
                    SUM(intervention_assignments) as intervention_enabled,
                    SUM(maintenance_reminders) as maintenance_enabled,
                    SUM(critical_alerts) as alerts_enabled
                FROM notification_preferences";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        $stats = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // Calculer les pourcentages
        $total = (int)$stats['total_users'];
        if ($total > 0) {
            $stats['email_percentage'] = round(($stats['email_enabled'] / $total) * 100, 2);
            $stats['sms_percentage'] = round(($stats['sms_enabled'] / $total) * 100, 2);
            $stats['intervention_percentage'] = round(($stats['intervention_enabled'] / $total) * 100, 2);
            $stats['maintenance_percentage'] = round(($stats['maintenance_enabled'] / $total) * 100, 2);
            $stats['alerts_percentage'] = round(($stats['alerts_enabled'] / $total) * 100, 2);
        } else {
            $stats['email_percentage'] = 0;
            $stats['sms_percentage'] = 0;
            $stats['intervention_percentage'] = 0;
            $stats['maintenance_percentage'] = 0;
            $stats['alerts_percentage'] = 0;
        }
        
        return $stats;
    }

    /**
     * Crée des préférences par défaut pour un utilisateur
     */
    public function createDefaultPreferences(int $userId): bool
    {
        $defaultPreferences = [
            'user_id' => $userId,
            'email_notifications' => true,
            'sms_notifications' => false,
            'intervention_assignments' => true,
            'maintenance_reminders' => true,
            'critical_alerts' => true,
            'reminder_frequency_days' => 7
        ];

        return $this->save($defaultPreferences);
    }

    /**
     * Supprime les préférences d'un utilisateur
     */
    public function deleteByUserId(int $userId): bool
    {
        $sql = "DELETE FROM notification_preferences WHERE user_id = :user_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Récupère les préférences de tous les utilisateurs
     */
    public function findAll(): array
    {
        $sql = "SELECT np.*, u.name, u.email, u.phone
                FROM notification_preferences np
                LEFT JOIN users u ON np.user_id = u.id
                ORDER BY np.updated_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Vérifie si un utilisateur a activé un type de notification
     */
    public function isNotificationEnabled(int $userId, string $type): bool
    {
        $preferences = $this->findByUserId($userId);
        
        if (!$preferences) {
            return false;
        }

        return $preferences[$type] ?? false;
    }

    /**
     * Récupère la fréquence des rappels pour un utilisateur
     */
    public function getReminderFrequency(int $userId): int
    {
        $preferences = $this->findByUserId($userId);
        
        if (!$preferences) {
            return 7; // Valeur par défaut
        }

        return (int)$preferences['reminder_frequency_days'];
    }
}



