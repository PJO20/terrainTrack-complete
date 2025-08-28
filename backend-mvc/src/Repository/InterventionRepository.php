<?php

namespace App\Repository;

use App\Entity\Intervention;
use App\Service\Database;
use PDO;

class InterventionRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Enregistre une nouvelle intervention en base.
     */
    public function save(Intervention $intervention): void
    {
        try {
            $query = "INSERT INTO interventions (vehicle_id, type, priority, description, scheduled_date, status, created_at, technicien, latitude, longitude, photo, title, team) 
                     VALUES (:vehicle_id, :type, :priority, :description, :scheduled_date, :status, :created_at, :technicien, :latitude, :longitude, :photo, :title, :team)";
            $stmt = $this->db->prepare($query);
            
            // Gérer scheduled_date correctement
            $scheduledDate = $intervention->getScheduledDate();
            if (empty($scheduledDate)) {
                $scheduledDate = null;
            }
            
            $result = $stmt->execute([
                'vehicle_id' => $intervention->getVehicleId(),
                'type' => $intervention->getType(),
                'priority' => $intervention->getPriority(),
                'description' => $intervention->getDescription(),
                'scheduled_date' => $scheduledDate,
                'status' => $intervention->getStatus(),
                'created_at' => $intervention->getCreatedAt(),
                'technicien' => $intervention->getTechnicien(),
                'latitude' => $intervention->getLatitude(),
                'longitude' => $intervention->getLongitude(),
                'photo' => $intervention->getPhoto(),
                'title' => $intervention->getTitle(),
                'team' => $intervention->getTeam()
            ]);
            
            // Récupérer et assigner l'ID généré automatiquement
            if ($result) {
                $intervention->setId((int)$this->db->lastInsertId());
            }
        } catch (\PDOException $e) {
            die("Erreur dans InterventionRepository::save : " . $e->getMessage());
        }
    }

    /**
     * Récupère toutes les interventions en base.
     * @return Intervention[]
     */
    public function findAll(): array
    {
        try {
            $query = "SELECT i.*, v.name as vehicle_name 
                     FROM interventions i 
                     LEFT JOIN vehicles v ON i.vehicle_id = v.id 
                     ORDER BY i.created_at DESC";
            $stmt = $this->db->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            die("Erreur dans InterventionRepository::findAll : " . $e->getMessage());
        }
    }

    public function findById(int $id): ?array
    {
        try {
            $query = "SELECT i.*, v.name as vehicle_name 
                     FROM interventions i 
                     LEFT JOIN vehicles v ON i.vehicle_id = v.id 
                     WHERE i.id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            die("Erreur dans InterventionRepository::findById : " . $e->getMessage());
        }
    }

    /**
     * Récupère toutes les interventions filtrées et triées.
     * @param string|null $status
     * @param string|null $priority
     * @param string|null $type
     * @param string|null $sort
     * @return array
     */
    public function findAllFiltered(?string $status, ?string $priority, ?string $type, ?string $sort): array
    {
        try {
            $query = "SELECT i.*, v.name as vehicle_name FROM interventions i LEFT JOIN vehicles v ON i.vehicle_id = v.id";
            $params = [];
            $conditions = [];
            if ($status) {
                $conditions[] = "i.status = :status";
                $params['status'] = $status;
            }
            if ($priority) {
                $conditions[] = "i.priority = :priority";
                $params['priority'] = $priority;
            }
            if ($type) {
                $conditions[] = "i.type = :type";
                $params['type'] = $type;
            }
            if ($conditions) {
                $query .= " WHERE " . implode(" AND ", $conditions);
            }
            // Gestion du tri
            $orderBy = "i.scheduled_date DESC";
            if ($sort === 'priority_asc') {
                $orderBy = "FIELD(i.priority, 'low', 'medium', 'high', 'critical'), i.scheduled_date DESC";
            } elseif ($sort === 'priority_desc') {
                $orderBy = "FIELD(i.priority, 'critical', 'high', 'medium', 'low'), i.scheduled_date DESC";
            } elseif ($sort === 'status_asc') {
                $orderBy = "i.status ASC, i.scheduled_date DESC";
            } elseif ($sort === 'status_desc') {
                $orderBy = "i.status DESC, i.scheduled_date DESC";
            } elseif ($sort === 'scheduled_date_asc') {
                $orderBy = "i.scheduled_date ASC";
            } elseif ($sort === 'scheduled_date_desc' || !$sort) {
                $orderBy = "i.scheduled_date DESC";
            }
            $query .= " ORDER BY $orderBy";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            die("Erreur dans InterventionRepository::findAllFiltered : " . $e->getMessage());
        }
    }

    /**
     * Met à jour le statut d'une intervention
     */
    public function updateStatus(int $id, string $status): bool
    {
        try {
            $query = "UPDATE interventions SET status = :status WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                'id' => $id,
                'status' => $status
            ]);

            // Vérifier si la mise à jour a affecté une ligne
            if ($result && $stmt->rowCount() > 0) {
                return true;
            }
            return false;
        } catch (\PDOException $e) {
            error_log("Erreur dans InterventionRepository::updateStatus : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour la liste des techniciens assignés à une intervention
     */
    public function updateTechnicians(int $id, string $technicians): bool
    {
        try {
            error_log("[updateTechnicians] id=$id, technicians=$technicians");
            $query = "UPDATE interventions SET technicien = :technicien WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                'id' => $id,
                'technicien' => $technicians
            ]);
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("[updateTechnicians] SQL ERROR: " . print_r($errorInfo, true));
            }
            return $result && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Erreur dans InterventionRepository::updateTechnicians : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour le véhicule assigné à une intervention
     */
    public function updateVehicle(int $id, ?int $vehicleId): bool
    {
        try {
            error_log("[updateVehicle] id=$id, vehicleId=$vehicleId");
            $query = "UPDATE interventions SET vehicle_id = :vehicle_id WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                'id' => $id,
                'vehicle_id' => $vehicleId
            ]);
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("[updateVehicle] SQL ERROR: " . print_r($errorInfo, true));
            }
            return $result && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Erreur dans InterventionRepository::updateVehicle : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour le titre d'une intervention
     */
    public function updateTitle(int $id, string $title): bool
    {
        try {
            error_log("[updateTitle] id=$id, title=$title");
            $query = "UPDATE interventions SET title = :title WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                'id' => $id,
                'title' => $title
            ]);
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("[updateTitle] SQL ERROR: " . print_r($errorInfo, true));
            }
            return $result && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Erreur dans InterventionRepository::updateTitle : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour la description d'une intervention
     */
    public function updateDescription(int $id, string $description): bool
    {
        try {
            error_log("[updateDescription] id=$id, description=$description");
            $query = "UPDATE interventions SET description = :description WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                'id' => $id,
                'description' => $description
            ]);
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("[updateDescription] SQL ERROR: " . print_r($errorInfo, true));
            }
            return $result && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Erreur dans InterventionRepository::updateDescription : " . $e->getMessage());
            return false;
        }
    }

    public function findByVehicleId(int $vehicleId): array
    {
        try {
            $query = "SELECT i.*, v.name as vehicle_name FROM interventions i LEFT JOIN vehicles v ON i.vehicle_id = v.id WHERE i.vehicle_id = :vehicle_id ORDER BY i.scheduled_date DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['vehicle_id' => $vehicleId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            die("Erreur dans InterventionRepository::findByVehicleId : " . $e->getMessage());
        }
    }

    /**
     * Récupère les N interventions les plus récentes
     */
    public function findRecent(int $limit = 10): array
    {
        $stmt = $this->db->prepare("SELECT * FROM interventions ORDER BY created_at DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les N interventions les plus récentes pour une équipe spécifique
     */
    public function findRecentByTeam(string $teamName, int $limit = 10): array
    {
        $stmt = $this->db->prepare("SELECT * FROM interventions WHERE team = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->bindValue(1, $teamName, PDO::PARAM_STR);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Supprime une intervention
     * @param int $id ID de l'intervention à supprimer
     * @return bool True si la suppression a réussi, false sinon
     */
    public function delete(int $id): bool
    {
        try {
            // Vérifier que l'intervention existe avant de la supprimer
            $checkQuery = "SELECT id FROM interventions WHERE id = :id";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute(['id' => $id]);
            
            if (!$checkStmt->fetch()) {
                error_log("Tentative de suppression d'une intervention inexistante (ID: $id)");
                return false;
            }

            $query = "DELETE FROM interventions WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute(['id' => $id]);
            
            if ($result && $stmt->rowCount() > 0) {
                error_log("Intervention ID $id supprimée avec succès");
                return true;
            }
            
            return false;
        } catch (\PDOException $e) {
            error_log("Erreur dans InterventionRepository::delete : " . $e->getMessage());
            return false;
        }
    }
}