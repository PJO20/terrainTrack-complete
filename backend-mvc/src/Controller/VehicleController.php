<?php

namespace App\Controller;

use App\Service\TwigService;
use App\Service\SessionManager;
use App\Service\EmailNotificationService;
use App\Service\SmsNotificationService;
use App\Repository\VehicleRepository;
use App\Repository\InterventionRepository;
use App\Repository\MaintenanceSchedulesRepository;

class VehicleController
{
    private TwigService $twig;
    private VehicleRepository $vehicleRepository;
    private InterventionRepository $interventionRepository;
    private EmailNotificationService $emailService;
    private SmsNotificationService $smsService;
    private MaintenanceSchedulesRepository $maintenanceRepository;

    public function __construct(
        TwigService $twig, 
        VehicleRepository $vehicleRepository, 
        InterventionRepository $interventionRepository,
        EmailNotificationService $emailService,
        SmsNotificationService $smsService,
        MaintenanceSchedulesRepository $maintenanceRepository
    ) {
        $this->twig = $twig;
        $this->vehicleRepository = $vehicleRepository;
        $this->interventionRepository = $interventionRepository;
        $this->emailService = $emailService;
        $this->smsService = $smsService;
        $this->maintenanceRepository = $maintenanceRepository;
    }

    private function calculateMaintenanceDue(array &$vehicle): void
    {
        if (!empty($vehicle['next_maintenance'])) {
            $nextMaintenance = new \DateTime($vehicle['next_maintenance']);
            $today = new \DateTime();
            $interval = $today->diff($nextMaintenance);
            $daysRemaining = $interval->invert ? -$interval->days : $interval->days;
            
            if ($daysRemaining < 0) {
                // Maintenance en retard
                $daysOverdue = abs($daysRemaining);
                $vehicle['maintenance_due'] = "Dépassée de {$daysOverdue} jours";
                $vehicle['maintenance_status'] = 'overdue';
            } else {
                // Maintenance à venir
                $vehicle['maintenance_due'] = "Dans {$daysRemaining} jours";
                $vehicle['maintenance_status'] = 'upcoming';
            }
        }
    }

    /**
     * Détermine l'emoji approprié selon le nom, marque, modèle et type du véhicule
     */
    private function getVehicleEmoji(string $name, string $brand, string $model, string $type): string
    {
        // Normaliser les chaînes pour comparaison
        $nameLower = strtolower($name);
        $brandLower = strtolower($brand);
        $modelLower = strtolower($model);
        $typeLower = strtolower($type);
        
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

    public function index()
    {
        SessionManager::requireLogin();
        
        $vehicles = $this->vehicleRepository->findAll();
        
        // Enrichir les données des véhicules avec les bons emojis et calculer la maintenance
        foreach ($vehicles as &$vehicle) {
            $vehicle['emoji'] = $this->getVehicleEmoji(
                $vehicle['name'] ?? '',
                $vehicle['brand'] ?? '',
                $vehicle['model'] ?? '',
                $vehicle['type'] ?? ''
            );
            // Calculer la maintenance pour chaque véhicule
            $this->calculateMaintenanceDue($vehicle);
        }
        
        // Gestion des messages de succès et d'erreur
        $successMessage = null;
        $errorMessage = null;
        
        if (isset($_GET['success'])) {
            switch ($_GET['success']) {
                case 'created':
                    $successMessage = 'created';
                    break;
                case 'deleted':
                    $successMessage = 'deleted';
                    break;
            }
        }
        
        if (isset($_GET['error'])) {
            switch ($_GET['error']) {
                case 'not_found':
                    $errorMessage = 'not_found';
                    break;
                case 'delete_failed':
                    $errorMessage = 'delete_failed';
                    break;
                case 'invalid_id':
                    $errorMessage = 'invalid_id';
                    break;
                default:
                    $errorMessage = 'unknown';
                    break;
            }
        }
        
        return $this->twig->render('vehicles.html.twig', [
            'vehicles' => $vehicles,
            'success_message' => $successMessage,
            'error_message' => $errorMessage
        ]);
    }

    public function create()
    {
        SessionManager::requireLogin();
        
        return $this->twig->render('vehicle_create.html.twig');
    }

    public function store()
    {
        SessionManager::requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Récupérer les données POST
            $data = [
                'name' => $_POST['name'] ?? '',
                'brand' => $_POST['brand'] ?? '',
                'model' => $_POST['model'] ?? '',
                'type' => $_POST['type'] ?? '',
                'year' => !empty($_POST['year']) ? (int)$_POST['year'] : null,
                'plate_number' => $_POST['plate_number'] ?? '',
                'status' => $_POST['status'] ?? 'Disponible',
                'mileage' => !empty($_POST['mileage']) ? (int)$_POST['mileage'] : 0,
                'usage_hours' => !empty($_POST['usage_hours']) ? (int)$_POST['usage_hours'] : 0,
                'last_maintenance' => !empty($_POST['last_maintenance']) ? $_POST['last_maintenance'] : null,
                'next_maintenance' => !empty($_POST['next_maintenance']) ? $_POST['next_maintenance'] : null,
                'notes' => $_POST['notes'] ?? '',
            ];

            // Validation des champs requis
            if (empty($data['name']) || empty($data['brand']) || empty($data['model']) || empty($data['type'])) {
                // Retourner le formulaire avec erreur
                return $this->twig->render('vehicle_create.html.twig', [
                    'error' => 'Veuillez remplir tous les champs obligatoires.',
                    'data' => $data
                ]);
            }

            // Sauvegarder le véhicule
            $vehicleId = $this->vehicleRepository->save($data);
            
            if ($vehicleId) {
                // Rediriger vers la liste des véhicules avec message de succès
                header('Location: /vehicles?success=created');
                exit;
            } else {
                // Erreur lors de la sauvegarde
                return $this->twig->render('vehicle_create.html.twig', [
                    'error' => 'Erreur lors de la création du véhicule.',
                    'data' => $data
                ]);
            }
        }

        // Si ce n'est pas une requête POST, rediriger vers le formulaire
        header('Location: /vehicles/create');
        exit;
    }

