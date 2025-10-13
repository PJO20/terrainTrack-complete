<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\TokenService;
use App\Service\TwigService;
use PDO;

class ResetPasswordController
{
    private TwigService $twigService;
    private UserRepository $userRepository;
    private TokenService $tokenService;
    private PDO $pdo;

    public function __construct(
        TwigService $twigService,
        UserRepository $userRepository,
        TokenService $tokenService,
        PDO $pdo
    ) {
        $this->twigService = $twigService;
        $this->userRepository = $userRepository;
        $this->tokenService = $tokenService;
        $this->pdo = $pdo;
    }

    /**
     * Affiche le formulaire de réinitialisation de mot de passe
     */
    public function showResetPassword(): void
    {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            $this->showError('Token de réinitialisation manquant.');
            return;
        }

        // Vérifier si le token est valide
        $tokenData = $this->getTokenData($token);
        if (!$tokenData) {
            $this->showError('Token de réinitialisation invalide ou expiré.');
            return;
        }

        // Générer un token CSRF simple
        $csrfToken = bin2hex(random_bytes(32));

        $this->twigService->render('auth/reset-password.html.twig', [
            'title' => 'Réinitialiser votre mot de passe - TerrainTrack',
            'token' => $token,
            'csrf_token_field' => '<input type="hidden" name="csrf_token" value="' . $csrfToken . '">'
        ]);
    }

    /**
     * Traite la réinitialisation de mot de passe
     */
    public function handleResetPassword(): void
    {
        try {
            $token = $_POST['token'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($token)) {
                $this->showError('Token de réinitialisation manquant.');
                return;
            }

            if (empty($password) || empty($confirmPassword)) {
                $this->showError('Veuillez saisir un mot de passe et sa confirmation.');
                return;
            }

            if ($password !== $confirmPassword) {
                $this->showError('Les mots de passe ne correspondent pas.');
                return;
            }

            if (strlen($password) < 8) {
                $this->showError('Le mot de passe doit contenir au moins 8 caractères.');
                return;
            }

            // Vérifier si le token est valide
            $tokenData = $this->getTokenData($token);
            if (!$tokenData) {
                $this->showError('Token de réinitialisation invalide ou expiré.');
                return;
            }

            // Mettre à jour le mot de passe
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $updateSuccess = $this->updateUserPassword($tokenData['user_id'], $hashedPassword);

            if (!$updateSuccess) {
                $this->showError('Erreur lors de la mise à jour du mot de passe. Veuillez réessayer.');
                return;
            }

            // Supprimer le token utilisé
            $this->deleteToken($token);

            // Afficher le succès
            $this->showSuccess();

        } catch (\Exception $e) {
            error_log('Erreur lors de la réinitialisation: ' . $e->getMessage());
            $this->showError('Une erreur est survenue. Veuillez réessayer plus tard.');
        }
    }

    /**
     * Récupère les données du token
     */
    private function getTokenData(string $token): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT user_id, expires_at 
                FROM password_reset_tokens 
                WHERE token = ? AND expires_at > NOW()
            ");
            $stmt->execute([$token]);
            $result = $stmt->fetch();

            return $result ?: null;
        } catch (\Exception $e) {
            error_log('Erreur lors de la récupération du token: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Met à jour le mot de passe de l'utilisateur
     */
    private function updateUserPassword(int $userId, string $hashedPassword): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET password = ?, password_updated_at = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([$hashedPassword, $userId]);
        } catch (\Exception $e) {
            error_log('Erreur lors de la mise à jour du mot de passe: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime le token utilisé
     */
    private function deleteToken(string $token): void
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM password_reset_tokens WHERE token = ?");
            $stmt->execute([$token]);
        } catch (\Exception $e) {
            error_log('Erreur lors de la suppression du token: ' . $e->getMessage());
        }
    }

    /**
     * Affiche une erreur
     */
    private function showError(string $message): void
    {
        $this->twigService->render('auth/reset-password.html.twig', [
            'title' => 'Erreur - TerrainTrack',
            'error' => $message,
            'token' => $_GET['token'] ?? $_POST['token'] ?? ''
        ]);
    }

    /**
     * Affiche le succès
     */
    private function showSuccess(): void
    {
        echo "
        <!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <title>Mot de passe réinitialisé - TerrainTrack</title>
            <link rel='stylesheet' href='/assets/css/style.css'>
            <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
            <style>
                body { font-family: Arial, sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; }
                .success { background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; border: 1px solid #c3e6cb; }
                .back-link { color: #2346a9; text-decoration: none; }
                .back-link:hover { text-decoration: underline; }
                .icon { font-size: 48px; color: #28a745; text-align: center; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class='icon'>
                <i class='fa-solid fa-check-circle'></i>
            </div>
            <div class='success'>
                <h2>✅ Mot de passe réinitialisé avec succès !</h2>
                <p>Votre mot de passe a été mis à jour. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.</p>
                <p><a href='/login' class='back-link'>← Retour à la connexion</a></p>
            </div>
        </body>
        </html>
        ";
    }
}
