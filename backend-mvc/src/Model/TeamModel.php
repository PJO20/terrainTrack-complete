<?php

namespace App\Model;

class TeamModel
{
    private array $teams;

    public function __construct()
    {
        // Démarrer la session si elle n'est pas déjà démarrée
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Données de base des équipes
        $baseTeams = [
            1 => [
                'id' => 1,
                'name' => 'Équipe Alpha',
                'lead' => 'Sophie Dubois',
                'members' => [
                    ['id' => 2, 'name' => 'Sophie Dubois', 'role' => 'Chef', 'initials' => 'SD', 'email' => 'sophie.dubois@terraintrack.com'],
                    ['id' => 3, 'name' => 'Jean Leclerc', 'role' => 'Technicien', 'initials' => 'JL', 'email' => 'jean.leclerc@terraintrack.com'],
                    ['id' => 6, 'name' => 'Claire Bernard', 'role' => 'Mécanicien', 'initials' => 'CB', 'email' => 'claire.bernard@terraintrack.com'],
                ],
                'member_ids' => [2, 3, 6],
                'vehicles_count' => 2,
                'vehicle_ids' => [1, 2],
                'active_interventions' => 3
            ],
            2 => [
                'id' => 2,
                'name' => 'Équipe Beta',
                'lead' => 'Marie Petit',
                'members' => [
                    ['id' => 4, 'name' => 'Marie Petit', 'role' => 'Chef', 'initials' => 'MP', 'email' => 'marie.petit@terraintrack.com'],
                    ['id' => 5, 'name' => 'Pierre Moreau', 'role' => 'Technicien', 'initials' => 'PM', 'email' => 'pierre.moreau@terraintrack.com'],
                ],
                'member_ids' => [4, 5],
                'vehicles_count' => 3,
                'vehicle_ids' => [1, 2, 3],
                'active_interventions' => 1
            ],
            3 => [
                'id' => 3,
                'name' => 'Équipe Gamma',
                'lead' => 'Emma Leroy',
                'members' => [
                    ['id' => 7, 'name' => 'Emma Leroy', 'role' => 'Chef', 'initials' => 'EL', 'email' => 'emma.leroy@terraintrack.com'],
                ],
                'member_ids' => [7],
                'vehicles_count' => 1,
                'vehicle_ids' => [1],
                'active_interventions' => 1
            ],
            4 => [
                'id' => 4,
                'name' => 'Équipe Delta',
                'lead' => 'Thomas Martin',
                'members' => [
                    ['id' => 1, 'name' => 'Thomas Martin', 'role' => 'admin', 'initials' => 'TM', 'email' => 'thomas.martin@terraintrack.com'],
                    ['id' => 8, 'name' => 'Lisa Garcia', 'role' => 'Technicien', 'initials' => 'LG', 'email' => 'lisa.garcia@terraintrack.com'],
                    ['id' => 9, 'name' => 'David Wilson', 'role' => 'Technicien', 'initials' => 'DW', 'email' => 'david.wilson@terraintrack.com'],
                ],
                'member_ids' => [1, 8, 9],
                'vehicles_count' => 2,
                'vehicle_ids' => [2, 3],
                'active_interventions' => 0
            ],
        ];

        // Utiliser les données de session si elles existent, sinon utiliser les données de base
        if (isset($_SESSION['teams_data'])) {
            $this->teams = $_SESSION['teams_data'];
        } else {
            $this->teams = $baseTeams;
            $_SESSION['teams_data'] = $this->teams;
        }
    }

    /**
     * Find a team by its ID.
     *
     * @param integer $id
     * @return object|null
     */
    public function find(int $id): ?object
    {
        if (isset($this->teams[$id])) {
            // Convert the array to an object to simulate typical model behavior (like fetching from an ORM)
            return (object) $this->teams[$id];
        }
        return null;
    }

    /**
     * Find all teams.
     *
     * @return array
     */
    public function findAll(): array
    {
        // Convert all team arrays to objects
        return array_map(function($team) {
            return (object) $team;
        }, $this->teams);
    }

