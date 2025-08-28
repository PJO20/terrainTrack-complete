<?php

namespace App\Controller;

use App\Service\TwigService;
use App\Service\SessionManager;
use App\Repository\UserSettingsRepository;
use App\Repository\NotificationSettingsRepository;
use App\Repository\AppearanceSettingsRepository;

class SettingsController
{
    private TwigService $twig;
    private UserSettingsRepository $userSettingsRepository;
    private NotificationSettingsRepository $notificationSettingsRepository;
    private AppearanceSettingsRepository $appearanceSettingsRepository;

    public function __construct(
        TwigService $twig,
        UserSettingsRepository $userSettingsRepository,
        NotificationSettingsRepository $notificationSettingsRepository,
        AppearanceSettingsRepository $appearanceSettingsRepository
    ) {
        $this->twig = $twig;
        $this->userSettingsRepository = $userSettingsRepository;
        $this->notificationSettingsRepository = $notificationSettingsRepository;
        $this->appearanceSettingsRepository = $appearanceSettingsRepository;
    }

    public function index()
    {
        // Vérifier l'authentification et le timeout de session
        SessionManager::requireLogin();
        
        // Récupérer l'utilisateur actuel depuis la session
        $currentUser = SessionManager::getCurrentUser();
        
        if (!$currentUser) {
            header('Location: /login');
            exit;
        }
        
        // Vérifier si l'utilisateur peut accéder à la gestion des permissions
        $canAccessPermissions = $this->canUserAccessPermissions($currentUser);
        
        // Récupérer les données utilisateur depuis la base de données
        $userId = $currentUser['id'];
        $userSettings = $this->userSettingsRepository->findByUserId($userId);
        $notificationSettings = $this->notificationSettingsRepository->findByUserId($userId);
        $appearanceSettings = $this->appearanceSettingsRepository->findByUserId($userId);
        
        // Données utilisateur avec fallback si pas en base
        $user = [
            'id' => $currentUser['id'],
            'name' => $userSettings['full_name'] ?? $currentUser['email'],
            'email' => $userSettings['email'] ?? $currentUser['email'] ?? '',
            'phone' => $userSettings['phone'] ?? '',
            'role' => $userSettings['role'] ?? $currentUser['role'],
            'department' => $userSettings['department'] ?? '',
            'location' => $userSettings['location'] ?? '',
            'timezone' => $userSettings['timezone'] ?? 'Europe/Paris',
            'language' => $userSettings['language'] ?? 'fr',
            'initials' => $this->userSettingsRepository->generateInitials($userSettings['full_name'] ?? $currentUser['email']),
            'is_admin' => $currentUser['role'] === 'admin' || $currentUser['role'] === 'super_admin',
            'is_super_admin' => $currentUser['role'] === 'super_admin',
            'can_access_permissions' => $canAccessPermissions
        ];

        // Données notifications avec fallback si pas en base
        $notifications = [
            'email_notifications' => $notificationSettings['email_notifications'] ?? true,
            'push_notifications' => $notificationSettings['push_notifications'] ?? true,
            'sms_notifications' => $notificationSettings['sms_notifications'] ?? false,
            'desktop_notifications' => $notificationSettings['desktop_notifications'] ?? true,
            'sound_notifications' => $notificationSettings['sound_notifications'] ?? true,
            'vibration_notifications' => $notificationSettings['vibration_notifications'] ?? true,
            'vehicle_alerts' => $notificationSettings['vehicle_alerts'] ?? true,
            'maintenance_reminders' => $notificationSettings['maintenance_reminders'] ?? true,
            'intervention_updates' => $notificationSettings['intervention_updates'] ?? true,
            'team_notifications' => $notificationSettings['team_notifications'] ?? true,
            'system_alerts' => $notificationSettings['system_alerts'] ?? true,
            'report_generation' => $notificationSettings['report_generation'] ?? false,
            'notification_frequency' => $notificationSettings['notification_frequency'] ?? 'realtime',
            'quiet_hours_enabled' => $notificationSettings['quiet_hours_enabled'] ?? true,
            'quiet_hours_start' => $notificationSettings['quiet_hours_start'] ?? '22:00',
            'quiet_hours_end' => $notificationSettings['quiet_hours_end'] ?? '07:00'
        ];

        // Données apparence avec fallback si pas en base
        $appearance = [
            'theme' => $appearanceSettings['theme'] ?? 'light',
            'primary_color' => $appearanceSettings['primary_color'] ?? 'blue',
            'font_size' => $appearanceSettings['font_size'] ?? 'medium',
            'compact_mode' => $appearanceSettings['compact_mode'] ?? false,
            'animations_enabled' => $appearanceSettings['animations_enabled'] ?? true,
            'high_contrast' => $appearanceSettings['high_contrast'] ?? false,
            'reduced_motion' => $appearanceSettings['reduced_motion'] ?? false
        ];

        return $this->twig->render('settings.html.twig', [
            'title' => 'Settings',
            'user' => $user,
            'notifications' => $notifications,
            'appearance' => $appearance,
            'current_user' => $currentUser,
            'can_access_permissions' => $canAccessPermissions
        ]);
    }

