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
        $sessionUser = SessionManager::getCurrentUser();
        if (!$sessionUser) {
            throw new \Exception('Utilisateur non connecté');
        }
        
        $userEntity = $this->userRepository->findById($sessionUser['id']);
        if (!$userEntity) {
            throw new \Exception('Utilisateur non trouvé');
        }
        
        // Données du profil utilisateur
        $user = [
            'id' => $userEntity->getId(),
            'name' => $userEntity->getName(),
            'email' => $userEntity->getEmail(),
            'phone' => $userEntity->getPhone(),
            'role' => $userEntity->getRole() ?: 'Administrateur',
            'department' => $userEntity->getDepartment(),
            'location' => $userEntity->getLocation(),
            'joined_date' => $userEntity->getCreatedAt()->format('Y-m-d'),
            'initials' => $this->generateInitials($userEntity->getName() ?? 'Utilisateur'),
            'avatar_url' => $userEntity->getAvatar(),
            'bio' => $userEntity->getBio(),
            'status' => 'active',
            'last_active' => date('Y-m-d H:i:s', strtotime('-15 minutes')),
            'timezone' => $userEntity->getTimezone() ?: 'Europe/Paris',
            'language' => $userEntity->getLanguage() ?: 'Français',
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

        // Le code de récupération utilisateur est déjà fait au-dessus, pas besoin de le dupliquer
        
        // Suppression de l'appel à addGlobalTranslations()
        return $this->twig->render('profile.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * Met à jour le profil via AJAX - rediriger vers la vraie méthode update
     */
    public function updateProfile()
    {
        return $this->update();
    }
    
    /**
     * Génère les initiales à partir du nom
     */
    private function generateInitials(string $name): string
    {
        $words = explode(' ', trim($name));
        $initials = '';
        
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
                if (strlen($initials) >= 2) {
                    break;
                }
            }
        }
        
        return $initials ?: 'U';
    }

    public function update()
    {
        SessionManager::requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }
        
        try {
            $sessionUser = SessionManager::getCurrentUser();
            if (!$sessionUser) {
                throw new \Exception('Utilisateur non connecté');
            }
            
            $userEntity = $this->userRepository->findById($sessionUser['id']);
            if (!$userEntity) {
                throw new \Exception('Utilisateur non trouvé');
            }
            
            // Traitement de l'upload de photo de profil
            $avatarUrl = null;
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $avatarUrl = $this->handleAvatarUpload($_FILES['avatar']);
            }
            
            // Mise à jour des données utilisateur
            $updateData = [
                'name' => $_POST['name'] ?? $userEntity->getName(),
                'email' => $_POST['email'] ?? $userEntity->getEmail(),
                'phone' => $_POST['phone'] ?? $userEntity->getPhone(),
                'location' => $_POST['location'] ?? $userEntity->getLocation(),
                'timezone' => $_POST['timezone'] ?? $userEntity->getTimezone(),
                'language' => $_POST['language'] ?? $userEntity->getLanguage(),
                'bio' => $_POST['bio'] ?? $userEntity->getBio(),
                'department' => $_POST['department'] ?? $userEntity->getDepartment(),
                'role' => $_POST['role'] ?? $userEntity->getRole()
            ];
            
            if ($avatarUrl) {
                $updateData['avatar'] = $avatarUrl;
            }
            
            // Mise à jour en base de données
            $success = $this->userRepository->update($userEntity->getId(), $updateData);
            
            if (!$success) {
                throw new \Exception('Erreur lors de la mise à jour en base de données');
            }
            
            // Récupérer les données mises à jour
            $updatedUser = $this->userRepository->findById($userEntity->getId());
            
            if (!$updatedUser) {
                throw new \Exception('Impossible de récupérer les données mises à jour');
            }
            
            // Mettre à jour la session utilisateur avec les nouvelles informations
            SessionManager::updateUserData([
                'id' => $updatedUser->getId(),
                'name' => $updatedUser->getName(),
                'email' => $updatedUser->getEmail(),
                'role' => $updatedUser->getRole() ?: 'Administrateur'
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Profil mis à jour avec succès',
                'user' => [
                    'id' => $updatedUser->getId(),
                    'name' => $updatedUser->getName(),
                    'email' => $updatedUser->getEmail(),
                    'phone' => $updatedUser->getPhone(),
                    'location' => $updatedUser->getLocation(),
                    'timezone' => $updatedUser->getTimezone(),
                    'language' => $updatedUser->getLanguage(),
                    'bio' => $updatedUser->getBio(),
                    'department' => $updatedUser->getDepartment(),
                    'role' => $updatedUser->getRole(),
                    'avatar_url' => $updatedUser->getAvatar(),
                    'initials' => $this->generateInitials($updatedUser->getName() ?? 'Utilisateur')
                ]
            ]);
            
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    private function handleAvatarUpload(array $file): string
    {
        // Vérifications de sécurité
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new \Exception('Format de fichier non supporté. Utilisez JPG ou PNG.');
        }
        
        if ($file['size'] > $maxSize) {
            throw new \Exception('Le fichier est trop volumineux. Taille maximale : 5MB');
        }
        
        // Créer le dossier d'upload s'il n'existe pas
        $uploadDir = __DIR__ . '/../../public/uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Générer un nom de fichier unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('avatar_', true) . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Déplacer le fichier
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new \Exception('Erreur lors de l\'upload du fichier');
        }
        
        // Retourner l'URL relative
        return '/uploads/avatars/' . $filename;
    }
    
} 