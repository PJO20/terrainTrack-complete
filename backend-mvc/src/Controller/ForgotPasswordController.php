<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\TwigService;
use App\Service\SessionManager;
use App\Repository\UserRepository;
use App\Service\EmailService;
use App\Service\TokenService;
use PDO;
use Exception;

class ForgotPasswordController
{
    private TwigService $twigService;
    private UserRepository $userRepository;
    private EmailService $emailService;
    private TokenService $tokenService;
    private PDO $pdo;

    public function __construct(
        TwigService $twigService,
        UserRepository $userRepository,
        EmailService $emailService,
        TokenService $tokenService,
        PDO $pdo
    ) {
        $this->twigService = $twigService;
        $this->userRepository = $userRepository;
        $this->emailService = $emailService;
        $this->tokenService = $tokenService;
        $this->pdo = $pdo;
    }

    /**
     * Affiche le formulaire de mot de passe oublié
     */
    public function showForgotPassword(): void
    {
        // Générer un token CSRF simple sans session
        $csrfToken = bin2hex(random_bytes(32));
        
        $this->twigService->render('auth/forgot-password.html.twig', [
            'title' => 'Mot de passe oublié - TerrainTrack',
            'csrf_token_field' => '<input type="hidden" name="csrf_token" value="' . $csrfToken . '">'
        ]);
    }