    /**
     * Add a member to a team.
     *
     * @param int $teamId
     * @param array $memberData
     * @return bool
     */
    public function addMember(int $teamId, array $memberData): bool
    {
        if (!isset($this->teams[$teamId])) {
            return false;
        }

        // Generate initials from full name
        $nameParts = explode(' ', $memberData['full_name']);
        $initials = '';
        if (count($nameParts) >= 2) {
            $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
        } else {
            $initials = strtoupper(substr($memberData['full_name'], 0, 2));
        }

        // Create new member
        $newMember = [
            'name' => $memberData['full_name'],
            'role' => $memberData['role'],
            'initials' => $initials,
            'email' => $memberData['email'],
            'phone' => $memberData['phone'] ?? ''
        ];

        // Add member to team
        $this->teams[$teamId]['members'][] = $newMember;

        // Update session data
        $_SESSION['teams_data'] = $this->teams;

        return true;
    }

    public function createTeam(string $teamName, array $memberIds, array $vehicleIds): bool
    {
        // Utiliser le UserRepository pour récupérer les vrais membres
        $userRepo = new \App\Repository\UserRepository();
        $allMembersFromDb = $userRepo->findAllMembers();
        $allMembers = [];
        
        // Convertir en format compatible avec l'ancien code
        foreach ($allMembersFromDb as $member) {
            $allMembers[$member['id']] = $member;
        }

        $selectedMembers = [];
        foreach ($memberIds as $memberId) {
            if (isset($allMembers[$memberId])) {
                $selectedMembers[] = $allMembers[$memberId];
            }
        }

        if (empty($selectedMembers)) {
            return false;
        }

        // Logique pour les véhicules
        $vehiclesCount = count($vehicleIds);

        // Créer la nouvelle équipe
        $newTeamId = max(array_keys($this->teams)) + 1;
        $newTeam = [
            'id' => $newTeamId,
            'name' => $teamName,
            'lead' => $selectedMembers[0]['name'], // Le premier membre sélectionné est le chef
            'members' => $selectedMembers,
            'member_ids' => $memberIds,
            'vehicles_count' => $vehiclesCount,
            'vehicle_ids' => $vehicleIds,
            'active_interventions' => 0
        ];

        $this->teams[$newTeamId] = $newTeam;
        $_SESSION['teams_data'] = $this->teams;

        return true;
    }

    public function updateTeam(int $teamId, string $teamName, array $memberIds, array $vehicleIds): bool
    {
        error_log("TeamModel::updateTeam called with teamId=$teamId, name='$teamName'");
        error_log("Member IDs: " . print_r($memberIds, true));
        error_log("Vehicle IDs: " . print_r($vehicleIds, true));
        
        if (!isset($this->teams[$teamId])) {
            error_log("Team $teamId not found");
            return false;
        }

        // Utiliser le UserRepository pour récupérer les vrais membres
        $userRepo = new \App\Repository\UserRepository();
        $allMembersFromDb = $userRepo->findAllMembers();
        $allMembers = [];
        
        // Convertir en format compatible avec l'ancien code
        foreach ($allMembersFromDb as $member) {
            $allMembers[$member['id']] = $member;
        }

        error_log("Available members from DB: " . count($allMembers));

        // Si aucun membre n'est fourni, garder les membres existants
        if (empty($memberIds)) {
            error_log("No member IDs provided, keeping existing members");
            $selectedMembers = $this->teams[$teamId]['members'];
            $memberIds = $this->teams[$teamId]['member_ids'];
        } else {
            $selectedMembers = [];
            foreach ($memberIds as $memberId) {
                if (isset($allMembers[$memberId])) {
                    $selectedMembers[] = $allMembers[$memberId];
                    error_log("Found member: {$allMembers[$memberId]['name']} (ID: $memberId)");
                } else {
                    error_log("Member ID $memberId not found in database");
                }
            }
            error_log("Selected " . count($selectedMembers) . " members");
        }

        // Mettre à jour l'équipe
        $this->teams[$teamId]['name'] = $teamName;
        $this->teams[$teamId]['members'] = $selectedMembers;
        $this->teams[$teamId]['member_ids'] = $memberIds;
        
        if (!empty($selectedMembers)) {
            $this->teams[$teamId]['lead'] = $selectedMembers[0]['name']; // Le premier membre sélectionné est le chef
        }
        
        $this->teams[$teamId]['vehicles_count'] = count($vehicleIds);
        $this->teams[$teamId]['vehicle_ids'] = $vehicleIds; // Ajouter les IDs des véhicules

        error_log("Team updated successfully. New vehicle_ids: " . print_r($vehicleIds, true));
        error_log("Team updated successfully. New member_ids: " . print_r($memberIds, true));

        // Mettre à jour les données de session
        $_SESSION['teams_data'] = $this->teams;

        return true;
    }
} 