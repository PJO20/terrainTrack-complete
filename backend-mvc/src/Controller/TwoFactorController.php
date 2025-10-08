<?php

namespace App\Controller;

use App\Service\TwoFactorService;
use App\Service\SessionManager;
use App\Service\TwigService;

class TwoFactorController
{
    private ?TwigService $twig;
    private ?SessionManager $sessionManager;
    private ?TwoFactorService $twoFactorService;

    public function __construct()
    {
        // Les services seront injectés par le conteneur
        $this->twig = null;
        $this->sessionManager = null;
        $this->twoFactorService = null;
    }

    public function setTwig(TwigService $twig): void
    {
        $this->twig = $twig;
    }

    public function setSessionManager(SessionManager $sessionManager): void
    {
        $this->sessionManager = $sessionManager;
    }

    public function setTwoFactorService(TwoFactorService $twoFactorService): void
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Page de gestion de la 2FA
     */
    public function index(): void
    {
        SessionManager::start();
        
        if (!SessionManager::isAuthenticated()) {
            header('Location: /login');
            exit;
        }

        $user = SessionManager::getUser();
        $userId = $user['id'];

        // Test simple sans dépendances
        $twoFactorEnabled = false;
        $twoFactorRequired = ($user['role'] === 'admin');

        echo "<!DOCTYPE html>
<html>
<head>
    <title>Authentification à deux facteurs</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .container { max-width: 600px; margin: 0 auto; }
        .status { padding: 20px; margin: 20px 0; border-radius: 8px; }
        .enabled { background: #f0fdf4; border: 1px solid #22c55e; }
        .disabled { background: #fef2f2; border: 1px solid #ef4444; }
        .required { background: #fef3c7; border: 1px solid #f59e0b; }
        button { padding: 10px 20px; margin: 10px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-danger { background: #ef4444; color: white; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔐 Authentification à deux facteurs</h1>
        <p>Utilisateur: {$user['name']} ({$user['email']})</p>
        
        <div class='status " . ($twoFactorEnabled ? 'enabled' : 'disabled') . "'>
            <h3>État actuel</h3>
            <p>2FA " . ($twoFactorEnabled ? 'Activé' : 'Désactivé') . "</p>
        </div>
        
        " . ($twoFactorRequired ? "<div class='status required'>
            <h3>⚠️ Obligatoire</h3>
            <p>L'authentification à deux facteurs est obligatoire pour votre rôle d'administrateur.</p>
        </div>" : "") . "
        
        <div>
            <button class='btn-primary' onclick='testActivation()'>Activer la 2FA</button>
            " . ($twoFactorEnabled ? "<button class='btn-danger' onclick='testDeactivation()'>Désactiver</button>" : "") . "
        </div>
        
        <div id='result' style='margin-top: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px; display: none;'></div>
    </div>
    
    <script>
    function testActivation() {
        console.log('Test activation 2FA...');
        fetch('/security/two-factor/enable', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Réponse:', data);
            document.getElementById('result').style.display = 'block';
            document.getElementById('result').innerHTML = '<strong>Réponse:</strong> ' + JSON.stringify(data, null, 2);
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('result').style.display = 'block';
            document.getElementById('result').innerHTML = '<strong>Erreur:</strong> ' + error.message;
        });
    }
    
    function testDeactivation() {
        console.log('Test désactivation 2FA...');
        fetch('/security/two-factor/disable', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Réponse:', data);
            document.getElementById('result').style.display = 'block';
            document.getElementById('result').innerHTML = '<strong>Réponse:</strong> ' + JSON.stringify(data, null, 2);
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('result').style.display = 'block';
            document.getElementById('result').innerHTML = '<strong>Erreur:</strong> ' + error.message;
        });
    }
    </script>
</body>
</html>";
    }

    /**
     * Activer la 2FA
     */
    public function enable(): void
    {
        SessionManager::start();
        
        if (!SessionManager::isAuthenticated()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Non authentifié']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
            exit;
        }

        $user = SessionManager::getUser();
        $userId = $user['id'];

        try {
            // Générer et stocker un code OTP
            $code = $this->twoFactorService->generateOtpCode();
            $this->twoFactorService->storeOtpCode($userId, $code);
            $this->twoFactorService->sendVerificationCode($userId, $user['email'], $code);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Code de vérification envoyé par email'
            ]);
        } catch (\Exception $e) {
            error_log("Erreur activation 2FA: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'activation']);
        }
    }

    /**
     * Vérifier le code 2FA
     */
    public function verify(): void
    {
        SessionManager::start();
        
        if (!SessionManager::isAuthenticated()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Non authentifié']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
            exit;
        }

        $user = SessionManager::getUser();
        $userId = $user['id'];
        $code = $_POST['code'] ?? '';

        if (empty($code)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Code requis']);
            exit;
        }

        try {
            if ($this->twoFactorService->verifyOtpCode($userId, $code)) {
                // Générer des codes de récupération
                $backupCodes = $this->twoFactorService->generateRecoveryCodes();
                
                // Activer la 2FA
                if ($this->twoFactorService->enableTwoFactor($userId, $backupCodes)) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Authentification à deux facteurs activée',
                        'backup_codes' => $backupCodes
                    ]);
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'activation']);
                }
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Code invalide ou expiré']);
            }
        } catch (\Exception $e) {
            error_log("Erreur vérification 2FA: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la vérification']);
        }
    }

    /**
     * Désactiver la 2FA
     */
    public function disable(): void
    {
        SessionManager::start();
        
        if (!SessionManager::isAuthenticated()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Non authentifié']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
            exit;
        }

        $user = SessionManager::getUser();
        $userId = $user['id'];

        // Vérifier si la 2FA est obligatoire
        if ($this->twoFactorService->isTwoFactorRequired($userId)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'La 2FA est obligatoire pour votre rôle']);
            exit;
        }

        try {
            if ($this->twoFactorService->disableTwoFactor($userId)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Authentification à deux facteurs désactivée']);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Erreur lors de la désactivation']);
            }
        } catch (\Exception $e) {
            error_log("Erreur désactivation 2FA: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la désactivation']);
        }
    }

    /**
     * Page de vérification 2FA lors de la connexion
     */
    public function verifyPage(): void
    {
        SessionManager::start();
        
        if (!isset($_SESSION['pending_2fa_user'])) {
            header('Location: /login');
            exit;
        }

        $this->twig->render('auth/verify-2fa.html.twig', [
            'page_title' => 'Vérification 2FA',
            'user' => $_SESSION['pending_2fa_user']
        ]);
    }

    /**
     * Traitement de la vérification 2FA lors de la connexion
     */
    public function verifyLogin(): void
    {
        SessionManager::start();
        
        if (!isset($_SESSION['pending_2fa_user'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /auth/verify-2fa');
            exit;
        }

        $user = $_SESSION['pending_2fa_user'];
        $userId = $user['id'];
        $code = $_POST['code'] ?? '';

        if (empty($code)) {
            header('Location: /auth/verify-2fa?error=Code requis');
            exit;
        }

        try {
            if ($this->twoFactorService->verifyOtpCode($userId, $code)) {
                // Connexion réussie
                $_SESSION['user'] = $user;
                $_SESSION['last_activity'] = time();
                $_SESSION['authenticated'] = true;
                unset($_SESSION['pending_2fa_user']);

                header('Location: /dashboard');
                exit;
            } else {
                header('Location: /auth/verify-2fa?error=Code invalide ou expiré');
                exit;
            }
        } catch (\Exception $e) {
            error_log("Erreur vérification 2FA login: " . $e->getMessage());
            header('Location: /auth/verify-2fa?error=Erreur lors de la vérification');
            exit;
        }
    }

    /**
     * Renvoyer un code 2FA
     */
    public function resendCode(): void
    {
        SessionManager::start();
        
        if (!isset($_SESSION['pending_2fa_user'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Session invalide']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
            exit;
        }

        $user = $_SESSION['pending_2fa_user'];
        $userId = $user['id'];

        try {
            $code = $this->twoFactorService->generateOtpCode();
            $this->twoFactorService->storeOtpCode($userId, $code);
            $this->twoFactorService->sendVerificationCode($userId, $user['email'], $code);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Nouveau code envoyé']);
        } catch (\Exception $e) {
            error_log("Erreur renvoi code 2FA: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'envoi']);
        }
    }
}