    /**
     * Met à jour le profil utilisateur via AJAX
     */
    public function updateProfile()
    {
        // Vérifier que c'est une requête POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        // Récupérer les données POST
        $data = [
            'fullname' => $_POST['fullname'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'role' => $_POST['role'] ?? 'operator',
            'department' => $_POST['department'] ?? '',
            'location' => $_POST['location'] ?? '',
            'timezone' => $_POST['timezone'] ?? 'Europe/Paris',
            'language' => $_POST['language'] ?? 'fr'
        ];

        // Validation basique
        if (empty($data['fullname']) || empty($data['email'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Le nom complet et l\'email sont obligatoires']);
            return;
        }

        // Validation de l'email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Format d\'email invalide']);
            return;
        }

        // Mettre à jour en base de données
        $userId = 1; // En réalité, on récupérerait l'ID depuis la session
        $success = $this->userSettingsRepository->updateProfile($userId, $data);

        if ($success) {
            // Retourner les nouvelles données
            $updatedUser = $this->userSettingsRepository->findByUserId($userId);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Profil mis à jour avec succès',
                'user' => [
                    'name' => $updatedUser['full_name'],
                    'email' => $updatedUser['email'],
                    'phone' => $updatedUser['phone'],
                    'role' => $updatedUser['role'],
                    'department' => $updatedUser['department'],
                    'location' => $updatedUser['location'],
                    'timezone' => $updatedUser['timezone'],
                    'language' => $updatedUser['language'],
                    'initials' => $this->userSettingsRepository->generateInitials($updatedUser['full_name'])
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde']);
        }
    }

    /**
     * Met à jour les paramètres de notifications via AJAX
     */
    public function updateNotifications()
    {
        // Vérifier que c'est une requête POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        // Récupérer les données POST
        $data = $_POST;

        // Validation des heures silencieuses
        if (isset($data['quiet_hours_start']) && !preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['quiet_hours_start'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Format d\'heure de début invalide']);
            return;
        }

        if (isset($data['quiet_hours_end']) && !preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['quiet_hours_end'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Format d\'heure de fin invalide']);
            return;
        }

        // Convertir les heures au format HH:MM:SS
        if (isset($data['quiet_hours_start'])) {
            $data['quiet_hours_start'] = $data['quiet_hours_start'] . ':00';
        }
        if (isset($data['quiet_hours_end'])) {
            $data['quiet_hours_end'] = $data['quiet_hours_end'] . ':00';
        }

        // Mettre à jour en base de données
        $userId = 1; // En réalité, on récupérerait l'ID depuis la session
        $success = $this->notificationSettingsRepository->updateNotifications($userId, $data);

