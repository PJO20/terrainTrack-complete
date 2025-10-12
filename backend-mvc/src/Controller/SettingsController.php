<?php

namespace App\Controller;

use App\Service\TwigService;
use App\Service\SessionManager;
use App\Service\AutoSaveService;
use App\Repository\UserRepository;
use App\Repository\UserSettingsRepository;
use App\Repository\NotificationSettingsRepository;
use App\Repository\AppearanceSettingsRepository;

class SettingsController
{
    private TwigService $twig;
    private UserRepository $userRepository;
    private UserSettingsRepository $userSettingsRepository;
    private NotificationSettingsRepository $notificationSettingsRepository;
    private AppearanceSettingsRepository $appearanceSettingsRepository;

    public function __construct(
        TwigService $twig,
        UserRepository $userRepository,
        UserSettingsRepository $userSettingsRepository,
        NotificationSettingsRepository $notificationSettingsRepository,
        AppearanceSettingsRepository $appearanceSettingsRepository
    ) {
        $this->twig = $twig;
        $this->userRepository = $userRepository;
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
        
        error_log("SettingsController: Utilisateur en session - ID: " . $currentUser['id'] . ", Email: " . $currentUser['email'] . ", Nom: " . ($currentUser['name'] ?? 'Non défini'));
        
        // Vérifier si l'utilisateur peut accéder à la gestion des permissions
        $canAccessPermissions = $this->canUserAccessPermissions($currentUser);
        
        // Récupérer les données utilisateur directement depuis la table users
        $userId = $currentUser['id'];
        $pdo = \App\Service\Database::connect();
        
        // Vérifier d'abord quelles colonnes existent
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        
        // Construire la requête SELECT selon les colonnes disponibles
        $selectColumns = "id, email";
        if (in_array('name', $columns)) {
            $selectColumns .= ", name";
        }
        if (in_array('phone', $columns)) {
            $selectColumns .= ", phone";
        }
        if (in_array('location', $columns)) {
            $selectColumns .= ", location";
        }
        if (in_array('department', $columns)) {
            $selectColumns .= ", department";
        }
        if (in_array('role', $columns)) {
            $selectColumns .= ", role";
        }
        if (in_array('timezone', $columns)) {
            $selectColumns .= ", timezone";
        }
        if (in_array('language', $columns)) {
            $selectColumns .= ", language";
        }
        if (in_array('avatar', $columns)) {
            $selectColumns .= ", avatar";
        }
        if (in_array('password_updated_at', $columns)) {
            $selectColumns .= ", password_updated_at";
        }
        if (in_array('session_timeout', $columns)) {
            $selectColumns .= ", session_timeout";
        }
        if (in_array('two_factor_enabled', $columns)) {
            $selectColumns .= ", two_factor_enabled";
        }
        if (in_array('two_factor_required', $columns)) {
            $selectColumns .= ", two_factor_required";
        }
        if (in_array('date_format', $columns)) {
            $selectColumns .= ", date_format";
        }
        if (in_array('time_format', $columns)) {
            $selectColumns .= ", time_format";
        }
        if (in_array('auto_save', $columns)) {
            $selectColumns .= ", auto_save";
        }
        
        // Récupérer les données utilisateur
        $stmt = $pdo->prepare("SELECT $selectColumns FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        error_log("SettingsController: Données récupérées de la base - " . print_r($userData, true));
        
        if (!$userData) {
            // Fallback sur les données de session si pas trouvé en base
            $userData = $currentUser;
            error_log("SettingsController: Utilisation des données de session comme fallback");
        }
        
        // Construire le nom complet - utiliser la colonne 'name' en priorité
        $fullName = '';
        if (isset($userData['name']) && !empty($userData['name'])) {
            $fullName = $userData['name'];
        } else {
            $fullName = $userData['email']; // Fallback sur l'email
        }
        
        // Générer les initiales
        $words = explode(' ', trim($fullName));
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
                if (strlen($initials) >= 2) {
                    break;
                }
            }
        }
        $initials = $initials ?: 'U';
        
        // Récupérer les autres paramètres (notifications, apparence)
        $notificationSettings = $this->notificationSettingsRepository->findByUserId($userId);
        $appearanceSettings = $this->appearanceSettingsRepository->findByUserId($userId);
        
