<?php

namespace App\Repository;

use App\Entity\Vehicle;
use PDO;

class VehicleRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findAll(): array
    {
        $query = "SELECT * FROM vehicles ORDER BY name ASC";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $query = "SELECT * FROM vehicles WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $query = "UPDATE vehicles SET name = :name, brand = :brand, model = :model, type = :type, year = :year, plate_number = :plate_number, status = :status, mileage = :mileage, usage_hours = :usage_hours, last_maintenance = :last_maintenance, next_maintenance = :next_maintenance, notes = :notes WHERE id = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'brand' => $data['brand'],
            'model' => $data['model'],
            'type' => $data['type'],
            'year' => $data['year'],
            'plate_number' => $data['registration'],
            'status' => $data['status'],
            'mileage' => $data['mileage'],
            'usage_hours' => $data['usage_hours'],
            'last_maintenance' => $data['last_maintenance'],
            'next_maintenance' => $data['next_maintenance'],
            'notes' => $data['notes'],
        ]);
    }

    public function save(array $data): ?int
    {
        $query = "INSERT INTO vehicles (name, brand, model, type, year, plate_number, status, mileage, usage_hours, last_maintenance, next_maintenance, notes, created_at) VALUES (:name, :brand, :model, :type, :year, :plate_number, :status, :mileage, :usage_hours, :last_maintenance, :next_maintenance, :notes, NOW())";
        $stmt = $this->db->prepare($query);
        
        $success = $stmt->execute([
            'name' => $data['name'],
            'brand' => $data['brand'],
            'model' => $data['model'],
            'type' => $data['type'],
            'year' => $data['year'],
            'plate_number' => $data['plate_number'],
            'status' => $data['status'],
            'mileage' => $data['mileage'],
            'usage_hours' => $data['usage_hours'],
            'last_maintenance' => $data['last_maintenance'],
            'next_maintenance' => $data['next_maintenance'],
            'notes' => $data['notes'],
        ]);
        
        return $success ? $this->db->lastInsertId() : null;
    }

    /**
     * Supprime un véhicule
     * @param int $id ID du véhicule à supprimer
     * @return bool True si la suppression a réussi, false sinon
     */
    public function delete(int $id): bool
    {
        try {
            // Vérifier que le véhicule existe avant de le supprimer
            $checkQuery = "SELECT id FROM vehicles WHERE id = :id";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute(['id' => $id]);
            
            if (!$checkStmt->fetch()) {
                error_log("Tentative de suppression d'un véhicule inexistant (ID: $id)");
                return false;
            }

            $query = "DELETE FROM vehicles WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute(['id' => $id]);
            
            if ($result && $stmt->rowCount() > 0) {
                error_log("Véhicule ID $id supprimé avec succès");
                return true;
            }
            
            return false;
        } catch (\PDOException $e) {
            error_log("Erreur dans VehicleRepository::delete : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère uniquement les véhicules disponibles et non occupés
     * @return array Véhicules disponibles pour une nouvelle intervention
     */
    public function findAvailableVehicles(): array
    {
        try {
            $query = "SELECT v.* FROM vehicles v 
                     WHERE v.status IN ('Disponible', 'Available', 'disponible', 'available')
                     AND v.id NOT IN (
                         SELECT DISTINCT i.vehicle_id 
                         FROM interventions i 
                         WHERE i.vehicle_id IS NOT NULL 
                         AND i.status IN ('pending', 'in-progress', 'en cours', 'planifiée', 'active')
                     )
                     ORDER BY v.name ASC";
            $stmt = $this->db->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur dans VehicleRepository::findAvailableVehicles : " . $e->getMessage());
            return [];
        }
    }
} 