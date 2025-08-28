<?php

namespace App\Controller;

use App\Service\TwigService;
use App\Service\SessionManager;
use App\Repository\UserRepository;

class ProfileController
{
    private TwigService $twig;
    private UserRepository $userRepository;

    public function __construct(TwigService $twig, UserRepository $userRepository)
    {
        $this->twig = $twig;
        $this->userRepository = $userRepository;
    }

    public function index()
    {
        SessionManager::requireLogin();
        
        // Récupérer les informations de l'utilisateur connecté
        // En réalité, on récupérerait l'ID depuis la session
        $userId = 1;
        
        // Données du profil utilisateur
        $user = [
            'id' => $userId,
            'name' => 'Thomas Martin',
            'email' => 'thomas.martin@terraintrack.com',
            'phone' => '+33 6 11 22 33 44',
            'role' => 'Administrateur Système',
            'department' => 'IT',
            'location' => 'Lyon, France',
            'joined_date' => '2023-01-15',
            'initials' => 'TM',
            'avatar_url' => 'https://randomuser.me/api/portraits/men/32.jpg',
            'bio' => 'Administrateur système passionné par l\'optimisation des processus et la gestion de flotte. Expert en solutions logistiques.',
            'status' => 'active',
            'last_active' => date('Y-m-d H:i:s', strtotime('-15 minutes')),
            'timezone' => 'Europe/Paris',
            'language' => 'Français',
            'notifications_enabled' => true,
            'two_factor_enabled' => false
        ];

        // Statistiques de l'utilisateur
        $stats = [
            'interventions_completed' => 47,
            'vehicles_managed' => 12,
            'teams_led' => 3,
            'reports_generated' => 89,
            'hours_logged' => 284,
            'success_rate' => 96
        ];

        // Activité récente
        $recentActivity = [
            [
                'id' => 1,
                'type' => 'intervention',
                'title' => 'Maintenance préventive terminée',
                'description' => 'Intervention sur Quad Explorer X450',
                'date' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'icon' => 'fa-wrench',
                'color' => 'success'
            ],
            [
                'id' => 2,
                'type' => 'vehicle',
                'title' => 'Nouveau véhicule ajouté',
                'description' => 'Heavy Duty Tractor T-800 ajouté à la flotte',
                'date' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'icon' => 'fa-plus-circle',
                'color' => 'info'
            ],
            [
                'id' => 3,
                'type' => 'team',
                'title' => 'Équipe Delta mise à jour',
                'description' => '2 nouveaux membres ajoutés',
                'date' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'icon' => 'fa-users',
                'color' => 'primary'
            ],
            [
                'id' => 4,
                'type' => 'report',
                'title' => 'Rapport mensuel généré',
                'description' => 'Rapport d\'activité de décembre 2024',
                'date' => date('Y-m-d H:i:s', strtotime('-1 week')),
                'icon' => 'fa-chart-bar',
                'color' => 'warning'
            ]
        ];

        // Compétences et certifications
        $skills = [
            [
                'name' => 'Gestion de Flotte',
                'level' => 95,
                'color' => 'success'
            ],
            [
                'name' => 'Maintenance Véhicules',
                'level' => 87,
                'color' => 'info'
            ],
            [
                'name' => 'Planification',
                'level' => 92,
                'color' => 'primary'
            ],
            [
                'name' => 'Leadership',
                'level' => 89,
                'color' => 'warning'
            ]
        ];

        $user = $this->userRepository->getCurrentUser();
        
        // Suppression de l'appel à addGlobalTranslations()
        return $this->twig->render('profile.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * Met à jour le profil via AJAX
     */
    public function updateProfile()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        // Récupérer les données POST
        $data = [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'bio' => $_POST['bio'] ?? '',
            'location' => $_POST['location'] ?? '',
            'timezone' => $_POST['timezone'] ?? 'Europe/Paris',
            'language' => $_POST['language'] ?? 'Français'
        ];

        // Validation basique
        if (empty($data['name']) || empty($data['email'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Le nom et l\'email sont obligatoires']);
            return;
        }

        // Simulation de la mise à jour (en réalité, on utiliserait une base de données)
        // $success = $this->userRepository->updateProfile($userId, $data);

        echo json_encode([
            'success' => true,
            'message' => 'Profil mis à jour avec succès',
            'user' => $data
        ]);
    }
} 