        if ($success) {
            // Retourner les nouvelles données
            $updatedNotifications = $this->notificationSettingsRepository->findByUserId($userId);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Paramètres de notifications mis à jour avec succès',
                'notifications' => $updatedNotifications
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde']);
        }
    }

    /**
     * Met à jour les paramètres d'apparence via AJAX
     */
    public function updateAppearance()
    {
        // Vérifier que c'est une requête POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        // Récupérer les données POST
        $data = $_POST;

        // Validation des couleurs autorisées
        $allowedColors = ['blue', 'green', 'purple', 'orange'];
        if (isset($data['primary_color']) && !in_array($data['primary_color'], $allowedColors)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Couleur non autorisée']);
            return;
        }

        // Validation des thèmes autorisés
        $allowedThemes = ['light', 'dark', 'auto'];
        if (isset($data['theme']) && !in_array($data['theme'], $allowedThemes)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Thème non autorisé']);
            return;
        }

        // Validation des tailles de police autorisées
        $allowedSizes = ['small', 'medium', 'large', 'extra-large'];
        if (isset($data['font_size']) && !in_array($data['font_size'], $allowedSizes)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Taille de police non autorisée']);
            return;
        }

        // Mettre à jour en base de données
        $userId = 1; // En réalité, on récupérerait l'ID depuis la session
        $success = $this->appearanceSettingsRepository->updateAppearance($userId, $data);

        if ($success) {
            // Retourner les nouvelles données
            $updatedAppearance = $this->appearanceSettingsRepository->findByUserId($userId);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Paramètres d\'apparence mis à jour avec succès',
                'appearance' => $updatedAppearance
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde']);
        }
    }

    /**
     * Change le mot de passe de l'utilisateur
     */
    public function changePassword()
    {
        // Vérifier que c'est une requête POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        // Récupérer les données POST
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validations de base
        if (empty($currentPassword)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Le mot de passe actuel est obligatoire']);
            return;
        }

        if (empty($newPassword)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Le nouveau mot de passe est obligatoire']);
            return;
        }

        if ($newPassword !== $confirmPassword) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'La confirmation du mot de passe ne correspond pas']);
            return;
        }

        // Validation de la complexité du mot de passe
        if (!$this->isPasswordStrong($newPassword)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Le nouveau mot de passe ne respecte pas les exigences de sécurité']);
            return;
        }

        // Vérifier que le nouveau mot de passe est différent de l'ancien
        if ($currentPassword === $newPassword) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Le nouveau mot de passe doit être différent de l\'ancien']);
            return;
        }

        // Récupérer l'utilisateur actuel (simulation - en réalité depuis la session)
        $userId = 1;
        $userRepo = new \App\Repository\UserRepository($this->userSettingsRepository->getConnection());
        $user = $userRepo->findById($userId);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
            return;
        }

        // Vérifier le mot de passe actuel
        if (!password_verify($currentPassword, $user->getPassword())) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Le mot de passe actuel est incorrect']);
            return;
        }

        // Hasher le nouveau mot de passe
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Mettre à jour le mot de passe en base
        $success = $userRepo->updatePassword($userId, $hashedPassword);

        if ($success) {
            // Log de sécurité
            error_log("Password changed successfully for user ID: $userId at " . date('Y-m-d H:i:s'));
            
            echo json_encode([
                'success' => true,
                'message' => 'Mot de passe changé avec succès'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du mot de passe']);
        }
    }

    /**
     * Valide la force d'un mot de passe
     */
    private function isPasswordStrong(string $password): bool
    {
        // Au moins 8 caractères
        if (strlen($password) < 8) {
            return false;
        }

        // Au moins une majuscule
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        // Au moins une minuscule
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        // Au moins un chiffre
        if (!preg_match('/\d/', $password)) {
            return false;
        }

        // Au moins un caractère spécial
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
            return false;
        }

        return true;
    }

    /**
     * Vérifie si l'utilisateur peut accéder à la gestion des permissions
     */
    private function canUserAccessPermissions($user): bool
    {
        if (!$user || !isset($user['role'])) {
            return false;
        }
        
        // Seuls les administrateurs et super administrateurs peuvent accéder
        return $user['role'] === 'admin' || $user['role'] === 'super_admin';
    }
} 