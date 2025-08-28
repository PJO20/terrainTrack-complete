<?php

namespace App\Repository;

use App\Entity\Notification;
use PDO;

class NotificationRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Sauvegarde une notification en base
     */
    public function save(Notification $notification): bool
    {
        try {
            $sql = "INSERT INTO notifications (
                        title, description, type, type_class, icon, 
                        related_to, related_id, related_type, recipient_id, 
                        is_read, priority, expires_at, metadata, created_at, updated_at
                    ) VALUES (
                        :title, :description, :type, :type_class, :icon,
                        :related_to, :related_id, :related_type, :recipient_id,
                        :is_read, :priority, :expires_at, :metadata, :created_at, :updated_at
                    )";

            $stmt = $this->pdo->prepare($sql);
            
            $result = $stmt->execute([
                'title' => $notification->getTitle(),
                'description' => $notification->getDescription(),
                'type' => $notification->getType(),
                'type_class' => $notification->getTypeClass(),
                'icon' => $notification->getIcon(),
                'related_to' => $notification->getRelatedTo(),
                'related_id' => $notification->getRelatedId(),
                'related_type' => $notification->getRelatedType(),
                'recipient_id' => $notification->getRecipientId(),
                'is_read' => $notification->isRead() ? 1 : 0,
                'priority' => $notification->getPriority(),
                'expires_at' => $notification->getExpiresAt()?->format('Y-m-d H:i:s'),
                'metadata' => $notification->getMetadata() ? json_encode($notification->getMetadata()) : null,
                'created_at' => $notification->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updated_at' => $notification->getUpdatedAt()?->format('Y-m-d H:i:s')
            ]);

            if ($result) {
                $notification->setId($this->pdo->lastInsertId());
            }

            return $result;
        } catch (\Exception $e) {
            error_log("Erreur lors de la sauvegarde de la notification : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Trouve toutes les notifications (avec filtres optionnels)
     */
    public function findAll(
        ?int $recipientId = null, 
        ?bool $onlyUnread = null, 
        ?string $type = null, 
        ?string $priority = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        try {
            $conditions = [];
            $params = [];

            // Filtres
            if ($recipientId !== null) {
                $conditions[] = "(recipient_id = :recipient_id OR recipient_id IS NULL)";
                $params['recipient_id'] = $recipientId;
            }

            if ($onlyUnread !== null) {
                $conditions[] = "is_read = :is_read";
                $params['is_read'] = $onlyUnread ? 0 : 1;
            }

            if ($type !== null) {
                $conditions[] = "type = :type";
                $params['type'] = $type;
            }

            if ($priority !== null) {
                $conditions[] = "priority = :priority";
                $params['priority'] = $priority;
            }

            // Exclure les notifications expirées
            $conditions[] = "(expires_at IS NULL OR expires_at > NOW())";

            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            $sql = "SELECT * FROM notifications 
                    $whereClause 
                    ORDER BY created_at DESC 
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->pdo->prepare($sql);
            
            // Bind des paramètres
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map([$this, 'hydrate'], $results);
        } catch (\Exception $e) {
            error_log("Erreur lors de la récupération des notifications : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Trouve les notifications récentes (7 derniers jours)
     */
    public function findRecent(?int $recipientId = null, int $limit = 10): array
    {
        try {
            $conditions = ["created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"];
            $params = [];

            if ($recipientId !== null) {
                $conditions[] = "(recipient_id = :recipient_id OR recipient_id IS NULL)";
                $params['recipient_id'] = $recipientId;
            }

            // Exclure les notifications expirées
            $conditions[] = "(expires_at IS NULL OR expires_at > NOW())";

            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "SELECT * FROM notifications 
                    $whereClause 
                    ORDER BY 
                        CASE WHEN is_read = 0 THEN 0 ELSE 1 END,
                        priority = 'critical' DESC,
                        priority = 'high' DESC,
                        priority = 'medium' DESC,
                        created_at DESC 
                    LIMIT :limit";

            $stmt = $this->pdo->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Récupérer les notifications de base et les formater
            return array_map([$this, 'hydrate'], $results);
        } catch (\Exception $e) {
            error_log("Erreur lors de la récupération des notifications récentes : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Trouve une notification par ID
     */
    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM notifications WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? $this->hydrate($result) : null;
        } catch (\Exception $e) {
            error_log("Erreur lors de la récupération de la notification : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Marque une notification comme lue
     */
    public function markAsRead(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = 1, updated_at = NOW() WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (\Exception $e) {
            error_log("Erreur lors du marquage comme lu : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marque une notification comme non lue
     */
    public function markAsUnread(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = 0, updated_at = NOW() WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (\Exception $e) {
            error_log("Erreur lors du marquage comme non lu : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marque plusieurs notifications comme lues
     */
    public function markMultipleAsRead(array $ids): bool
    {
        try {
            if (empty($ids)) {
                return true;
            }

            $placeholders = [];
            $params = [];
            
            foreach ($ids as $index => $id) {
                $placeholder = ":id_$index";
                $placeholders[] = $placeholder;
                $params[$placeholder] = $id;
            }

            $sql = "UPDATE notifications SET is_read = 1, updated_at = NOW() WHERE id IN (" . implode(',', $placeholders) . ")";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (\Exception $e) {
            error_log("Erreur lors du marquage multiple comme lu : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marque plusieurs notifications comme non lues
     */
    public function markMultipleAsUnread(array $ids): bool
    {
        try {
            if (empty($ids)) {
                return true;
            }

            $placeholders = [];
            $params = [];
            
            foreach ($ids as $index => $id) {
                $placeholder = ":id_$index";
                $placeholders[] = $placeholder;
                $params[$placeholder] = $id;
            }

            $sql = "UPDATE notifications SET is_read = 0, updated_at = NOW() WHERE id IN (" . implode(',', $placeholders) . ")";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (\Exception $e) {
            error_log("Erreur lors du marquage multiple comme non lu : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime plusieurs notifications
     */
    public function deleteMultiple(array $ids): bool
    {
        try {
            if (empty($ids)) {
                return true;
            }

            $placeholders = [];
            $params = [];
            
            foreach ($ids as $index => $id) {
                $placeholder = ":id_$index";
                $placeholders[] = $placeholder;
                $params[$placeholder] = $id;
            }

            $sql = "DELETE FROM notifications WHERE id IN (" . implode(',', $placeholders) . ")";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (\Exception $e) {
            error_log("Erreur lors de la suppression multiple: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime une notification
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "DELETE FROM notifications WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute(['id' => $id]);
        } catch (\Exception $e) {
            error_log("Erreur lors de la suppression: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Compte les notifications non lues
     */
    public function countUnread(?int $recipientId = null): int
    {
        try {
            $conditions = ["is_read = 0"];
            $params = [];

            if ($recipientId !== null) {
                $conditions[] = "(recipient_id = :recipient_id OR recipient_id IS NULL)";
                $params['recipient_id'] = $recipientId;
            }

            // Exclure les notifications expirées
            $conditions[] = "(expires_at IS NULL OR expires_at > NOW())";

            $whereClause = "WHERE " . implode(" AND ", $conditions);
            $sql = "SELECT COUNT(*) FROM notifications $whereClause";

            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $count = $stmt->fetchColumn();

            return (int)$count;
        } catch (\Exception $e) {
            error_log("Erreur lors du comptage des notifications non lues : " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Nettoie les notifications expirées
     */
    public function cleanupExpired(): int
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM notifications WHERE expires_at IS NOT NULL AND expires_at < NOW()");
            $stmt->execute();
            return $stmt->rowCount();
        } catch (\Exception $e) {
            error_log("Erreur lors du nettoyage des notifications expirées : " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Hydrate un tableau en format compatible avec l'interface
     */
    private function hydrate(array $data): array
    {
        return [
            'id' => (int) $data['id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'type' => $data['type'],
            'type_class' => $data['type_class'],
            'icon' => $data['icon'],
            'related_to' => $data['related_to'],
            'related_id' => $data['related_id'] ? (int) $data['related_id'] : null,
            'related_type' => $data['related_type'],
            'recipient_id' => $data['recipient_id'] ? (int) $data['recipient_id'] : null,
            'read' => (bool) $data['is_read'],
            'priority' => $data['priority'],
            'expires_at' => $data['expires_at'],
            'metadata' => $data['metadata'] ? json_decode($data['metadata'], true) : null,
            'created_at' => $data['created_at'],
            'updated_at' => $data['updated_at'],
            'date' => date('d/m/Y', strtotime($data['created_at'])), // Format compatible
        ];
    }
} 