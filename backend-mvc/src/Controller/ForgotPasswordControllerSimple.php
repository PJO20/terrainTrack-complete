<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\EmailServiceAdvanced;
use App\Service\TokenService;
use PDO;

class ForgotPasswordControllerSimple
{
    private UserRepository $userRepository;
    private EmailServiceAdvanced $emailService;
    private TokenService $tokenService;
    private PDO $pdo;

    public function __construct(
        UserRepository $userRepository,
        EmailServiceAdvanced $emailService,
        TokenService $tokenService,
        PDO $pdo
    ) {
        $this->userRepository = $userRepository;
        $this->emailService = $emailService;
        $this->tokenService = $tokenService;
        $this->pdo = $pdo;
    }
    /**
     * Affiche le formulaire de mot de passe oublié - Version simple
     */
    public function showForgotPassword(): void
    {
        echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mot de passe oublié - TerrainTrack</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; }
        .form-card { background: #f9f9f9; padding: 30px; border-radius: 8px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="email"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .submit-btn { background: #2346a9; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; }
        .submit-btn:hover { background: #1d357a; }
        .back-link { color: #2346a9; text-decoration: none; }
    </style>
</head>
<body>
    <div class="form-card">
        <h1>🔐 Réinitialiser votre mot de passe</h1>
        <p>Saisissez votre adresse e-mail et nous vous enverrons les instructions pour réinitialiser votre mot de passe.</p>
        
        <form method="POST" action="/forgot-password">
            <div class="form-group">
                <label for="email">Adresse e-mail</label>
                <input id="email" type="email" name="email" placeholder="votre@email.com" required>
            </div>
            <button class="submit-btn" type="submit">
                📧 Envoyer les instructions
            </button>
        </form>
        
        <p style="margin-top: 20px;">
            <a href="/login" class="back-link">← Retour à la connexion</a>
        </p>
    </div>
</body>
</html>';
    }

    /**
     * Traite la demande de réinitialisation - Version simple avec logique complète
     */
    public function handleForgotPassword(): void
    {
        try {
            $email = trim($_POST['email'] ?? '');
            
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->showError('Veuillez saisir une adresse e-mail valide.');
                return;
            }

            // Vérifier si l'utilisateur existe
            $user = $this->userRepository->findByEmail($email);
            
            if (!$user) {
                // Pour des raisons de sécurité, on affiche le même message même si l'email n'existe pas
                $this->showSuccess($email, 'Si cette adresse e-mail est associée à un compte, vous recevrez un e-mail avec les instructions de réinitialisation dans quelques minutes.');
                return;
            }

            // Générer un token de réinitialisation
            $token = $this->tokenService->generateResetToken();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours')); // Token valide 24 heures

            // Sauvegarder le token en base de données
            $this->saveResetToken($user->getId(), $token, $expiresAt);

            // Envoyer l'email de réinitialisation
            $resetLink = "http://localhost:8888/reset-password?token=" . $token;
            
            $emailSent = $this->emailService->sendPasswordResetEmail(
                $user->getEmail(),
                $user->getName() ?? $user->getEmail(),
                $resetLink
            );

            if ($emailSent) {
                $this->showSuccess($email, 'Un e-mail avec les instructions de réinitialisation a été envoyé à votre adresse e-mail. Vérifiez votre boîte de réception et vos spams.');
            } else {
                $this->showError('Une erreur est survenue lors de l\'envoi de l\'e-mail. Veuillez réessayer plus tard.');
            }

        } catch (\Exception $e) {
            error_log('Erreur lors de la demande de réinitialisation: ' . $e->getMessage());
            $this->showError('Une erreur est survenue. Veuillez réessayer plus tard.');
        }
    }

    /**
     * Affiche une page d'erreur
     */
    private function showError(string $message): void
    {
        echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Erreur - TerrainTrack</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; border: 1px solid #f5c6cb; }
        .back-link { color: #2346a9; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="error">
        <h2>❌ Erreur</h2>
        <p>' . htmlspecialchars($message) . '</p>
        <a href="/forgot-password" class="back-link">← Retour</a>
    </div>
</body>
</html>';
    }

    /**
     * Affiche une page de succès
     */
    private function showSuccess(string $email, string $message): void
    {
        echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Email envoyé - TerrainTrack</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; border: 1px solid #c3e6cb; }
        .back-link { color: #2346a9; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        .note { background: #e2e3e5; color: #383d41; padding: 10px; border-radius: 4px; margin-top: 15px; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="success">
        <h2>✅ Email envoyé</h2>
        <p>' . htmlspecialchars($message) . '</p>
        <div class="note">
            <strong>Note :</strong> En mode développement, les emails sont loggés dans le dossier <code>logs/emails/</code>
        </div>
        <p style="margin-top: 20px;">
            <a href="/login" class="back-link">← Retour à la connexion</a>
        </p>
    </div>
</body>
</html>';
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
}
