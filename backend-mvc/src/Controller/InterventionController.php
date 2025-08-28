<?php

namespace App\Controller;

use App\Entity\Intervention;
use App\Repository\InterventionRepository;
use App\Repository\VehicleRepository;
use App\Repository\TechnicianRepository;
use App\Service\UploadService;
use App\Service\TwigService;
use App\Service\SessionManager;
use App\Service\NotificationService;
use App\Middleware\AuthorizationMiddleware;

class InterventionController
{
    private TwigService $twig;
    private InterventionRepository $interventionRepository;
    private VehicleRepository $vehicleRepository;
    private TechnicianRepository $technicianRepository;
    private NotificationService $notificationService;
    private AuthorizationMiddleware $auth;
    private $uploadService;

    public function __construct(
        TwigService $twig,
        InterventionRepository $interventionRepository,
        VehicleRepository $vehicleRepository,
        TechnicianRepository $technicianRepository,
        NotificationService $notificationService,
        AuthorizationMiddleware $auth
    ) {
        //die('Dans le constructeur');
        $this->twig = $twig;
        $this->interventionRepository = $interventionRepository;
        $this->vehicleRepository = $vehicleRepository;
        $this->technicianRepository = $technicianRepository;
        $this->notificationService = $notificationService;
        $this->auth = $auth;
        $this->uploadService = new UploadService();
    }

    // Liste des interventions
    public function list(): string
    {
        // Restaurer la vérification des permissions
        $this->auth->requirePermission('interventions.read');
        
        //echo "Début méthode list"; exit;
        try {
            SessionManager::requireLogin();
            $status = $_GET['status'] ?? null;
            $priority = $_GET['priority'] ?? null;
            $type = $_GET['type'] ?? null;
            $sort = $_GET['sort'] ?? null;
            $interventions = $this->interventionRepository->findAllFiltered($status, $priority, $type, $sort);
            
            // Gestion des messages de succès spécifiques
            $successMessage = null;
            if (isset($_GET['success'])) {
                if ($_GET['success'] === 'deleted') {
                    $successMessage = 'deleted';
                } elseif ($_GET['success'] === '1') {
                    $successMessage = 'added';
                }
            }
            
            //die('Juste avant Twig');
            return $this->twig->render('intervention_list.html.twig', [
                'interventions' => $interventions,
                'success_message' => $successMessage,
                'selected_status' => $status,
                'selected_priority' => $priority,
                'selected_type' => $type,
                'selected_sort' => $sort
            ]);
        } catch (\Throwable $e) {
            echo '<pre style="color:red">';
            echo "Erreur : " . $e->getMessage() . "\n";
            echo $e->getTraceAsString();
            echo '</pre>';
            exit;
        }
    }

    // Formulaire pour créer une intervention
    public function create(): string
    {
        $this->auth->requirePermission('interventions.create');
        SessionManager::requireLogin();
        $userRole = $_SESSION['user']['role'] ?? '';
        // Temporairement commenté pour les tests
        /*
        if (!in_array($userRole, ['Responsable', 'Chef d'équipe'])) {
            // Rediriger ou afficher un message d'accès refusé
            return $this->twig->render('access_denied.html.twig', [
                'message' => "Vous n'avez pas l'autorisation de créer une intervention."
            ]);
        }
        */
        try {
            $vehicles = $this->vehicleRepository->findAvailableVehicles();
            $technicians = $this->technicianRepository->findAllActive(); // Tous les techniciens pour intervention générale
            $types = [
                'maintenance' => 'Maintenance',
                'repair' => 'Réparation',
                'emergency' => 'Urgence',
                'inspection' => 'Inspection'
            ];
            return $this->twig->render('intervention_create.html.twig', [
                'vehicles' => $vehicles,
                'technicians' => $technicians,
                'types' => $types
            ]);
        } catch (\Exception $e) {
            die("Erreur dans InterventionController::create : " . $e->getMessage());
        }
    }

