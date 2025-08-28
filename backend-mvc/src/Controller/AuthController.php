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
}