        // Données utilisateur avec les vraies données de la base
        $user = [
            'id' => $userData['id'],
            'name' => $fullName,
            'email' => $userData['email'],
            'phone' => $userData['phone'] ?? '',
            'role' => $userData['role'] ?? $currentUser['role'],
            'department' => $userData['department'] ?? '',
            'location' => $userData['location'] ?? '',
            'timezone' => $userData['timezone'] ?? 'Europe/Paris',
            'language' => $userData['language'] ?? 'fr',
            'initials' => $initials,
            'avatar_url' => $userData['avatar'] ?? null,
            'is_admin' => ($userData['role'] ?? $currentUser['role']) === 'admin',
            'is_super_admin' => ($userData['role'] ?? $currentUser['role']) === 'super_admin',
            'can_access_permissions' => $canAccessPermissions,
            'password_updated_at' => $userData['password_updated_at'] ?? null,
            'date_format' => $userData['date_format'] ?? 'DD/MM/YYYY',
            'time_format' => $userData['time_format'] ?? '24',
            'auto_save' => $userData['auto_save'] ?? true
        ];
        
        error_log("SettingsController: Données utilisateur finales - " . print_r($user, true));

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
        // Déboggage
        error_log("updateProfile: Début de la méthode");
        
        try {
        // Vérifier que c'est une requête POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                error_log("updateProfile: Méthode non POST");
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

            // Récupérer l'utilisateur connecté
            $sessionUser = SessionManager::getCurrentUser();
            error_log("updateProfile: sessionUser = " . print_r($sessionUser, true));
            
            if (!$sessionUser || !isset($sessionUser['id'])) {
                error_log("updateProfile: Pas de session utilisateur");
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
                return;
            }
            
            $userId = $sessionUser['id'];
            error_log("updateProfile: userId = " . $userId);

            // Récupérer et valider les données POST
            $fullname = trim(isset($_POST['fullname']) ? $_POST['fullname'] : '');
            $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
            $phone = trim(isset($_POST['phone']) ? $_POST['phone'] : '');
            $location = trim(isset($_POST['location']) ? $_POST['location'] : '');
            $department = trim(isset($_POST['department']) ? $_POST['department'] : '');
            $role = trim(isset($_POST['role']) ? $_POST['role'] : '');
            $timezone = trim(isset($_POST['timezone']) ? $_POST['timezone'] : '');
            $language = trim(isset($_POST['language']) ? $_POST['language'] : '');
            
            // Traitement de l'upload de photo de profil
            $avatarUrl = null;
            $removeAvatar = isset($_POST['remove_avatar']) && $_POST['remove_avatar'] === '1';
            
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $avatarUrl = $this->handleAvatarUpload($_FILES['avatar']);
                error_log("updateProfile: Avatar uploadé avec succès: " . $avatarUrl);
            } elseif ($removeAvatar) {
                $avatarUrl = ''; // Supprimer l'avatar
                error_log("updateProfile: Suppression de l'avatar demandée");
            }
            
            error_log("updateProfile: fullname = " . $fullname . ", email = " . $email . ", phone = " . $phone . ", location = " . $location . ", department = " . $department . ", role = " . $role . ", timezone = " . $timezone . ", language = " . $language);