    public function edit($id)
    {
        SessionManager::requireLogin();
        
        $vehicle = $this->vehicleRepository->findById($id);
        if (!$vehicle) {
            header('HTTP/1.0 404 Not Found');
            echo 'Véhicule non trouvé';
            exit;
        }
        return $this->twig->render('vehicle_edit_form.html.twig', [
            'vehicle' => $vehicle
        ]);
    }

    public function update($id)
    {
        SessionManager::requireLogin();
        
        $vehicle = $this->vehicleRepository->findById($id);
        if (!$vehicle) {
            header('HTTP/1.0 404 Not Found');
            echo 'Véhicule non trouvé';
            exit;
        }
        // Récupérer les données POST
        $data = [
            'name' => $_POST['name'] ?? '',
            'brand' => $_POST['brand'] ?? '',
            'model' => $_POST['model'] ?? '',
            'type' => $_POST['type'] ?? '',
            'year' => !empty($_POST['year']) ? (int)$_POST['year'] : null,
            'registration' => $_POST['registration'] ?? '',
            'status' => $_POST['status'] ?? '',
            'mileage' => !empty($_POST['mileage']) ? (int)$_POST['mileage'] : null,
            'usage_hours' => !empty($_POST['usage_hours']) ? (int)$_POST['usage_hours'] : null,
            'last_maintenance' => !empty($_POST['last_maintenance']) ? $_POST['last_maintenance'] : null,
            'next_maintenance' => !empty($_POST['next_maintenance']) ? $_POST['next_maintenance'] : null,
            'notes' => $_POST['notes'] ?? '',
        ];
        $this->vehicleRepository->update($id, $data);
        
        // Recharger les données à jour
        $vehicle = $this->vehicleRepository->findById($id);
        // Calculer les jours restants
        $this->calculateMaintenanceDue($vehicle);
        $interventions = $this->interventionRepository->findByVehicleId($id);
        
        return $this->twig->render('vehicle_show.html.twig', [
            'vehicle' => $vehicle,
            'interventions' => $interventions
        ]);
    }

    public function show($id)
    {
        SessionManager::requireLogin();
        
        $vehicle = $this->vehicleRepository->findById($id);
        if (!$vehicle) {
            header('HTTP/1.0 404 Not Found');
            echo 'Véhicule non trouvé';
            exit;
        }

        // Enrichir avec le bon emoji
        $vehicle['emoji'] = $this->getVehicleEmoji(
            $vehicle['name'] ?? '',
            $vehicle['brand'] ?? '',
            $vehicle['model'] ?? '',
            $vehicle['type'] ?? ''
        );

        // Calculer les jours restants
        $this->calculateMaintenanceDue($vehicle);
        $interventions = $this->interventionRepository->findByVehicleId($id);

        // Si AJAX/partial demandé, ne rendre que le fragment
        if (isset($_GET['partial']) && $_GET['partial'] == 1) {
            return $this->twig->render('vehicle_show.html.twig', [
                'vehicle' => $vehicle,
                'interventions' => $interventions
            ]);
        }

        // Sinon, template complet
        return $this->twig->render('vehicle_show.html.twig', [
            'vehicle' => $vehicle,
            'interventions' => $interventions
        ]);
    }

    /**
     * Supprime un véhicule
     */
    public function delete($id)
    {
        // Vérifier que l'ID est valide
        if (!is_numeric($id) || $id <= 0) {
            header('Location: /vehicles?error=invalid_id');
            exit;
        }

        // Vérifier que le véhicule existe
        $vehicle = $this->vehicleRepository->findById($id);
        if (!$vehicle) {
            header('Location: /vehicles?error=not_found');
            exit;
        }

        // Supprimer le véhicule
        $success = $this->vehicleRepository->delete($id);
        
        if ($success) {
            header('Location: /vehicles?success=deleted');
        } else {
            header('Location: /vehicles?error=delete_failed');
        }
        exit;
    }
} 