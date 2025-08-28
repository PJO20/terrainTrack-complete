<?php

namespace App\Repository;

use App\Entity\Technician;
use PDO;

class TechnicianRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findAll(): array
    {
        $query = "SELECT * FROM technicians WHERE is_active = 1 ORDER BY name ASC";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $query = "SELECT * FROM technicians WHERE id = :id AND is_active = 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Récupère les techniciens d'une équipe spécifique
     */
    public function findByTeam(int $teamId): array
    {
        $query = "SELECT * FROM technicians WHERE is_active = 1 AND JSON_CONTAINS(team_ids, :team_id) ORDER BY name ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['team_id' => json_encode($teamId)]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les techniciens disponibles pour une équipe donnée
     * (ceux qui appartiennent à cette équipe ou qui sont polyvalents)
     */
    public function findAvailableForTeam(int $teamId): array
    {
        $query = "SELECT * FROM technicians 
                  WHERE is_active = 1 
                  AND (JSON_CONTAINS(team_ids, :team_id1) OR role IN ('Admin', 'Chef'))
                  ORDER BY 
                    CASE 
                      WHEN JSON_CONTAINS(team_ids, :team_id2) THEN 1 
                      ELSE 2 
                    END,
                    name ASC";
        $stmt = $this->db->prepare($query);
        $teamIdJson = json_encode($teamId);
        $stmt->execute([
            'team_id1' => $teamIdJson,
            'team_id2' => $teamIdJson
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère tous les techniciens actifs avec leurs noms et spécialisations
     */
    public function findAllActive(): array
    {
        $query = "SELECT id, name, email, role, specialization, team_ids 
                  FROM technicians 
                  WHERE is_active = 1 
                  ORDER BY name ASC";
        $stmt = $this->db->query($query);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Décoder les team_ids JSON pour chaque technicien
        return array_map(function($tech) {
            $tech['team_ids'] = json_decode($tech['team_ids'], true) ?? [];
            return $tech;
        }, $result);
    }

    /**
     * Récupère les techniciens par rôle
     */
    public function findByRole(string $role): array
    {
        $query = "SELECT * FROM technicians WHERE is_active = 1 AND role = :role ORDER BY name ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['role' => $role]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Créer un nouveau technicien
     */
    public function save(Technician $technician): ?int
    {
        $query = "INSERT INTO technicians (name, email, role, team_ids, phone, specialization, is_active) 
                  VALUES (:name, :email, :role, :team_ids, :phone, :specialization, :is_active)";
        $stmt = $this->db->prepare($query);
        
        $success = $stmt->execute([
            'name' => $technician->getName(),
            'email' => $technician->getEmail(),
            'role' => $technician->getRole(),
            'team_ids' => json_encode($technician->getTeamIds()),
            'phone' => $technician->getPhone(),
            'specialization' => $technician->getSpecialization(),
            'is_active' => $technician->isActive()
        ]);
        
        return $success ? $this->db->lastInsertId() : null;
    }

    /**
     * Mettre à jour un technicien
     */
    public function update(int $id, Technician $technician): bool
    {
        $query = "UPDATE technicians SET 
                    name = :name, 
                    email = :email, 
                    role = :role, 
                    team_ids = :team_ids, 
                    phone = :phone, 
                    specialization = :specialization, 
                    is_active = :is_active,
                    updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([
            'id' => $id,
            'name' => $technician->getName(),
            'email' => $technician->getEmail(),
            'role' => $technician->getRole(),
            'team_ids' => json_encode($technician->getTeamIds()),
            'phone' => $technician->getPhone(),
            'specialization' => $technician->getSpecialization(),
            'is_active' => $technician->isActive()
        ]);
    }

    /**
     * Supprimer un technicien (soft delete)
     */
    public function delete(int $id): bool
    {
        $query = "UPDATE technicians SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Vérifier si un technicien avec cet email existe déjà
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = "SELECT COUNT(*) FROM technicians WHERE email = :email AND is_active = 1";
        $params = ['email' => $email];
        
        if ($excludeId) {
            $query .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Convertir un tableau de données en objet Technician
     */
    private function arrayToTechnician(array $data): Technician
    {
        $technician = new Technician();
        $technician->setId($data['id'] ?? null)
                   ->setName($data['name'])
                   ->setEmail($data['email'])
                   ->setRole($data['role'])
                   ->setTeamIds(json_decode($data['team_ids'], true))
                   ->setPhone($data['phone'])
                   ->setSpecialization($data['specialization'])
                   ->setIsActive($data['is_active'])
                   ->setCreatedAt($data['created_at'])
                   ->setUpdatedAt($data['updated_at']);
        
        return $technician;
    }
} 