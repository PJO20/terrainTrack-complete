<?php

namespace App\Controller;

use App\Service\TwigService;
use App\Service\SessionManager;
use App\Service\ValidationService;
use App\Service\CsrfService;
use App\Service\RateLimitService;

class AuthController
{
    private TwigService $twig;
    private ValidationService $validator;
    private CsrfService $csrf;
    private RateLimitService $rateLimit;

    public function __construct(TwigService $twig)
    {
        $this->twig = $twig;
        $this->validator = new ValidationService();
        $this->csrf = new CsrfService();
        $this->rateLimit = new RateLimitService();
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
        // Vérification CSRF
        if (!$this->csrf->validateFromRequest('login')) {
            return $this->showLoginForm(['error' => 'Token de sécurité invalide. Veuillez réessayer.']);
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
            $this->createSession($user);

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
            return $this->showPasswordResetForm(['error' => 'Token de sécurité invalide. Veuillez réessayer.']);
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
            return $this->showRegistrationForm(['error' => 'Token de sécurité invalide. Veuillez réessayer.']);
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
        
        // Pour l'instant, on simule l'inscription
        // Dans une vraie application, vous vérifieriez l'unicité et inséreriez en base
        
        return $this->showRegistrationForm([
            'success' => 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.',
            'email' => $email
        ]);
    }
}