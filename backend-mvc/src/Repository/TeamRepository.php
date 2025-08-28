<?php

namespace App\Repository;

use PDO;

class TeamRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Récupère toutes les équipes
     */
    public function findAll(): array
    {
        $query = "SELECT * FROM teams ORDER BY name ASC";
        $stmt = $this->db->query($query);
        $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupérer les détails des membres une seule fois
        $userRepo = new \App\Repository\UserRepository();
        $allMembers = $userRepo->findAllMembers();
        
        // Convertir les JSON en arrays et créer des objets avec les membres complets
        return array_map(function($team) use ($allMembers) {
            $memberIds = json_decode($team['member_ids'], true) ?: [];
            
            // Récupérer les détails complets des membres
            $members = [];
            foreach ($memberIds as $memberId) {
                foreach ($allMembers as $member) {
                    if ($member['id'] == $memberId) {
                        $members[] = $member;
                        break;
                    }
                }
            }
            
            return (object) [
                'id' => $team['id'],
                'name' => $team['name'],
                'lead' => $team['team_lead'],
                'member_ids' => $memberIds,
                'members' => $members, // Ajouter les détails complets des membres
                'vehicle_ids' => json_decode($team['vehicle_ids'], true) ?: [],
                'vehicles_count' => $team['vehicles_count'],
                'active_interventions' => $team['active_interventions'],
                'created_at' => $team['created_at'],
                'updated_at' => $team['updated_at']
            ];
        }, $teams);
    }

    /**
     * Récupère une équipe par son ID
     */
    public function find(int $id): ?object
    {
        $query = "SELECT * FROM teams WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['id' => $id]);
        $team = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$team) {
            return null;
        }
        
        $memberIds = json_decode($team['member_ids'], true) ?: [];
        
        // Récupérer les détails complets des membres
        $userRepo = new \App\Repository\UserRepository();
        $allMembers = $userRepo->findAllMembers();
        $members = [];
        foreach ($memberIds as $memberId) {
            foreach ($allMembers as $member) {
                if ($member['id'] == $memberId) {
                    $members[] = $member;
                    break;
                }
            }
        }
        
        // Convertir en objet avec les JSON décodés et les membres complets
        return (object) [
            'id' => $team['id'],
            'name' => $team['name'],
            'lead' => $team['team_lead'],
            'member_ids' => $memberIds,
            'members' => $members, // Ajouter les détails complets des membres
            'vehicle_ids' => json_decode($team['vehicle_ids'], true) ?: [],
            'vehicles_count' => $team['vehicles_count'],
            'active_interventions' => $team['active_interventions'],
            'created_at' => $team['created_at'],
            'updated_at' => $team['updated_at']
        ];
    }

    /**
     * Crée une nouvelle équipe
     */
    public function createTeam(string $teamName, array $memberIds, array $vehicleIds): bool
    {
        // Récupérer les informations du chef d'équipe (premier membre)
        $leadName = $teamName; // Par défaut
        if (!empty($memberIds)) {
            $userRepo = new \App\Repository\UserRepository();
            $users = $userRepo->findAllMembers();
            foreach ($users as $user) {
                if ($user['id'] == $memberIds[0]) {
                    $leadName = $user['name'];
                    break;
                }
            }
        }

        $query = "INSERT INTO teams (name, team_lead, member_ids, vehicle_ids, vehicles_count, active_interventions, created_at, updated_at) 
                  VALUES (:name, :team_lead, :member_ids, :vehicle_ids, :vehicles_count, 0, NOW(), NOW())";
        
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([
            'name' => $teamName,
            'team_lead' => $leadName,
            'member_ids' => json_encode($memberIds),
            'vehicle_ids' => json_encode($vehicleIds),
            'vehicles_count' => count($vehicleIds)
        ]);
    }

    /**
     * Met à jour une équipe
     */
    public function updateTeam(int $teamId, string $teamName, array $memberIds, array $vehicleIds): bool
    {
        // Récupérer les informations du chef d'équipe (premier membre)
        $leadName = $teamName; // Par défaut
        if (!empty($memberIds)) {
            $userRepo = new \App\Repository\UserRepository();
            $users = $userRepo->findAllMembers();
            foreach ($users as $user) {
                if ($user['id'] == $memberIds[0]) {
                    $leadName = $user['name'];
                    break;
                }
            }
        }

        $query = "UPDATE teams SET name = :name, team_lead = :team_lead, member_ids = :member_ids, vehicle_ids = :vehicle_ids, vehicles_count = :vehicles_count, updated_at = NOW() WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([
            'id' => $teamId,
            'name' => $teamName,
            'team_lead' => $leadName,
            'member_ids' => json_encode($memberIds),
            'vehicle_ids' => json_encode($vehicleIds),
            'vehicles_count' => count($vehicleIds)
        ]);
    }

    /**
     * Ajoute un membre à une équipe
     */
    public function addMember(int $teamId, array $memberData): bool
    {
        $team = $this->find($teamId);
        if (!$team) {
            return false;
        }

        // Logique d'ajout de membre (pour compatibilité avec l'ancien code)
        // Cette méthode pourrait être étendue selon les besoins
        return true;
    }

    /**
     * Récupère tous les IDs des membres assignés (pour éviter les doublons)
     */
    public function getAssignedMemberIds(?int $excludeTeamId = null): array
    {
        $query = "SELECT member_ids FROM teams";
        $params = [];
        
        if ($excludeTeamId) {
            $query .= " WHERE id != :exclude_id";
            $params['exclude_id'] = $excludeTeamId;
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $assignedIds = [];
        foreach ($teams as $team) {
            $memberIds = json_decode($team['member_ids'], true) ?: [];
            $assignedIds = array_merge($assignedIds, $memberIds);
        }
        
        return array_unique($assignedIds);
    }

    /**
     * Récupère tous les IDs des véhicules assignés (pour éviter les doublons)
     */
    public function getAssignedVehicleIds(?int $excludeTeamId = null): array
    {
        $query = "SELECT vehicle_ids FROM teams";
        $params = [];
        
        if ($excludeTeamId) {
            $query .= " WHERE id != :exclude_id";
            $params['exclude_id'] = $excludeTeamId;
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $assignedIds = [];
        foreach ($teams as $team) {
            $vehicleIds = json_decode($team['vehicle_ids'], true) ?: [];
            $assignedIds = array_merge($assignedIds, $vehicleIds);
        }
        
        return array_unique($assignedIds);
    }

    /**
     * Supprime une équipe
     */
    public function delete(int $id): bool
    {
        try {
            $query = "DELETE FROM teams WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute(['id' => $id]);
            
            return $result && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Erreur dans TeamRepository::delete : " . $e->getMessage());
            return false;
        }
    }
} 