            if (empty($fullname) || empty($email)) {
                error_log("updateProfile: Données manquantes");
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Le nom complet et l\'email sont obligatoires']);
            return;
        }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                error_log("updateProfile: Email invalide");
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Format d\'email invalide']);
            return;
        }

            // Séparer le nom complet en prénom et nom
            $nameParts = explode(' ', $fullname, 2);
            $firstName = $nameParts[0];
            $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
            
            error_log("updateProfile: firstName = " . $firstName . ", lastName = " . $lastName);

            // Mettre à jour dans la base de données (en utilisant les colonnes qui existent)
            $pdo = \App\Service\Database::connect();
            error_log("updateProfile: Connexion BDD réussie");
            
            // Vérifier d'abord la structure de la table
            $stmt = $pdo->query("DESCRIBE users");
            $columns = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            error_log("updateProfile: Colonnes disponibles = " . implode(', ', $columns));
            
            // Utiliser la colonne 'name' qui existe dans la base de données
            $updateData = [];
            if (!empty($fullname)) {
                $updateData['name'] = $fullname;
            }
            if (!empty($email)) {
                $updateData['email'] = $email;
            }
            if ($avatarUrl !== null) {
                $updateData['avatar'] = $avatarUrl;
            }
            if (!empty($phone)) {
                $updateData['phone'] = $phone;
            }
            if (!empty($location)) {
                $updateData['location'] = $location;
            }
            if (!empty($department)) {
                $updateData['department'] = $department;
            }
            if (!empty($role)) {
                $updateData['role'] = $role;
            }
            if (!empty($timezone)) {
                $updateData['timezone'] = $timezone;
            }
            if (!empty($language)) {
                $updateData['language'] = $language;
            }
            
            // Utiliser UserRepository pour la mise à jour
            $success = $this->userRepository->update($userId, $updateData);
            
            error_log("updateProfile: Mise à jour BDD success = " . ($success ? 'true' : 'false'));

            if (!$success) {
                throw new \Exception('Erreur lors de la mise à jour en base de données');
            }

            // Récupérer les données mises à jour (avec colonnes dynamiques)
            $selectColumns = "id, email";
            if (in_array('name', $columns)) {
                $selectColumns .= ", name";
            }
            if (in_array('username', $columns)) {
                $selectColumns .= ", username";
            }
            if (in_array('first_name', $columns)) {
                $selectColumns .= ", first_name";
            }
            if (in_array('last_name', $columns)) {
                $selectColumns .= ", last_name";
            }
            if (in_array('avatar', $columns)) {
                $selectColumns .= ", avatar";
            }
            if (in_array('is_admin', $columns)) {
                $selectColumns .= ", is_admin";
            }
            if (in_array('phone', $columns)) {
                $selectColumns .= ", phone";
            }
            if (in_array('location', $columns)) {
                $selectColumns .= ", location";
            }
            if (in_array('department', $columns)) {
                $selectColumns .= ", department";
            }
            if (in_array('role', $columns)) {
                $selectColumns .= ", role";
            }
            if (in_array('timezone', $columns)) {
                $selectColumns .= ", timezone";
            }
            if (in_array('language', $columns)) {
                $selectColumns .= ", language";
            }
            
            $stmt = $pdo->prepare("SELECT $selectColumns FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $updatedUser = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            error_log("updateProfile: updatedUser = " . print_r($updatedUser, true));
            
            if ($updatedUser) {
                // Le nom est déjà dans la colonne 'name', pas besoin de le construire
                
                // Générer les initiales de manière cohérente
                $fullName = '';
                if (isset($updatedUser['name']) && !empty($updatedUser['name'])) {
                    $fullName = $updatedUser['name'];
                } else {
                    $fullName = $updatedUser['email'];
                }
                
                // Générer les initiales directement ici
                $words = explode(' ', trim($fullName));
                $initials = '';
                foreach ($words as $word) {
                    if (!empty($word)) {
                        $initials .= strtoupper(substr($word, 0, 1));
                        if (strlen($initials) >= 2) {
                            break;
                        }
                    }
                }
                $updatedUser['initials'] = $initials ?: 'U';
                
                // Avatar - ne pas remplacer si l'utilisateur a déjà un avatar personnalisé
                if (empty($updatedUser['avatar'])) {
                    $updatedUser['avatar_url'] = null; // Pas d'avatar personnalisé
                } else {
                    $updatedUser['avatar_url'] = $updatedUser['avatar'];
                }
                
                            error_log("updateProfile: Données enrichies = " . print_r($updatedUser, true));
        }
        
        // Mettre à jour la session avec les nouvelles données
        if ($updatedUser) {
            try {
                // Préparer les données pour la session (format attendu par SessionManager)
                $sessionData = [
                    'id' => $updatedUser['id'],
                    'email' => $updatedUser['email'],
                    'name' => $updatedUser['name'] ?? $updatedUser['email'],
                    'role' => $updatedUser['role'] ?? 'admin',
                    'initials' => $updatedUser['initials'] ?? 'U',
                    'phone' => $updatedUser['phone'] ?? '',
                    'location' => $updatedUser['location'] ?? '',
                    'department' => $updatedUser['department'] ?? '',
                    'timezone' => $updatedUser['timezone'] ?? 'Europe/Paris',
                    'language' => $updatedUser['language'] ?? 'fr',
                    'avatar' => $updatedUser['avatar'] ?? null
                ];
                
                // Mettre à jour la session avec les nouvelles données via SessionManager
                $sessionUpdated = SessionManager::updateUserData($sessionData);
                if ($sessionUpdated) {
                    error_log("updateProfile: Session mise à jour avec les nouvelles données via SessionManager");
                } else {
                    error_log("updateProfile: Échec de la mise à jour de la session via SessionManager");
                }
            } catch (\Exception $e) {
                error_log("updateProfile: Erreur lors de la mise à jour de la session: " . $e->getMessage());
            }
        }
        
        // Retourner le succès
        echo json_encode([
            'success' => true, 
            'message' => 'Profil mis à jour avec succès',
            'user' => $updatedUser
        ]);
        
        error_log("updateProfile: Réponse envoyée avec succès");
            
        } catch (\Exception $e) {
            error_log("Erreur updateProfile : " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Erreur serveur : ' . $e->getMessage()
            ]);
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
        
        // Séparer les données d'apparence des données de localisation
        $appearanceData = [];
        $localizationData = [];
        
        // Champs d'apparence (gérés par AppearanceSettingsRepository)
        $appearanceFields = ['theme', 'primary_color', 'font_size'];
        foreach ($appearanceFields as $field) {
            if (isset($data[$field])) {
                $appearanceData[$field] = $data[$field];
            }
        }
        
        // Champs de localisation (gérés directement dans la table users)
        $localizationFields = ['language', 'timezone', 'date_format', 'time_format', 'auto_save'];
        foreach ($localizationFields as $field) {
            if (isset($data[$field])) {
                $localizationData[$field] = $data[$field];
            }
        }

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

        // Récupérer l'utilisateur actuel depuis la session
        $currentUser = SessionManager::getCurrentUser();
        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Utilisateur non authentifié']);
            return;
        }
        
        $userId = $currentUser['id'];
        error_log("Mise à jour des paramètres d'apparence pour l'utilisateur ID: $userId");
        
        // Mettre à jour les données d'apparence
        $appearanceSuccess = true;
        if (!empty($appearanceData)) {
            $appearanceSuccess = $this->appearanceSettingsRepository->updateAppearance($userId, $appearanceData);
        }
        
        // Mettre à jour les données de localisation dans la table users
        $localizationSuccess = true;
        if (!empty($localizationData)) {
            // Traitement spécial pour auto_save (checkbox)
            if (isset($localizationData['auto_save'])) {
                $localizationData['auto_save'] = $localizationData['auto_save'] === 'on' ? 1 : 0;
            }
            
            $localizationSuccess = $this->userRepository->update($userId, $localizationData);
        }

        if ($appearanceSuccess && $localizationSuccess) {
            // Récupérer les nouvelles données
            $updatedAppearance = $this->appearanceSettingsRepository->findByUserId($userId);
            
            // Récupérer les données de localisation mises à jour
            $pdo = \App\Service\Database::connect();
            $stmt = $pdo->prepare("SELECT language, timezone, date_format, time_format, auto_save FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $updatedLocalization = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Paramètres mis à jour avec succès',
                'appearance' => $updatedAppearance,
                'localization' => $updatedLocalization
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde']);
        }
    }

    /**
     * Met à jour les paramètres d'auto-save via AJAX
     */
    public function updateAutoSave()
    {
        // Vérifier que c'est une requête POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        // Récupérer l'utilisateur actuel depuis la session
        $currentUser = SessionManager::getCurrentUser();
        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Utilisateur non authentifié']);
            return;
        }
        
        $userId = $currentUser['id'];
        $enabled = isset($_POST['auto_save']) && $_POST['auto_save'] === 'on';
        
        // Mettre à jour l'auto-save
        $success = AutoSaveService::setAutoSaveEnabled($userId, $enabled);
        
        if ($success) {
            echo json_encode([
                'success' => true, 
                'message' => 'Paramètres d\'auto-save mis à jour avec succès',
                'auto_save' => $enabled
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de l\'auto-save']);
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

        // Récupérer l'utilisateur actuel depuis la session
        $sessionUser = SessionManager::getCurrentUser();
        if (!$sessionUser || !isset($sessionUser['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
            return;
        }
        $userId = $sessionUser['id'];
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
    
    /**
     * Gère l'upload de la photo de profil
     */
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