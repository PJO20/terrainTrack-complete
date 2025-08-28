<?php

namespace App\Controller;

use App\Service\TwigService;
use App\Service\SessionManager;
use App\Repository\TeamRepository;
use App\Repository\VehicleRepository;
use App\Repository\UserRepository;
use App\Repository\InterventionRepository;

class TeamController
{
    private TwigService $twig;
    private TeamRepository $teamRepository;
    private VehicleRepository $vehicleRepository;
    private UserRepository $userRepository;
    private InterventionRepository $interventionRepository;

    public function __construct(TwigService $twig, TeamRepository $teamRepository, VehicleRepository $vehicleRepository, UserRepository $userRepository, InterventionRepository $interventionRepository)
    {
        $this->twig = $twig;
        $this->teamRepository = $teamRepository;
        $this->vehicleRepository = $vehicleRepository;
        $this->userRepository = $userRepository;
        $this->interventionRepository = $interventionRepository;
    }

    public function index()
    {
        SessionManager::requireLogin();
        
        $teams = $this->teamRepository->findAll();
        
        // Enrichir chaque équipe avec les statuts des véhicules
        foreach ($teams as &$team) {
            $vehicleStatuses = $this->calculateVehicleStatuses($team);
            $team->vehicle_statuses = $vehicleStatuses;
        }
        
        return $this->twig->render('teams.html.twig', [
            'teams' => $teams
        ]);
    }
    
    /**
     * Calculer les statuts des véhicules pour une équipe
     */
    private function calculateVehicleStatuses($team): array
    {
        $vehicleStatuses = [
            'disponible' => 0,
            'occupe' => 0,
            'maintenance' => 0,
            'total' => 0,
            'percentage_disponible' => 0
        ];
        
        // Si l'équipe n'a pas de véhicules assignés
        if (!isset($team->vehicle_ids) || empty($team->vehicle_ids)) {
            return $vehicleStatuses;
        }
        
        // Récupérer tous les véhicules de la base
        $allVehicles = $this->vehicleRepository->findAll();
        
        // Filtrer les véhicules de cette équipe et compter les statuts
        foreach ($allVehicles as $vehicle) {
            if (in_array($vehicle['id'], $team->vehicle_ids)) {
                $vehicleStatuses['total']++;
                
                $status = strtolower($vehicle['status'] ?? 'disponible');
                switch ($status) {
                    case 'disponible':
                    case 'available':
                        $vehicleStatuses['disponible']++;
                        break;
                    case 'en intervention':
                    case 'in_use':
                    case 'occupé':
                    case 'occupied':
                        $vehicleStatuses['occupe']++;
                        break;
                    case 'maintenance':
                    case 'en maintenance':
                        $vehicleStatuses['maintenance']++;
                        break;
                    default:
                        // Statuts inconnus comptés comme occupés
                        $vehicleStatuses['occupe']++;
                        break;
                }
            }
        }
        
        // Calculer le pourcentage de véhicules disponibles
        if ($vehicleStatuses['total'] > 0) {
            $vehicleStatuses['percentage_disponible'] = ($vehicleStatuses['disponible'] / $vehicleStatuses['total']) * 100;
        }
        
        return $vehicleStatuses;
    }

    public function create()
    {
        SessionManager::requireLogin();
        
        // Récupérer tous les membres depuis la base de données
        $allMembers = $this->userRepository->findAllMembers();
        
        // Récupérer tous les membres déjà assignés à des équipes existantes
        $assignedMemberIds = $this->teamRepository->getAssignedMemberIds();
        
        // Filtrer pour ne garder que les membres non assignés
        $availableMembers = array_filter($allMembers, function($member) use ($assignedMemberIds) {
            return !in_array($member['id'], $assignedMemberIds);
        });

        // Récupérer tous les véhicules depuis la base de données
        $allVehicles = $this->vehicleRepository->findAll();
        
        // Récupérer tous les véhicules déjà assignés à des équipes existantes
        $assignedVehicleIds = $this->teamRepository->getAssignedVehicleIds();
        
        // Filtrer pour ne garder que les véhicules non assignés
        $availableVehiclesFromDb = array_filter($allVehicles, function($vehicle) use ($assignedVehicleIds) {
            return !in_array($vehicle['id'], $assignedVehicleIds);
        });

        $availableVehicles = [];
        foreach ($availableVehiclesFromDb as $vehicle) {
            $availableVehicles[] = [
                'id' => $vehicle['id'],
                'name' => $vehicle['name'],
                'type' => $vehicle['brand'] . ' ' . $vehicle['model'],
                'emoji' => $this->getVehicleEmoji($vehicle['name'] ?? '', $vehicle['brand'] ?? '', $vehicle['model'] ?? '', $vehicle['type'] ?? '')
            ];
        }

        return $this->twig->render('team_create.html.twig', [
            'availableMembers' => $availableMembers,
            'availableVehicles' => $availableVehicles
        ]);
    }

