<?php

namespace App\Controller;

use App\Service\TwigService;
use App\Service\SessionManager;
use App\Service\ValidationService;
use App\Service\CsrfService;
use App\Service\RateLimitService;
use App\Service\TwoFactorService;
use App\Repository\UserRepository;
use App\Repository\RememberMeTokenRepository;
use App\Entity\User;

class AuthController
{
    private TwigService $twig;
    private ValidationService $validator;
    private CsrfService $csrf;
    private RateLimitService $rateLimit;
    private UserRepository $userRepository;
    private RememberMeTokenRepository $rememberMeRepository;
    private TwoFactorService $twoFactorService;

    public function __construct(TwigService $twig, TwoFactorService $twoFactorService = null)
    {
        $this->twig = $twig;
        $this->validator = new ValidationService();
        $this->csrf = new CsrfService();
        $this->rateLimit = new RateLimitService();
        $this->userRepository = new UserRepository();
        $this->rememberMeRepository = new RememberMeTokenRepository();
        
        // Utiliser le service injecté ou créer un nouveau (fallback)
        $this->twoFactorService = $twoFactorService ?? new TwoFactorService();
    }

    public function login()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                return $this->processLogin();
            }
            
            // Afficher le formulaire de connexion
            return $this->showLoginForm();
            
        } catch (\Throwable $e) {
            error_log("Erreur dans AuthController::login: " . $e->getMessage());
            return $this->showLoginForm(['error' => 'Une erreur est survenue lors de la connexion.']);
        }
    }

    private function processLogin()
    {
        // Vérification CSRF avec gestion d'erreur améliorée
        if (!$this->csrf->validateFromRequest('login')) {
            // Régénérer un nouveau token CSRF et permettre une nouvelle tentative
            return $this->showLoginForm(['error' => 'Veuillez réessayer votre connexion.']);
        }
        
        // Vérification du rate limiting
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $email = $this->validator->sanitizeString($_POST['email'] ?? '');
        
        $rateLimitCheck = $this->rateLimit->checkLoginAttempts($clientIp, $email);
        if (!$rateLimitCheck['allowed']) {
            $retryAfter = ceil($rateLimitCheck['retry_after'] / 60);
            return $this->showLoginForm([
                'error' => $rateLimitCheck['reason'] . ". Réessayez dans {$retryAfter} minutes."
            ]);
        }
        
        $password = $_POST['password'] ?? '';

        // Validation des entrées
        $this->validator->validateEmail($email, 'email');
        $this->validator->validateRequired($password, 'password');
        
        if ($this->validator->hasErrors()) {
            $errors = $this->validator->getErrors();
            $firstError = reset($errors);
            return $this->showLoginForm(['error' => $firstError, 'email' => $email]);
        }

        try {
            // Connexion à la base de données via le service
            $pdo = \App\Service\Database::connect();

            // Rechercher l'utilisateur
            $stmt = $pdo->prepare("SELECT id, email, password, name, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                // Enregistrer la tentative échouée
                $this->rateLimit->recordFailedLogin($clientIp, $email);
                return $this->showLoginForm(['error' => 'Identifiants incorrects.', 'email' => $email]);
            }

            // Vérifier le mot de passe
            if (!password_verify($password, $user['password'])) {
                // Enregistrer la tentative échouée
                $this->rateLimit->recordFailedLogin($clientIp, $email);
                return $this->showLoginForm(['error' => 'Identifiants incorrects.', 'email' => $email]);
            }

            // Authentification réussie
            $this->rateLimit->recordSuccessfulLogin($clientIp, $email);
            
            // Vérifier si la 2FA est requise ou activée pour cet utilisateur
            $twoFactorRequired = $this->twoFactorService->isTwoFactorRequired($user['id']);
            $twoFactorEnabled = $this->twoFactorService->isTwoFactorEnabled($user['id']);
            
            if ($twoFactorRequired || $twoFactorEnabled) {
                // Stocker l'utilisateur en attente de vérification 2FA
                $_SESSION['pending_2fa_user'] = [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'is_admin' => ($user['role'] === 'admin'),
                    'notification_email' => $user['notification_email'] ?? $user['email']
                ];
                
                // Générer et envoyer un code de vérification
                $code = $this->twoFactorService->generateOtpCode();
                $this->twoFactorService->storeOtpCode($user['id'], $code);
                
                // Utiliser l'email de notification si disponible
                $emailToUse = $user['notification_email'] ?? $user['email'];
                $this->twoFactorService->sendVerificationCode($user['id'], $emailToUse, $code);
                
                // Rediriger vers la page de vérification 2FA
                header('Location: /auth/verify-2fa.php');
                exit;
            }
            
            // Connexion normale (sans 2FA)
            $this->createSession($user);

            // Gérer "Se souvenir de moi"
            if (isset($_POST['remember_me']) && $_POST['remember_me'] === 'on') {
                $this->createRememberMeToken($user['id']);
            }

            // Mettre à jour last_login si la colonne existe
            try {
                $stmt = $pdo->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
            } catch (\Exception $e) {
                // Ignorer si la colonne n'existe pas
            }

            // Redirection vers le dashboard
            header('Location: /dashboard');
            exit;

        } catch (\PDOException $e) {
            error_log("Erreur BDD dans processLogin: " . $e->getMessage());
            return $this->showLoginForm(['error' => 'Erreur de connexion à la base de données.']);
        }
    }

    private function createSession(array $user): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role'], // 'admin' ou 'technician'
            'is_admin' => ($user['role'] === 'admin')
        ];

        $_SESSION['last_activity'] = time();
        $_SESSION['authenticated'] = true;
    }

    private function showLoginForm(array $data = []): string
    {
        // Générer le token CSRF pour le formulaire
        $csrfToken = $this->csrf->getTokenField('login');
        
        return $this->twig->render('auth/login.html.twig', array_merge([
            'title' => 'Connexion - TerrainTrack',
            'error' => null,
            'csrf_token_field' => $csrfToken
        ], $data));
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Détruire la session
        session_destroy();
        
        // Redirection vers la page de connexion
        header('Location: /login');
        exit;
    }

    public function unauthorized()
    {
        http_response_code(403);
        return $this->twig->render('auth/unauthorized.html.twig', [
            'title' => 'Accès non autorisé - TerrainTrack'
        ]);
    }

    public function resetPassword()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                return $this->processPasswordReset();
            }
            
            // Afficher le formulaire de réinitialisation de mot de passe
            return $this->showPasswordResetForm();
            
        } catch (\Throwable $e) {
            error_log("Erreur dans AuthController::resetPassword: " . $e->getMessage());
            return $this->showPasswordResetForm(['error' => 'Une erreur est survenue lors de la réinitialisation.']);
        }
    }

    private function showPasswordResetForm(array $data = [])
    {
        return $this->twig->render('auth/reset_password.html.twig', array_merge([
            'title' => 'Réinitialisation du mot de passe - TerrainTrack',
            'csrf_token' => $this->csrf->generateToken('reset_password')
        ], $data));
    }

    private function processPasswordReset()
    {
        // Vérification CSRF
        if (!$this->csrf->validateFromRequest('reset_password')) {
            return $this->showPasswordResetForm(['error' => 'Suite à une période d\'inactivité, votre session a expiré. Veuillez réessayer.']);
        }
        
        // Vérification du rate limiting
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!$this->rateLimit->checkLimit($clientIp, 'password_reset', 5, 300)) {
            return $this->showPasswordResetForm(['error' => 'Trop de tentatives. Veuillez attendre 5 minutes.']);
        }
        
        $email = $_POST['email'] ?? '';
        
        // Validation de l'email
        if (empty($email)) {
            return $this->showPasswordResetForm(['error' => 'Veuillez saisir votre adresse email.']);
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->showPasswordResetForm(['error' => 'Adresse email invalide.']);
        }
        
        // Pour l'instant, on simule l'envoi d'email
        // Dans une vraie application, vous enverriez un email avec un lien de réinitialisation
        
        return $this->showPasswordResetForm([
            'success' => 'Si cette adresse email existe dans notre système, vous recevrez un lien de réinitialisation.'
        ]);
    }

    public function register()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                return $this->processRegistration();
            }
            
            // Afficher le formulaire d'inscription
            return $this->showRegistrationForm();
            
        } catch (\Throwable $e) {
            error_log("Erreur dans AuthController::register: " . $e->getMessage());
            return $this->showRegistrationForm(['error' => 'Une erreur est survenue lors de l\'inscription.']);
        }
    }

    private function showRegistrationForm(array $data = [])
    {
        return $this->twig->render('register.html.twig', array_merge([
            'title' => 'Créer un compte - TerrainTrack',
            'csrf_token' => $this->csrf->generateToken('register')
        ], $data));
    }

    private function processRegistration()
    {
        // Vérification CSRF
        if (!$this->csrf->validateFromRequest('register')) {
            return $this->showRegistrationForm(['error' => 'Suite à une période d\'inactivité, votre session a expiré. Veuillez réessayer.']);
        }
        
        // Vérification du rate limiting
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!$this->rateLimit->checkLimit($clientIp, 'registration', 3, 300)) {
            return $this->showRegistrationForm(['error' => 'Trop de tentatives. Veuillez attendre 5 minutes.']);
        }
        
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation des champs
        $errors = [];
        
        if (empty($email)) {
            $errors[] = 'L\'adresse email est requise.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Adresse email invalide.';
        }
        
        if (empty($password)) {
            $errors[] = 'Le mot de passe est requis.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = 'Les mots de passe ne correspondent pas.';
        }
        
        if (!empty($errors)) {
            return $this->showRegistrationForm([
                'error' => implode(' ', $errors),
                'email' => $email
            ]);
        }
        
        // Vérifier si l'email existe déjà
        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser) {
            return $this->showRegistrationForm([
                'error' => 'Cette adresse email est déjà utilisée.',
                'email' => $email
            ]);
        }
        
        // Créer un nouvel utilisateur
        $user = new User();
        $user->setEmail($email);
        $user->setPassword(password_hash($password, PASSWORD_DEFAULT));
        $user->setIsActive(true);
        $user->setCreatedAt(new \DateTime());
        $user->setUpdatedAt(new \DateTime());
        
        // Enregistrer l'utilisateur en base de données
        try {
            $success = $this->userRepository->save($user);
            
            if ($success) {
                return $this->showRegistrationForm([
                    'success' => 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.',
                    'email' => $email
                ]);
            } else {
                return $this->showRegistrationForm([
                    'error' => 'Une erreur est survenue lors de la création du compte. Veuillez réessayer.',
                    'email' => $email
                ]);
            }
        } catch (\Exception $e) {
            error_log("Erreur lors de l'inscription: " . $e->getMessage());
            return $this->showRegistrationForm([
                'error' => 'Une erreur est survenue lors de la création du compte. Veuillez réessayer.',
                'email' => $email
            ]);
        }
    }

    /**
     * Crée un token "Se souvenir de moi" pour l'utilisateur
     */
    private function createRememberMeToken(int $userId): void
    {
        try {
            // Générer un token sécurisé
            $token = bin2hex(random_bytes(32));
            
            // Date d'expiration : 30 jours
            $expiresAt = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));
            
            // Sauvegarder le token en base de données
            $this->rememberMeRepository->addToken($userId, $token, $expiresAt);
            
            // Créer le cookie (30 jours)
            setcookie('remember_me', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
            
        } catch (\Exception $e) {
            error_log("Erreur lors de la création du token remember me: " . $e->getMessage());
            // Ne pas faire échouer la connexion si le remember me échoue
        }
    }
}