    // Traitement de l'envoi du formulaire
    public function store()
    {
        $this->auth->requirePermission('interventions.create');
        SessionManager::requireLogin();
        $userRole = $_SESSION['user']['role'] ?? '';
        // Temporairement commenté pour les tests
        /*
        if (!in_array($userRole, ['Responsable', 'Chef d'équipe'])) {
            // Refuser la création
            header('Location: /intervention/list?error=forbidden');
            exit;
        }
        */
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $vehicleId = isset($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : null;
                
                // Vérifier que le véhicule est disponible
                if ($vehicleId) {
                    $availableVehicles = $this->vehicleRepository->findAvailableVehicles();
                    $vehicleAvailable = false;
                    foreach ($availableVehicles as $vehicle) {
                        if ($vehicle['id'] == $vehicleId) {
                            $vehicleAvailable = true;
                            break;
                        }
                    }
                    
                    if (!$vehicleAvailable) {
                        // Rediriger avec message d'erreur
                        header('Location: /intervention/create?error=vehicle_not_available');
                        exit;
                    }
                }
                
                // Gérer les techniciens (array de checkboxes ou texte libre)
                $techniciens = [];
                if (isset($_POST['technicians']) && is_array($_POST['technicians'])) {
                    // Techniciens sélectionnés via checkboxes
                    $techniciens = $_POST['technicians'];
                } elseif (isset($_POST['technicien']) && !empty($_POST['technicien'])) {
                    // Texte libre (mode compatible)
                    $techniciens = array_map('trim', explode(',', $_POST['technicien']));
                }
                $technicienString = implode(', ', $techniciens);
                
                $description = $_POST['description'] ?? '';
                $latitude = $_POST['latitude'] ?? 0;
                $longitude = $_POST['longitude'] ?? 0;

                $photoPath = null;
                if (isset($_FILES['photo']) && is_array($_FILES['photo'])) {
                    $photoPath = $this->uploadService->handleUpload($_FILES['photo']);
                }

                $intervention = new Intervention();
                $intervention->setTechnicien($technicienString);
                $intervention->setDescription($description);
                $intervention->setLatitude((float)$latitude);
                $intervention->setLongitude((float)$longitude);
                $intervention->setPhoto($photoPath ?? '');
                $intervention->setCreatedAt(date('Y-m-d H:i:s'));
                $intervention->setVehicleId($vehicleId);
                $intervention->setType($_POST['type'] ?? null);
                $intervention->setPriority($_POST['priority'] ?? null);
                
                // Gérer scheduled_date correctement pour éviter les valeurs vides
                $scheduledDate = $_POST['scheduled_date'] ?? '';
                $intervention->setScheduledDate(!empty($scheduledDate) ? $scheduledDate : null);
                
                $intervention->setStatus('pending');
                $intervention->setTitle($_POST['title'] ?? null);

                $this->interventionRepository->save($intervention);

                // Envoyer une notification de création d'intervention
                $title = $intervention->getTitle() ?? 'Nouvelle intervention';
                $this->notificationService->sendInterventionNotification(
                    $intervention->getId(),
                    $title,
                    "Nouvelle intervention créée",
                    "Une nouvelle intervention \"$title\" a été créée",
                    'info'
                );

                header('Location: /intervention/list?success=1');
                exit;
            }
        } catch (\Exception $e) {
            die("Erreur dans InterventionController::store : " . $e->getMessage());
        }
    }

    public function getAll(): array
    {
        try {
            $interventions = $this->interventionRepository->findAll();
            return array_map(function ($i) {
                return [
                    'id' => $i->getId(),
                    'technicien' => $i->getTechnicien(),
                    'description' => $i->getDescription(),
                    'latitude' => $i->getLatitude(),
                    'longitude' => $i->getLongitude(),
                    'photo' => $i->getPhoto(),
                    'created_at' => $i->getCreatedAt(),
                ];
            }, $interventions);
        } catch (\Exception $e) {
            die("Erreur dans InterventionController::getAll : " . $e->getMessage());
        }
    }

    // Affichage détaillé d'une intervention
    public function show($id)
    {
        $this->auth->requirePermission('interventions.read');
        try {
            $intervention = $this->interventionRepository->findById($id);
            if (!$intervention) {
                header('HTTP/1.0 404 Not Found');
                echo 'Intervention non trouvée';
                exit;
            }
            // Récupérer infos véhicule
            $vehicleType = null;
            $vehicleStatus = null;
            if (!empty($intervention['vehicle_id'])) {
                $vehicleRepo = $this->vehicleRepository;
                $vehicle = $vehicleRepo->findById($intervention['vehicle_id']);
                if ($vehicle) {
                    $vehicleType = $vehicle['type'] ?? null;
                    $vehicleStatus = $vehicle['status'] ?? null;
                }
            }

            // Récupérer tous les véhicules disponibles pour l'assignation
            $allVehicles = $this->vehicleRepository->findAll();
            $availableVehicles = [];
            foreach ($allVehicles as $vehicle) {
                $availableVehicles[] = [
                    'id' => $vehicle['id'],
                    'name' => $vehicle['name'],
                    'type' => $vehicle['type'] ?? 'N/A',
                    'status' => $vehicle['status'] ?? 'N/A',
                    'is_assigned' => $vehicle['id'] == $intervention['vehicle_id']
                ];
            }

            // Techniciens disponibles (à adapter selon ton modèle réel)
            $allTechs = [
                'Jean Leclerc',
                'Marie Petit',
                'Pierre Moreau',
                'Lucas Rousseau',
            ];
            // Techniciens assignés (depuis la base)
            $assignedTechs = [];
            if (!empty($intervention['technicien'])) {
                $assignedTechs = array_filter(array_map(function($t) {
                    return trim($t);
                }, explode(',', $intervention['technicien'])));
            }
            // Nettoyage des noms assignés (remplace \u0020 par espace, trim)
            $assignedTechs = array_map(function($t) {
                return trim(str_replace('\\u0020', ' ', $t));
            }, $assignedTechs);
            // Fusionne les deux listes pour ne rien perdre, en nettoyant aussi allTechs
            $allNames = array_unique(array_merge(
                array_map(function($t) { return trim(str_replace('\\u0020', ' ', $t)); }, $allTechs),
                $assignedTechs
            ));
            $assignedTechsLower = array_map('mb_strtolower', $assignedTechs);
            $technicians = [];
            foreach ($allNames as $techName) {
                $isAssigned = in_array(mb_strtolower($techName), $assignedTechsLower, true);
                $technicians[] = [
                    'name' => $techName,
                    'assigned' => $isAssigned
                ];
            }
            // Debug temporaire :
            // error_log('TECHS DB: ' . $intervention['technicien'] . ' | ARRAY: ' . json_encode($assignedTechs));
            return $this->twig->render('intervention_show.html.twig', [
                'intervention' => $intervention,
                'vehicle_type' => $vehicleType,
                'vehicle_status' => $vehicleStatus,
                'technicians' => $technicians,
                'available_vehicles' => $availableVehicles
            ]);
        } catch (\Exception $e) {
            die("Erreur dans InterventionController::show : " . $e->getMessage());
        }
    }

    // Mise à jour du statut d'une intervention
    public function updateStatus()
    {
        $this->auth->requirePermission('interventions.update');
        try {
            // Vérifier que c'est bien une requête POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Méthode non autorisée']);
                return;
            }

            // Récupérer et décoder les données JSON
            $rawData = file_get_contents('php://input');
            $data = json_decode($rawData, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['error' => 'Données JSON invalides']);
                return;
            }

            // Vérifier les données requises
            if (!isset($data['id']) || !isset($data['status'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID et status requis']);
                return;
            }

            $id = (int)$data['id'];
            $status = $data['status'];

            // Valider le statut
            $validStatuses = ['pending', 'in-progress', 'done', 'cancelled'];
            if (!in_array($status, $validStatuses)) {
                http_response_code(400);
                echo json_encode(['error' => 'Statut invalide']);
                return;
            }

            // Vérifier que l'intervention existe
            $intervention = $this->interventionRepository->findById($id);
            if (!$intervention) {
                http_response_code(404);
                echo json_encode(['error' => 'Intervention non trouvée']);
                return;
            }

            // Mettre à jour le statut
            $success = $this->interventionRepository->updateStatus($id, $status);
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Statut mis à jour avec succès',
                    'status' => $status
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erreur lors de la mise à jour du statut']);
            }
        } catch (\Exception $e) {
            error_log("Erreur dans updateStatus : " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erreur serveur lors de la mise à jour']);
        }
    }

    /**
     * Mise à jour des techniciens assignés à une intervention
     */
    public function updateTechnicians()
    {
        $this->auth->requirePermission('interventions.update');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;
            $technicians = $input['technicians'] ?? [];

            if (!$id) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'ID de l\'intervention manquant']);
                return;
            }

            // Convertir le tableau de techniciens en chaîne séparée par des virgules
            $technicianString = implode(', ', $technicians);

            $result = $this->interventionRepository->updateTechnicians($id, $technicianString);

            header('Content-Type: application/json');
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Échec de la mise à jour']);
            }
        } catch (\Exception $e) {
            error_log("Erreur dans updateTechnicians: " . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Mise à jour du véhicule assigné à une intervention
     */
    public function updateVehicle()
    {
        $this->auth->requirePermission('interventions.update');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;
            $vehicleId = $input['vehicle_id'] ?? null;

            if (!$id) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'ID de l\'intervention manquant']);
                return;
            }

            // Convertir vehicleId en entier ou null
            $vehicleId = $vehicleId ? (int)$vehicleId : null;

            $result = $this->interventionRepository->updateVehicle($id, $vehicleId);

            header('Content-Type: application/json');
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Échec de la mise à jour du véhicule']);
            }
        } catch (\Exception $e) {
            error_log("Erreur dans updateVehicle: " . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Mise à jour du titre d'une intervention
     */
    public function updateTitle()
    {
        $this->auth->requirePermission('interventions.update');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;
            $title = $input['title'] ?? null;

            if (!$id) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'ID de l\'intervention manquant']);
                return;
            }

            if (!$title || trim($title) === '') {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Le titre ne peut pas être vide']);
                return;
            }

            $title = trim($title);
            $result = $this->interventionRepository->updateTitle($id, $title);

            header('Content-Type: application/json');
            if ($result) {
                echo json_encode(['success' => true, 'title' => $title]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Échec de la mise à jour du titre']);
            }
        } catch (\Exception $e) {
            error_log("Erreur dans updateTitle: " . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Mise à jour de la description d'une intervention
     */
    public function updateDescription()
    {
        $this->auth->requirePermission('interventions.update');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;
            $description = $input['description'] ?? null;

            if (!$id) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'ID de l\'intervention manquant']);
                return;
            }

            if (!$description || trim($description) === '') {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'La description ne peut pas être vide']);
                return;
            }

            $description = trim($description);
            $result = $this->interventionRepository->updateDescription($id, $description);

            header('Content-Type: application/json');
            if ($result) {
                echo json_encode(['success' => true, 'description' => $description]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Échec de la mise à jour de la description']);
            }
        } catch (\Exception $e) {
            error_log("Erreur dans updateDescription: " . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Supprime une intervention
     */
    public function delete($id)
    {
        $this->auth->requirePermission('interventions.delete');
        try {
            SessionManager::requireLogin();
            
            // Log de débogage
            error_log("🗑️ InterventionController::delete appelée avec ID: " . var_export($id, true));
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                error_log("❌ Méthode non autorisée: " . $_SERVER['REQUEST_METHOD']);
                if ($this->isAjaxRequest()) {
                    http_response_code(405);
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'Méthode non autorisée']);
                    return;
                }
                header('Location: /intervention/list?error=method_not_allowed');
                exit;
            }

            // Vérifier que l'ID est valide avec une validation plus stricte
            if ($id === null || $id === '' || $id === 'null' || $id === 'undefined') {
                error_log("❌ ID null ou vide détecté: " . var_export($id, true));
                if ($this->isAjaxRequest()) {
                    http_response_code(400);
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'ID manquant ou invalide']);
                    return;
                }
                header('Location: /intervention/list?error=invalid_id');
                exit;
            }
            
            $id = (int)$id;
            if ($id <= 0) {
                error_log("❌ ID invalide après conversion: " . $id);
                if ($this->isAjaxRequest()) {
                    http_response_code(400);
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'ID invalide (doit être un entier positif)']);
                    return;
                }
                header('Location: /intervention/list?error=invalid_id');
                exit;
            }

            error_log("✅ ID validé: " . $id);

            // Vérifier que l'intervention existe
            $intervention = $this->interventionRepository->findById($id);
            if (!$intervention) {
                error_log("❌ Intervention ID $id non trouvée dans la base");
                if ($this->isAjaxRequest()) {
                    http_response_code(404);
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'Intervention non trouvée']);
                    return;
                }
                header('Location: /intervention/list?error=not_found');
                exit;
            }

            error_log("✅ Intervention trouvée: " . $intervention['title']);

            // Supprimer l'intervention
            $result = $this->interventionRepository->delete($id);

            if ($result) {
                error_log("✅ Suppression réussie pour ID: " . $id);
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Intervention "' . $intervention['title'] . '" supprimée avec succès'
                    ]);
                    return;
                }
                header('Location: /intervention/list?success=deleted');
                exit;
            } else {
                error_log("❌ Échec de la suppression pour ID: " . $id);
                if ($this->isAjaxRequest()) {
                    http_response_code(500);
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'Échec de la suppression']);
                    return;
                }
                header('Location: /intervention/list?error=delete_failed');
                exit;
            }
        } catch (\Exception $e) {
            error_log("❌ Exception dans InterventionController::delete : " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            if ($this->isAjaxRequest()) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
                return;
            }
            header('Location: /intervention/list?error=server_error');
            exit;
        }
    }
    
    /**
     * Vérifie si la requête est une requête AJAX
     */
    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /**
     * Formulaire pour créer une intervention pour l'équipe Alpha
     */
    public function createForAlpha(): string
    {
        try {
            SessionManager::requireLogin();
            $vehicles = $this->vehicleRepository->findAvailableVehicles();
            $technicians = $this->technicianRepository->findAvailableForTeam(1); // Alpha = team 1
            $types = [
                'maintenance' => 'Maintenance',
                'repair' => 'Réparation',
                'emergency' => 'Urgence',
                'inspection' => 'Inspection'
            ];
            return $this->twig->render('intervention_create.html.twig', [
                'vehicles' => $vehicles,
                'technicians' => $technicians,
                'types' => $types,
                'team' => 'alpha',
                'team_id' => 1,
                'team_name' => 'Équipe Alpha',
                'form_action' => '/teams/alpha/intervention/store'
            ]);
        } catch (\Exception $e) {
            die("Erreur dans InterventionController::createForAlpha : " . $e->getMessage());
        }
    }

    /**
     * Formulaire pour créer une intervention pour l'équipe Beta
     */
    public function createForBeta(): string
    {
        try {
            SessionManager::requireLogin();
            $vehicles = $this->vehicleRepository->findAvailableVehicles();
            $technicians = $this->technicianRepository->findAvailableForTeam(2); // Beta = team 2
            $types = [
                'maintenance' => 'Maintenance',
                'repair' => 'Réparation',
                'emergency' => 'Urgence',
                'inspection' => 'Inspection'
            ];
            return $this->twig->render('intervention_create.html.twig', [
                'vehicles' => $vehicles,
                'technicians' => $technicians,
                'types' => $types,
                'team' => 'beta',
                'team_id' => 2,
                'team_name' => 'Équipe Beta',
                'form_action' => '/teams/beta/intervention/store'
            ]);
        } catch (\Exception $e) {
            die("Erreur dans InterventionController::createForBeta : " . $e->getMessage());
        }
    }

    /**
     * Formulaire pour créer une intervention pour l'équipe Gamma
     */
    public function createForGamma(): string
    {
        try {
            SessionManager::requireLogin();
            $vehicles = $this->vehicleRepository->findAvailableVehicles();
            $technicians = $this->technicianRepository->findAvailableForTeam(3); // Gamma = team 3
            $types = [
                'maintenance' => 'Maintenance',
                'repair' => 'Réparation',
                'emergency' => 'Urgence',
                'inspection' => 'Inspection'
            ];
            return $this->twig->render('intervention_create.html.twig', [
                'vehicles' => $vehicles,
                'technicians' => $technicians,
                'types' => $types,
                'team' => 'gamma',
                'team_id' => 3,
                'team_name' => 'Équipe Gamma',
                'form_action' => '/teams/gamma/intervention/store'
            ]);
        } catch (\Exception $e) {
            die("Erreur dans InterventionController::createForGamma : " . $e->getMessage());
        }
    }

    /**
     * Traitement de l'envoi du formulaire pour l'équipe Alpha
     */
    public function storeForAlpha()
    {
        try {
            SessionManager::requireLogin();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $vehicleId = isset($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : null;
                
                // Vérifier que le véhicule est disponible
                if ($vehicleId) {
                    $availableVehicles = $this->vehicleRepository->findAvailableVehicles();
                    $vehicleAvailable = false;
                    foreach ($availableVehicles as $vehicle) {
                        if ($vehicle['id'] == $vehicleId) {
                            $vehicleAvailable = true;
                            break;
                        }
                    }
                    
                    if (!$vehicleAvailable) {
                        header('Location: /teams/alpha/intervention/create?error=vehicle_not_available');
                        exit;
                    }
                }
                
                // Gérer les techniciens (array de checkboxes ou texte libre)
                $techniciens = [];
                if (isset($_POST['technicians']) && is_array($_POST['technicians'])) {
                    // Techniciens sélectionnés via checkboxes
                    $techniciens = $_POST['technicians'];
                } elseif (isset($_POST['technicien']) && !empty($_POST['technicien'])) {
                    // Texte libre (mode compatible)
                    $techniciens = array_map('trim', explode(',', $_POST['technicien']));
                }
                $technicienString = implode(', ', $techniciens);
                
                $description = $_POST['description'] ?? '';
                $latitude = $_POST['latitude'] ?? 0;
                $longitude = $_POST['longitude'] ?? 0;

                $photoPath = null;
                if (isset($_FILES['photo']) && is_array($_FILES['photo'])) {
                    $photoPath = $this->uploadService->handleUpload($_FILES['photo']);
                }

                $intervention = new Intervention();
                $intervention->setTechnicien($technicienString);
                $intervention->setDescription($description);
                $intervention->setLatitude((float)$latitude);
                $intervention->setLongitude((float)$longitude);
                $intervention->setPhoto($photoPath ?? '');
                $intervention->setCreatedAt(date('Y-m-d H:i:s'));
                $intervention->setVehicleId($vehicleId);
                $intervention->setType($_POST['type'] ?? null);
                $intervention->setPriority($_POST['priority'] ?? null);
                
                // Gérer scheduled_date correctement pour éviter les valeurs vides
                $scheduledDate = $_POST['scheduled_date'] ?? '';
                $intervention->setScheduledDate(!empty($scheduledDate) ? $scheduledDate : null);
                
                $intervention->setStatus('pending');
                $intervention->setTitle($_POST['title'] ?? null);
                $intervention->setTeam('alpha'); // Spécifier l'équipe

                $this->interventionRepository->save($intervention);

                // Envoyer une notification de création d'intervention pour l'équipe Alpha
                $title = $intervention->getTitle() ?? 'Nouvelle intervention';
                $this->notificationService->sendInterventionNotification(
                    $intervention->getId(),
                    $title,
                    "Nouvelle intervention pour l'équipe Alpha",
                    "Une nouvelle intervention \"$title\" a été créée et assignée à l'équipe Alpha",
                    'info'
                );

                header('Location: /teams/alpha?success=intervention_created');
                exit;
            }
        } catch (\Exception $e) {
            die("Erreur dans InterventionController::storeForAlpha : " . $e->getMessage());
        }
    }

    /**
     * Traitement de l'envoi du formulaire pour l'équipe Beta
     */
    public function storeForBeta()
    {
        try {
            SessionManager::requireLogin();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $vehicleId = isset($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : null;
                
                // Vérifier que le véhicule est disponible
                if ($vehicleId) {
                    $availableVehicles = $this->vehicleRepository->findAvailableVehicles();
                    $vehicleAvailable = false;
                    foreach ($availableVehicles as $vehicle) {
                        if ($vehicle['id'] == $vehicleId) {
                            $vehicleAvailable = true;
                            break;
                        }
                    }
                    
                    if (!$vehicleAvailable) {
                        header('Location: /teams/beta/intervention/create?error=vehicle_not_available');
                        exit;
                    }
                }
                
                // Gérer les techniciens (array de checkboxes ou texte libre)
                $techniciens = [];
                if (isset($_POST['technicians']) && is_array($_POST['technicians'])) {
                    // Techniciens sélectionnés via checkboxes
                    $techniciens = $_POST['technicians'];
                } elseif (isset($_POST['technicien']) && !empty($_POST['technicien'])) {
                    // Texte libre (mode compatible)
                    $techniciens = array_map('trim', explode(',', $_POST['technicien']));
                }
                $technicienString = implode(', ', $techniciens);
                
                $description = $_POST['description'] ?? '';
                $latitude = $_POST['latitude'] ?? 0;
                $longitude = $_POST['longitude'] ?? 0;

                $photoPath = null;
                if (isset($_FILES['photo']) && is_array($_FILES['photo'])) {
                    $photoPath = $this->uploadService->handleUpload($_FILES['photo']);
                }

                $intervention = new Intervention();
                $intervention->setTechnicien($technicienString);
                $intervention->setDescription($description);
                $intervention->setLatitude((float)$latitude);
                $intervention->setLongitude((float)$longitude);
                $intervention->setPhoto($photoPath ?? '');
                $intervention->setCreatedAt(date('Y-m-d H:i:s'));
                $intervention->setVehicleId($vehicleId);
                $intervention->setType($_POST['type'] ?? null);
                $intervention->setPriority($_POST['priority'] ?? null);
                
                // Gérer scheduled_date correctement pour éviter les valeurs vides
                $scheduledDate = $_POST['scheduled_date'] ?? '';
                $intervention->setScheduledDate(!empty($scheduledDate) ? $scheduledDate : null);
                
                $intervention->setStatus('pending');
                $intervention->setTitle($_POST['title'] ?? null);
                $intervention->setTeam('beta'); // Spécifier l'équipe

                $this->interventionRepository->save($intervention);

                // Envoyer une notification de création d'intervention pour l'équipe Beta
                $title = $intervention->getTitle() ?? 'Nouvelle intervention';
                $this->notificationService->sendInterventionNotification(
                    $intervention->getId(),
                    $title,
                    "Nouvelle intervention pour l'équipe Beta",
                    "Une nouvelle intervention \"$title\" a été créée et assignée à l'équipe Beta",
                    'info'
                );

                header('Location: /teams/beta?success=intervention_created');
                exit;
            }
        } catch (\Exception $e) {
            die("Erreur dans InterventionController::storeForBeta : " . $e->getMessage());
        }
    }

    /**
     * Traitement de l'envoi du formulaire pour l'équipe Gamma
     */
    public function storeForGamma()
    {
        try {
            SessionManager::requireLogin();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $vehicleId = isset($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : null;
                
                // Vérifier que le véhicule est disponible
                if ($vehicleId) {
                    $availableVehicles = $this->vehicleRepository->findAvailableVehicles();
                    $vehicleAvailable = false;
                    foreach ($availableVehicles as $vehicle) {
                        if ($vehicle['id'] == $vehicleId) {
                            $vehicleAvailable = true;
                            break;
                        }
                    }
                    
                    if (!$vehicleAvailable) {
                        header('Location: /teams/gamma/intervention/create?error=vehicle_not_available');
                        exit;
                    }
                }
                
                // Gérer les techniciens (array de checkboxes ou texte libre)
                $techniciens = [];
                if (isset($_POST['technicians']) && is_array($_POST['technicians'])) {
                    // Techniciens sélectionnés via checkboxes
                    $techniciens = $_POST['technicians'];
                } elseif (isset($_POST['technicien']) && !empty($_POST['technicien'])) {
                    // Texte libre (mode compatible)
                    $techniciens = array_map('trim', explode(',', $_POST['technicien']));
                }
                $technicienString = implode(', ', $techniciens);
                
                $description = $_POST['description'] ?? '';
                $latitude = $_POST['latitude'] ?? 0;
                $longitude = $_POST['longitude'] ?? 0;

                $photoPath = null;
                if (isset($_FILES['photo']) && is_array($_FILES['photo'])) {
                    $photoPath = $this->uploadService->handleUpload($_FILES['photo']);
                }

                $intervention = new Intervention();
                $intervention->setTechnicien($technicienString);
                $intervention->setDescription($description);
                $intervention->setLatitude((float)$latitude);
                $intervention->setLongitude((float)$longitude);
                $intervention->setPhoto($photoPath ?? '');
                $intervention->setCreatedAt(date('Y-m-d H:i:s'));
                $intervention->setVehicleId($vehicleId);
                $intervention->setType($_POST['type'] ?? null);
                $intervention->setPriority($_POST['priority'] ?? null);
                
                // Gérer scheduled_date correctement pour éviter les valeurs vides
                $scheduledDate = $_POST['scheduled_date'] ?? '';
                $intervention->setScheduledDate(!empty($scheduledDate) ? $scheduledDate : null);
                
                $intervention->setStatus('pending');
                $intervention->setTitle($_POST['title'] ?? null);
                $intervention->setTeam('gamma'); // Spécifier l'équipe

                $this->interventionRepository->save($intervention);

                // Envoyer une notification de création d'intervention pour l'équipe Gamma
                $title = $intervention->getTitle() ?? 'Nouvelle intervention';
                $this->notificationService->sendInterventionNotification(
                    $intervention->getId(),
                    $title,
                    "Nouvelle intervention pour l'équipe Gamma",
                    "Une nouvelle intervention \"$title\" a été créée et assignée à l'équipe Gamma",
                    'info'
                );

                header('Location: /teams/gamma?success=intervention_created');
                exit;
            }
        } catch (\Exception $e) {
            die("Erreur dans InterventionController::storeForGamma : " . $e->getMessage());
        }
    }
}