    public function store()
    {
        SessionManager::requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $teamName = trim($_POST['team_name'] ?? '');
            $memberIds = $_POST['members'] ?? [];
            $vehicleIds = $_POST['vehicles'] ?? [];

            if (!empty($teamName)) {
                $this->teamRepository->createTeam($teamName, $memberIds, $vehicleIds);
                header('Location: /teams');
                exit;
            }
        }
        header('Location: /teams/create');
        exit;
    }

    public function show($id)
    {
        SessionManager::requireLogin();
        
        $team = $this->teamRepository->find($id);
        if (!$team) {
            http_response_code(404);
            return $this->twig->render('404.html.twig');
        }

        // Récupérer les détails des véhicules assignés à l'équipe
        $teamVehicles = [];
        if (isset($team->vehicle_ids) && !empty($team->vehicle_ids)) {
            $allVehicles = $this->vehicleRepository->findAll();
            foreach ($allVehicles as $vehicle) {
                if (in_array($vehicle['id'], $team->vehicle_ids)) {
                    $teamVehicles[] = [
                        'id' => $vehicle['id'],
                        'name' => $vehicle['name'],
                        'type' => $vehicle['brand'] . ' ' . $vehicle['model'],
                        'brand' => $vehicle['brand'],
                        'model' => $vehicle['model'],
                        'emoji' => $this->getVehicleEmoji($vehicle['name'] ?? '', $vehicle['brand'] ?? '', $vehicle['model'] ?? '', $vehicle['type'] ?? ''),
                        'status' => $vehicle['status'] ?? 'Disponible'
                    ];
                }
            }
        }

        // Récupérer les interventions récentes de l'équipe
        // Extraire le nom court de l'équipe (alpha, beta, gamma) du nom complet "Équipe Alpha"
        $teamName = strtolower($team->name);
        // Prendre le dernier mot après l'espace (Alpha → alpha)
        $parts = explode(' ', trim($teamName));
        $teamName = end($parts);
        $recentInterventions = $this->interventionRepository->findRecentByTeam($teamName, 5);
        
        // Enrichir les données des interventions avec les informations des véhicules
        $enrichedInterventions = array_map(function($intervention) {
            // Récupérer les détails du véhicule si l'ID existe
            $vehicle = null;
            if (!empty($intervention['vehicle_id'])) {
                $vehicle = $this->vehicleRepository->findById($intervention['vehicle_id']);
            }
            
            // Générer un titre par défaut si nécessaire
            $title = $intervention['title'] ?? '';
            if (empty(trim($title))) {
                $title = 'Intervention #' . ($intervention['id'] ?? 'Unknown');
            }
            
            return [
                'id' => $intervention['id'] ?? null,
                'title' => $title,
                'description' => $intervention['description'] ?? 'Aucune description',
                'status' => $intervention['status'] ?? 'pending',
                'priority' => $intervention['priority'] ?? 'medium',
                'technicien' => $intervention['technicien'] ?? 'Non assigné',
                'scheduled_date' => $intervention['scheduled_date'] ?? null,
                'created_at' => $intervention['created_at'] ?? null,
                'vehicle' => $vehicle ? [
                    'name' => $vehicle['name'],
                    'type' => $vehicle['type'],
                    'emoji' => $this->getVehicleEmoji($vehicle['name'] ?? '', $vehicle['brand'] ?? '', $vehicle['model'] ?? '', $vehicle['type'] ?? '')
                ] : null,
                'status_class' => $this->getInterventionStatusBadgeClass($intervention['status'] ?? 'pending'),
                'priority_class' => $this->getPriorityBadgeClass($intervention['priority'] ?? 'medium'),
                'status_label' => $this->getStatusLabel($intervention['status'] ?? 'pending'),
                'priority_label' => $this->getPriorityLabel($intervention['priority'] ?? 'medium')
            ];
        }, $recentInterventions);

        return $this->twig->render('team_show.html.twig', [
            'team' => $team,
            'team_vehicles' => $teamVehicles,
            'recent_interventions' => $enrichedInterventions
        ]);
    }

    public function edit($id)
    {
        $team = $this->teamRepository->find($id);
        if (!$team) {
            http_response_code(404);
            return $this->twig->render('404.html.twig');
        }

        // Récupérer tous les membres depuis la base de données
        $allMembers = $this->userRepository->findAllMembers();
        
        // Récupérer tous les membres déjà assignés à d'autres équipes (exclure l'équipe actuelle)
        $assignedMemberIds = $this->teamRepository->getAssignedMemberIds($id);
        
        // Filtrer pour ne garder que les membres non assignés OU assignés à l'équipe actuelle
        $availableMembers = array_filter($allMembers, function($member) use ($assignedMemberIds, $team) {
            $isAssignedElsewhere = in_array($member['id'], $assignedMemberIds);
            $isInCurrentTeam = in_array($member['id'], $team->member_ids ?? []);
            return !$isAssignedElsewhere || $isInCurrentTeam;
        });
        
        // Récupérer tous les véhicules depuis la base de données
        $allVehicles = $this->vehicleRepository->findAll();
        
        // Récupérer tous les véhicules déjà assignés à d'autres équipes (exclure l'équipe actuelle)
        $assignedVehicleIds = $this->teamRepository->getAssignedVehicleIds($id);
        
        // Filtrer pour ne garder que les véhicules non assignés OU assignés à l'équipe actuelle
        $availableVehiclesFromDb = array_filter($allVehicles, function($vehicle) use ($assignedVehicleIds, $team) {
            $isAssignedElsewhere = in_array($vehicle['id'], $assignedVehicleIds);
            $isInCurrentTeam = in_array($vehicle['id'], $team->vehicle_ids ?? []);
            return !$isAssignedElsewhere || $isInCurrentTeam;
        });
        
        $availableVehicles = [];
        foreach ($availableVehiclesFromDb as $vehicle) {
            $availableVehicles[] = [
                'id' => $vehicle['id'],
                'name' => $vehicle['name'],
                'type' => $vehicle['brand'] . ' ' . $vehicle['model'],
                'emoji' => $this->getVehicleEmoji($vehicle['name'] ?? '', $vehicle['brand'] ?? '', $vehicle['model'] ?? '', $vehicle['type'] ?? '')
            ];
        }
        
        return $this->twig->render('team_edit.html.twig', [
            'team' => $team,
            'available_members' => $availableMembers,
            'available_vehicles' => $availableVehicles,
        ]);
    }

    public function update($id)
    {
        error_log("=== TeamController::update called ===");
        error_log("ID: $id");
        error_log("Method: " . $_SERVER['REQUEST_METHOD']);
        error_log("POST data: " . print_r($_POST, true));
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $teamName = trim($_POST['name'] ?? '');
            $memberIds = $_POST['members'] ?? [];
            $vehicleIds = $_POST['vehicles'] ?? [];

            error_log("Parsed - Team name: '$teamName'");
            error_log("Parsed - Member IDs: " . print_r($memberIds, true));
            error_log("Parsed - Vehicle IDs: " . print_r($vehicleIds, true));

            if (!empty($teamName)) {
                error_log("Team name is not empty, proceeding with update");
                $result = $this->teamRepository->updateTeam((int)$id, $teamName, $memberIds, $vehicleIds);
                error_log("Update result: " . ($result ? 'SUCCESS' : 'FAILED'));
                
                // Vérifier immédiatement après mise à jour
                $updatedTeam = $this->teamRepository->find((int)$id);
                error_log("After update - vehicle_ids: " . print_r($updatedTeam->vehicle_ids ?? [], true));
                
                header('Location: /teams');
                exit;
            } else {
                error_log("Team name is empty! Redirecting to edit page");
            }
        } else {
            error_log("Not a POST request, method: " . $_SERVER['REQUEST_METHOD']);
        }
        
        error_log("Redirecting to edit page");
        header('Location: /teams/' . $id . '/edit');
        exit;
    }

    /**
     * Détermine l'emoji approprié selon le type de véhicule
     */
    private function getVehicleEmoji(string $name, string $brand, string $model, string $type): string
    {
        // Normaliser les chaînes pour comparaison
        $nameLower = strtolower($name ?? '');
        $brandLower = strtolower($brand ?? '');
        $modelLower = strtolower($model ?? '');
        $typeLower = strtolower($type ?? '');
        
        // Combinaisons spécifiques par nom et marque
        $fullName = $nameLower . ' ' . $brandLower . ' ' . $modelLower;
        
        // Quads - 🏍️
        if (strpos($nameLower, 'quad') !== false || 
            strpos($fullName, 'quad') !== false || 
            strpos($nameLower, 'explorer') !== false ||
            strpos($nameLower, 'sport') !== false && strpos($typeLower, 'quad') !== false) {
            return '🏍️';
        }
        
        // Tracteurs - 🚜
        if (strpos($nameLower, 'jd') !== false || 
            strpos($brandLower, 'john deere') !== false ||
            strpos($modelLower, '6120r') !== false ||
            strpos($typeLower, 'tracteur') !== false ||
            strpos($typeLower, 'tractor') !== false) {
            return '🚜';
        }
        
        // Fourgons utilitaires - 🚐
        if (strpos($brandLower, 'mercedes') !== false && strpos($modelLower, 'sprinter') !== false ||
            strpos($brandLower, 'renault') !== false && strpos($modelLower, 'master') !== false ||
            strpos($modelLower, 'sprinter') !== false ||
            strpos($modelLower, 'master') !== false ||
            strpos($typeLower, 'fourgon') !== false ||
            strpos($typeLower, 'utilitaire') !== false) {
            return '🚐';
        }
        
        // Camions - 🚛
        if (strpos($nameLower, 'camion') !== false ||
            strpos($brandLower, 'daf') !== false ||
            strpos($brandLower, 'scania') !== false ||
            strpos($modelLower, 'r730') !== false ||
            strpos($modelLower, 'kerax') !== false ||
            strpos($modelLower, 'r') !== false && strpos($brandLower, 'scania') !== false ||
            strpos($typeLower, 'camion') !== false ||
            strpos($typeLower, 'truck') !== false) {
            return '🚛';
        }
        
        // Véhicules de transport général - 🚚
        if (strpos($nameLower, 'transport') !== false) {
            return '🚚';
        }
        
        // Fallback selon le type
        switch ($typeLower) {
            case 'quad':
            case 'atv':
                return '🏍️';
            case 'tracteur':
            case 'tractor':
                return '🚜';
            case 'camion':
            case 'truck':
                return '🚛';
            case 'fourgon':
            case 'van':
            case 'utilitaire':
                return '🚐';
            default:
                return '🚗'; // Véhicule générique
        }
    }

    /**
     * Fonction pour obtenir la classe CSS du badge de statut d'intervention
     */
    private function getInterventionStatusBadgeClass(string $status): string
    {
        return match (strtolower($status)) {
            'scheduled', 'planifiée', 'pending' => 'badge-blue',
            'in-progress', 'en cours' => 'badge-lightblue',
            'completed', 'terminée' => 'badge-green',
            'cancelled', 'annulée' => 'badge-red',
            default => 'badge-blue'
        };
    }

    /**
     * Fonction pour obtenir la classe CSS du badge de priorité
     */
    private function getPriorityBadgeClass(string $priority): string
    {
        return match (strtolower($priority)) {
            'low', 'faible' => 'badge-green',
            'medium', 'moyenne' => 'badge-cyan',
            'high', 'élevée' => 'badge-yellow',
            'critical', 'critique' => 'badge-red',
            default => 'badge-cyan'
        };
    }

    /**
     * Fonction pour obtenir le label du statut en français
     */
    private function getStatusLabel(string $status): string
    {
        return match (strtolower($status)) {
            'scheduled', 'planifiée', 'pending' => 'En attente',
            'in-progress', 'en cours' => 'En cours',
            'completed', 'terminée' => 'Terminée',
            'cancelled', 'annulée' => 'Annulée',
            default => 'En attente'
        };
    }

    /**
     * Fonction pour obtenir le label de priorité en français
     */
    private function getPriorityLabel(string $priority): string
    {
        return match (strtolower($priority)) {
            'low', 'faible' => 'Faible',
            'medium', 'moyenne' => 'Moyenne',
            'high', 'élevée' => 'Élevée',
            'critical', 'critique' => 'Critique',
            default => 'Moyenne'
        };
    }

    /**
     * Créer un membre pour une équipe (avec ID numérique)
     */
    public function createMember($id)
    {
        $team = $this->teamRepository->find($id);
        if (!$team) {
            http_response_code(404);
            return $this->twig->render('404.html.twig');
        }

        return $this->twig->render('member_create.html.twig', [
            'team' => $team
        ]);
    }

    /**
     * Stocker un nouveau membre pour une équipe (avec ID numérique)
     */
    public function storeMember($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $memberData = [
                'full_name' => trim($_POST['full_name'] ?? ''),
                'role' => trim($_POST['role'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? '')
            ];

            if (!empty($memberData['full_name']) && !empty($memberData['email'])) {
                $this->teamRepository->addMember((int)$id, $memberData);
                header('Location: /teams/' . $id);
                exit;
            }
        }
        header('Location: /teams/' . $id . '/members/create');
        exit;
    }

    // Méthodes pour les équipes avec noms (alpha, beta, gamma)
    
    /**
     * Afficher l'équipe Alpha (ID = 1)
     */
    public function showAlpha()
    {
        return $this->show(1);
    }

    /**
     * Afficher l'équipe Beta (ID = 2)
     */
    public function showBeta()
    {
        return $this->show(2);
    }

    /**
     * Afficher l'équipe Gamma (ID = 3)
     */
    public function showGamma()
    {
        return $this->show(3);
    }

    /**
     * Éditer l'équipe Alpha
     */
    public function editAlpha()
    {
        return $this->edit(1);
    }

    /**
     * Éditer l'équipe Beta
     */
    public function editBeta()
    {
        return $this->edit(2);
    }

    /**
     * Éditer l'équipe Gamma
     */
    public function editGamma()
    {
        return $this->edit(3);
    }

    /**
     * Créer un membre pour l'équipe Alpha
     */
    public function createMemberAlpha()
    {
        $team = $this->teamRepository->find(1);
        if (!$team) {
            http_response_code(404);
            return $this->twig->render('404.html.twig');
        }

        return $this->twig->render('member_create.html.twig', [
            'team' => $team
        ]);
    }

    /**
     * Créer un membre pour l'équipe Beta
     */
    public function createMemberBeta()
    {
        $team = $this->teamRepository->find(2);
        if (!$team) {
            http_response_code(404);
            return $this->twig->render('404.html.twig');
        }

        return $this->twig->render('member_create_beta.html.twig', [
            'team' => $team
        ]);
    }

    /**
     * Créer un membre pour l'équipe Gamma
     */
    public function createMemberGamma()
    {
        $team = $this->teamRepository->find(3);
        if (!$team) {
            http_response_code(404);
            return $this->twig->render('404.html.twig');
        }

        return $this->twig->render('member_create_gamma.html.twig', [
            'team' => $team
        ]);
    }

    /**
     * Stocker un membre pour l'équipe Alpha
     */
    public function storeMemberAlpha()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $memberData = [
                'full_name' => trim($_POST['full_name'] ?? ''),
                'role' => trim($_POST['role'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? '')
            ];

            if (!empty($memberData['full_name']) && !empty($memberData['email'])) {
                $this->teamRepository->addMember(1, $memberData);
                header('Location: /teams/alpha');
                exit;
            }
        }
        header('Location: /teams/alpha/members/create');
        exit;
    }

    /**
     * Stocker un membre pour l'équipe Beta
     */
    public function storeMemberBeta()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $memberData = [
                'full_name' => trim($_POST['full_name'] ?? ''),
                'role' => trim($_POST['role'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? '')
            ];

            if (!empty($memberData['full_name']) && !empty($memberData['email'])) {
                $this->teamRepository->addMember(2, $memberData);
                header('Location: /teams/beta');
                exit;
            }
        }
        header('Location: /teams/beta/members/create');
        exit;
    }

    /**
     * Stocker un membre pour l'équipe Gamma
     */
    public function storeMemberGamma()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $memberData = [
                'full_name' => trim($_POST['full_name'] ?? ''),
                'role' => trim($_POST['role'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? '')
            ];

            if (!empty($memberData['full_name']) && !empty($memberData['email'])) {
                $this->teamRepository->addMember(3, $memberData);
                header('Location: /teams/gamma');
                exit;
            }
        }
        header('Location: /teams/gamma/members/create');
        exit;
    }
}
