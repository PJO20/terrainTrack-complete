<?php

namespace App\Repository;

class NotificationLogsRepository
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Crée un nouveau log de notification
     */
    public function create(array $data): bool
    {
        $sql = "INSERT INTO notification_logs (user_id, notification_type, subject, message, status) 
                VALUES (:user_id, :notification_type, :subject, :message, :status)";
        
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([
            'user_id' => $data['user_id'],
            'notification_type' => $data['notification_type'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'status' => $data['status']
        ]);
    }

    /**
     * Récupère les logs de notification pour un utilisateur
     */
    public function findByUserId(int $userId, int $limit = 50): array
    {
        $sql = "SELECT * FROM notification_logs 
                WHERE user_id = :user_id 
                ORDER BY sent_at DESC 
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les logs par type de notification
     */
    public function findByType(string $type, int $limit = 50): array
    {
        $sql = "SELECT * FROM notification_logs 
                WHERE notification_type = :type 
                ORDER BY sent_at DESC 
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':type', $type, \PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les logs par statut
     */
    public function findByStatus(string $status, int $limit = 50): array
    {
        $sql = "SELECT * FROM notification_logs 
                WHERE status = :status 
                ORDER BY sent_at DESC 
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':status', $status, \PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les statistiques par type de notification
     */
    public function getStatsByType(string $type, int $userId = null): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN status = 'bounced' THEN 1 ELSE 0 END) as bounced
                FROM notification_logs 
                WHERE notification_type = :type";
        
        $params = ['type' => $type];
        
        if ($userId) {
            $sql .= " AND user_id = :user_id";
            $params['user_id'] = $userId;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        $stats = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // Calculer les pourcentages
        $total = (int)$stats['total'];
        if ($total > 0) {
            $stats['success_rate'] = round(($stats['sent'] / $total) * 100, 2);
            $stats['failure_rate'] = round((($stats['failed'] + $stats['bounced']) / $total) * 100, 2);
        } else {
            $stats['success_rate'] = 0;
            $stats['failure_rate'] = 0;
        }
        
        return $stats;
    }

    /**
     * Récupère les statistiques globales
     */
    public function getGlobalStats(int $userId = null): array
    {
        $sql = "SELECT 
                    notification_type,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN status = 'bounced' THEN 1 ELSE 0 END) as bounced
                FROM notification_logs";
        
        $params = [];
        
        if ($userId) {
            $sql .= " WHERE user_id = :user_id";
            $params['user_id'] = $userId;
        }
        
        $sql .= " GROUP BY notification_type ORDER BY total DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les logs récents (dernières 24h)
     */
    public function getRecentLogs(int $hours = 24): array
    {
        $sql = "SELECT nl.*, u.name as user_name, u.email as user_email
                FROM notification_logs nl
                LEFT JOIN users u ON nl.user_id = u.id
                WHERE nl.sent_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
                ORDER BY nl.sent_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':hours', $hours, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Supprime les anciens logs (plus de 30 jours)
     */
    public function cleanupOldLogs(int $days = 30): int
    {
        $sql = "DELETE FROM notification_logs 
                WHERE sent_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':days', $days, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->rowCount();
    }

    /**
     * Récupère les logs d'erreur
     */
    public function getErrorLogs(int $limit = 50): array
    {
        $sql = "SELECT nl.*, u.name as user_name, u.email as user_email
                FROM notification_logs nl
                LEFT JOIN users u ON nl.user_id = u.id
                WHERE nl.status IN ('failed', 'bounced')
                ORDER BY nl.sent_at DESC
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Met à jour le statut d'un log
     */
    public function updateStatus(int $logId, string $status, string $errorMessage = null): bool
    {
        $sql = "UPDATE notification_logs 
                SET status = :status, error_message = :error_message 
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([
            'id' => $logId,
            'status' => $status,
            'error_message' => $errorMessage
        ]);
    }

    /**
     * Récupère un log par ID
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT nl.*, u.name as user_name, u.email as user_email
                FROM notification_logs nl
                LEFT JOIN users u ON nl.user_id = u.id
                WHERE nl.id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Supprime un log
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM notification_logs WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Récupère tous les logs
     */
    public function findAll(): array
    {
        $sql = "SELECT nl.*, u.name as user_name, u.email as user_email
                FROM notification_logs nl
                LEFT JOIN users u ON nl.user_id = u.id
                ORDER BY nl.sent_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Supprime les anciens logs (alias pour cleanupOldLogs)
     */
    public function deleteOldLogs(int $days = 30): int
    {
        return $this->cleanupOldLogs($days);
    }
}