    /**
     * Traite la demande de réinitialisation de mot de passe
     */
    public function handleForgotPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /forgot-password');
            exit;
        }

        try {
            $email = trim($_POST['email'] ?? '');
            
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->twigService->render('auth/forgot-password.html.twig', [
                    'title' => 'Mot de passe oublié - TerrainTrack',
                    'error' => 'Veuillez saisir une adresse e-mail valide.',
                    'email' => $email
                ]);
                return;
            }

            // Vérifier si l'utilisateur existe
            $user = $this->userRepository->findByEmail($email);
            
            if (!$user) {
                // Pour des raisons de sécurité, on affiche le même message même si l'email n'existe pas
                $this->twigService->render('auth/forgot-password.html.twig', [
                    'title' => 'Mot de passe oublié - TerrainTrack',
                    'success' => 'Si cette adresse e-mail est associée à un compte, vous recevrez un e-mail avec les instructions de réinitialisation dans quelques minutes.'
                ]);
                return;
            }

            // Générer un token de réinitialisation
            $token = $this->tokenService->generateResetToken();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token valide 1 heure

            // Sauvegarder le token en base de données
            $this->saveResetToken($user['id'], $token, $expiresAt);

            // Envoyer l'email de réinitialisation
            $resetLink = "http://localhost:8888/reset-password?token=" . $token;
            
            $emailSent = $this->emailService->sendPasswordResetEmail(
                $user['email'],
                $user['fullname'],
                $resetLink
            );

            if ($emailSent) {
                $this->twigService->render('auth/forgot-password.html.twig', [
                    'title' => 'Mot de passe oublié - TerrainTrack',
                    'success' => 'Un e-mail avec les instructions de réinitialisation a été envoyé à votre adresse e-mail. Vérifiez votre boîte de réception et vos spams.'
                ]);
            } else {
                $this->twigService->render('auth/forgot-password.html.twig', [
                    'title' => 'Mot de passe oublié - TerrainTrack',
                    'error' => 'Une erreur est survenue lors de l\'envoi de l\'e-mail. Veuillez réessayer plus tard.'
                ]);
            }

        } catch (Exception $e) {
            error_log('Erreur lors de la demande de réinitialisation: ' . $e->getMessage());
            
            $this->twigService->render('auth/forgot-password.html.twig', [
                'title' => 'Mot de passe oublié - TerrainTrack',
                'error' => 'Une erreur est survenue. Veuillez réessayer plus tard.'
            ]);
        }
    }

    /**
     * Affiche le formulaire de réinitialisation de mot de passe
     */
    public function showResetPassword(): void
    {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            $this->twigService->render('auth/reset-password.html.twig', [
                'title' => 'Réinitialisation - TerrainTrack',
                'error' => 'Token de réinitialisation manquant ou invalide.'
            ]);
            return;
        }

        // Vérifier la validité du token
        $tokenData = $this->validateResetToken($token);
        
        if (!$tokenData) {
            $this->twigService->render('auth/reset-password.html.twig', [
                'title' => 'Réinitialisation - TerrainTrack',
                'error' => 'Token de réinitialisation invalide ou expiré. Veuillez demander un nouveau lien de réinitialisation.'
            ]);
            return;
        }

        $this->twigService->render('auth/reset-password.html.twig', [
            'title' => 'Nouveau mot de passe - TerrainTrack',
            'token' => $token
        ]);
    }

    /**
     * Traite la réinitialisation de mot de passe
     */
    public function handleResetPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /forgot-password');
            exit;
        }

        try {
            $token = $_POST['token'] ?? '';
            $password = $_POST['password'] ?? '';
            $passwordConfirmation = $_POST['password_confirmation'] ?? '';

            // Validation des données
            if (empty($token)) {
                $this->twigService->render('auth/reset-password.html.twig', [
                    'title' => 'Réinitialisation - TerrainTrack',
                    'error' => 'Token de réinitialisation manquant.'
                ]);
                return;
            }

            if (empty($password) || strlen($password) < 8) {
                $this->twigService->render('auth/reset-password.html.twig', [
                    'title' => 'Nouveau mot de passe - TerrainTrack',
                    'error' => 'Le mot de passe doit contenir au moins 8 caractères.',
                    'token' => $token
                ]);
                return;
            }

            if ($password !== $passwordConfirmation) {
                $this->twigService->render('auth/reset-password.html.twig', [
                    'title' => 'Nouveau mot de passe - TerrainTrack',
                    'error' => 'Les mots de passe ne correspondent pas.',
                    'token' => $token
                ]);
                return;
            }

            // Vérifier la validité du token
            $tokenData = $this->validateResetToken($token);
            
            if (!$tokenData) {
                $this->twigService->render('auth/reset-password.html.twig', [
                    'title' => 'Réinitialisation - TerrainTrack',
                    'error' => 'Token de réinitialisation invalide ou expiré. Veuillez demander un nouveau lien de réinitialisation.'
                ]);
                return;
            }

            // Mettre à jour le mot de passe
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $success = $stmt->execute([$hashedPassword, $tokenData['user_id']]);

            if ($success) {
                // Supprimer le token utilisé
                $this->deleteResetToken($token);
                
                $this->twigService->render('auth/reset-password.html.twig', [
                    'title' => 'Mot de passe réinitialisé - TerrainTrack',
                    'success' => 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.'
                ]);
            } else {
                $this->twigService->render('auth/reset-password.html.twig', [
                    'title' => 'Nouveau mot de passe - TerrainTrack',
                    'error' => 'Une erreur est survenue lors de la réinitialisation. Veuillez réessayer.',
                    'token' => $token
                ]);
            }

        } catch (Exception $e) {
            error_log('Erreur lors de la réinitialisation: ' . $e->getMessage());
            
            $this->twigService->render('auth/reset-password.html.twig', [
                'title' => 'Nouveau mot de passe - TerrainTrack',
                'error' => 'Une erreur est survenue. Veuillez réessayer plus tard.',
                'token' => $token ?? ''
            ]);
        }
    }

    /**
     * Sauvegarde un token de réinitialisation en base de données
     */
    private function saveResetToken(int $userId, string $token, string $expiresAt): void
    {
        // Supprimer les anciens tokens pour cet utilisateur
        $stmt = $this->pdo->prepare("DELETE FROM password_reset_tokens WHERE user_id = ?");
        $stmt->execute([$userId]);

        // Insérer le nouveau token
        $stmt = $this->pdo->prepare("
            INSERT INTO password_reset_tokens (user_id, token, expires_at, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $token, $expiresAt]);
    }

    /**
     * Valide un token de réinitialisation
     */
    private function validateResetToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT prt.user_id, prt.expires_at 
            FROM password_reset_tokens prt 
            WHERE prt.token = ? AND prt.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Supprime un token de réinitialisation
     */
    private function deleteResetToken(string $token): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM password_reset_tokens WHERE token = ?");
        $stmt->execute([$token]);
    }
}
