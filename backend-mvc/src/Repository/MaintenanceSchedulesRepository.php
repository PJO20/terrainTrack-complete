<?php

namespace App\Repository;

class MaintenanceSchedulesRepository
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère tous les plannings de maintenance
     */
    public function findAll(): array
    {
        $sql = "SELECT ms.*, v.name as vehicle_name, u.name as technician_name
                FROM maintenance_schedules ms
                LEFT JOIN vehicles v ON ms.vehicle_id = v.id
                LEFT JOIN users u ON ms.assigned_technician_id = u.id
                ORDER BY ms.due_date ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un planning par ID
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT ms.*, v.name as vehicle_name, u.name as technician_name
                FROM maintenance_schedules ms
                LEFT JOIN vehicles v ON ms.vehicle_id = v.id
                LEFT JOIN users u ON ms.assigned_technician_id = u.id
                WHERE ms.id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Récupère les entretiens à échéance
     */
    public function getDueMaintenances(): array
    {
        $sql = "SELECT ms.*, v.name as vehicle_name, u.name as technician_name
                FROM maintenance_schedules ms
                LEFT JOIN vehicles v ON ms.vehicle_id = v.id
                LEFT JOIN users u ON ms.assigned_technician_id = u.id
                WHERE ms.status = 'scheduled'
                AND ms.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                AND ms.due_date >= CURDATE()
                ORDER BY ms.due_date ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les entretiens à venir (alias pour getDueMaintenances)
     */
    public function findUpcomingMaintenance(int $days = 7): array
    {
        $sql = "SELECT ms.*, v.name as vehicle_name, u.name as technician_name
                FROM maintenance_schedules ms
                LEFT JOIN vehicles v ON ms.vehicle_id = v.id
                LEFT JOIN users u ON ms.assigned_technician_id = u.id
                WHERE ms.status = 'scheduled'
                AND ms.due_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
                AND ms.due_date >= CURDATE()
                ORDER BY ms.due_date ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':days', $days, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les entretiens en retard
     */
    public function getOverdueMaintenances(): array
    {
        $sql = "SELECT ms.*, v.name as vehicle_name, u.name as technician_name
                FROM maintenance_schedules ms
                LEFT JOIN vehicles v ON ms.vehicle_id = v.id
                LEFT JOIN users u ON ms.assigned_technician_id = u.id
                WHERE ms.status = 'scheduled'
                AND ms.due_date < CURDATE()
                ORDER BY ms.due_date ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les entretiens en retard (alias pour getOverdueMaintenances)
     */
    public function findOverdueMaintenance(): array
    {
        return $this->getOverdueMaintenances();
    }

    /**
     * Récupère les entretiens par technicien
     */
    public function findByTechnician(int $technicianId): array
    {
        $sql = "SELECT ms.*, v.name as vehicle_name
                FROM maintenance_schedules ms
                LEFT JOIN vehicles v ON ms.vehicle_id = v.id
                WHERE ms.assigned_technician_id = :technician_id
                ORDER BY ms.due_date ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':technician_id', $technicianId, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les entretiens par véhicule
     */
    public function findByVehicle(int $vehicleId): array
    {
        $sql = "SELECT ms.*, u.name as technician_name
                FROM maintenance_schedules ms
                LEFT JOIN users u ON ms.assigned_technician_id = u.id
                WHERE ms.vehicle_id = :vehicle_id
                ORDER BY ms.due_date ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':vehicle_id', $vehicleId, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Crée un nouveau planning de maintenance
     */
    public function create(array $data): bool
    {
        $sql = "INSERT INTO maintenance_schedules 
                (vehicle_id, maintenance_type, scheduled_date, due_date, priority, 
                 description, assigned_technician_id, status) 
                VALUES (:vehicle_id, :maintenance_type, :scheduled_date, :due_date, :priority, 
                        :description, :assigned_technician_id, :status)";
        
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([
            'vehicle_id' => $data['vehicle_id'],
            'maintenance_type' => $data['maintenance_type'],
            'scheduled_date' => $data['scheduled_date'],
            'due_date' => $data['due_date'],
            'priority' => $data['priority'] ?? 'medium',
            'description' => $data['description'] ?? null,
            'assigned_technician_id' => $data['assigned_technician_id'] ?? null,
            'status' => $data['status'] ?? 'scheduled'
        ]);
    }

    /**
     * Met à jour un planning de maintenance
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = ['id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, ['vehicle_id', 'maintenance_type', 'scheduled_date', 'due_date', 
                               'priority', 'description', 'assigned_technician_id', 'status'])) {
                $fields[] = "{$key} = :{$key}";
                $values[$key] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE maintenance_schedules SET " . implode(', ', $fields) . " WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute($values);
    }

    /**
     * Supprime un planning de maintenance
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM maintenance_schedules WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Marque un entretien comme notifié
     */
    public function markAsNotified(int $id): bool
    {
        $sql = "UPDATE maintenance_schedules 
                SET updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Change le statut d'un entretien
     */
    public function updateStatus(int $id, string $status): bool
    {
        $allowedStatuses = ['scheduled', 'in_progress', 'completed', 'cancelled'];
        
        if (!in_array($status, $allowedStatuses)) {
            throw new \InvalidArgumentException("Statut invalide: {$status}");
        }

        $sql = "UPDATE maintenance_schedules 
                SET status = :status, updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([
            'id' => $id,
            'status' => $status
        ]);
    }

    /**
     * Récupère les statistiques des rappels
     */
    public function getReminderStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'scheduled' AND due_date <= CURDATE() THEN 1 ELSE 0 END) as overdue,
                    SUM(CASE WHEN status = 'scheduled' AND due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as due_soon,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress
                FROM maintenance_schedules";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        $stats = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // Calculer les pourcentages
        $total = (int)$stats['total'];
        if ($total > 0) {
            $stats['completion_rate'] = round(($stats['completed'] / $total) * 100, 2);
            $stats['overdue_rate'] = round(($stats['overdue'] / $total) * 100, 2);
        } else {
            $stats['completion_rate'] = 0;
            $stats['overdue_rate'] = 0;
        }
        
        return $stats;
    }

    /**
     * Récupère les entretiens par priorité
     */
    public function getByPriority(string $priority): array
    {
        $allowedPriorities = ['low', 'medium', 'high', 'critical'];
        
        if (!in_array($priority, $allowedPriorities)) {
            throw new \InvalidArgumentException("Priorité invalide: {$priority}");
        }

        $sql = "SELECT ms.*, v.name as vehicle_name, u.name as technician_name
                FROM maintenance_schedules ms
                LEFT JOIN vehicles v ON ms.vehicle_id = v.id
                LEFT JOIN users u ON ms.assigned_technician_id = u.id
                WHERE ms.priority = :priority
                ORDER BY ms.due_date ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':priority', $priority, \PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les entretiens par type
     */
    public function getByType(string $type): array
    {
        $sql = "SELECT ms.*, v.name as vehicle_name, u.name as technician_name
                FROM maintenance_schedules ms
                LEFT JOIN vehicles v ON ms.vehicle_id = v.id
                LEFT JOIN users u ON ms.assigned_technician_id = u.id
                WHERE ms.maintenance_type = :type
                ORDER BY ms.due_date ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':type', $type, \PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les entretiens pour une période donnée
     */
    public function getByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        $sql = "SELECT ms.*, v.name as vehicle_name, u.name as technician_name
                FROM maintenance_schedules ms
                LEFT JOIN vehicles v ON ms.vehicle_id = v.id
                LEFT JOIN users u ON ms.assigned_technician_id = u.id
                WHERE ms.due_date BETWEEN :start_date AND :end_date
                ORDER BY ms.due_date ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':start_date', $startDate->format('Y-m-d'), \PDO::PARAM_STR);
        $stmt->bindValue(':end_date', $endDate->format('Y-m-d'), \PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
