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
        // Générer un token CSRF simple sans session
        $csrfToken = bin2hex(random_bytes(32));
        
        // Utiliser le template Twig avec le nouveau design
        $template = file_get_contents(__DIR__ . '/../../template/auth/forgot-password.html.twig');
        
        // Remplacer les variables Twig
        $template = str_replace('{{ title|default(\'Mot de passe oublié - TerrainTrack\') }}', 'Mot de passe oublié - TerrainTrack', $template);
        $template = str_replace('<title>Réinitialiser votre mot de passe - TerrainTrack</title>', '<title>Mot de passe oublié - TerrainTrack</title>', $template);
        $template = str_replace('{{ csrf_token_field|raw }}', '<input type="hidden" name="csrf_token" value="' . $csrfToken . '">', $template);
        
        // Nettoyer toutes les conditions Twig
        $template = preg_replace('/\{\%\s*if\s+success\s*\%\}.*?\{\%\s*endif\s*\%\}/s', '', $template);
        $template = preg_replace('/\{\%\s*if\s+error\s*\%\}.*?\{\%\s*endif\s*\%\}/s', '', $template);
        $template = preg_replace('/\{\%\s*if\s+info\s*\%\}.*?\{\%\s*endif\s*\%\}/s', '', $template);
        $template = preg_replace('/\{\%\s*if\s+not\s+success\s*\%\}(.*?)\{\%\s*endif\s*\%\}/s', '$1', $template);
        
        // Nettoyer les variables restantes
        $template = str_replace('{{ email ?? \'\' }}', '', $template);
        $template = str_replace('{{ success }}', '', $template);
        $template = str_replace('{{ error }}', '', $template);
        $template = str_replace('{{ info }}', '', $template);
        
        echo $template;
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
        $template = file_get_contents(__DIR__ . '/../../template/auth/forgot-password.html.twig');
        
        // Remplacer les variables Twig pour afficher l'erreur
        $template = str_replace('{{ title|default(\'Mot de passe oublié - TerrainTrack\') }}', 'Erreur - TerrainTrack', $template);
        $template = str_replace('<title>Réinitialiser votre mot de passe - TerrainTrack</title>', '<title>Erreur - TerrainTrack</title>', $template);
        $template = str_replace('{{ csrf_token_field|raw }}', '', $template);
        
        // Nettoyer toutes les conditions Twig et garder seulement l'erreur
        $template = preg_replace('/\{\%\s*if\s+success\s*\%\}.*?\{\%\s*endif\s*\%\}/s', '', $template);
        $template = preg_replace('/\{\%\s*if\s+info\s*\%\}.*?\{\%\s*endif\s*\%\}/s', '', $template);
        $template = preg_replace('/\{\%\s*if\s+error\s*\%\}(.*?)\{\%\s*endif\s*\%\}/s', '$1', $template);
        $template = preg_replace('/\{\%\s*if\s+not\s+success\s*\%\}.*?\{\%\s*endif\s*\%\}/s', '', $template);
        
        // Remplacer les variables
        $template = str_replace('{{ error }}', htmlspecialchars($message), $template);
        $template = str_replace('{{ email ?? \'\' }}', '', $template);
        $template = str_replace('{{ success }}', '', $template);
        $template = str_replace('{{ info }}', '', $template);
        
        echo $template;
    }

    /**
     * Affiche une page de succès
     */
    private function showSuccess(string $email, string $message): void
    {
        $template = file_get_contents(__DIR__ . '/../../template/auth/forgot-password.html.twig');
        
        // Remplacer les variables Twig pour afficher le succès
        $template = str_replace('{{ title|default(\'Mot de passe oublié - TerrainTrack\') }}', 'Email envoyé - TerrainTrack', $template);
        $template = str_replace('<title>Réinitialiser votre mot de passe - TerrainTrack</title>', '<title>Email envoyé - TerrainTrack</title>', $template);
        $template = str_replace('{{ csrf_token_field|raw }}', '', $template);
        
        // Nettoyer toutes les conditions Twig et garder seulement le succès
        $template = preg_replace('/\{\%\s*if\s+error\s*\%\}.*?\{\%\s*endif\s*\%\}/s', '', $template);
        $template = preg_replace('/\{\%\s*if\s+info\s*\%\}.*?\{\%\s*endif\s*\%\}/s', '', $template);
        $template = preg_replace('/\{\%\s*if\s+success\s*\%\}(.*?)\{\%\s*endif\s*\%\}/s', '$1', $template);
        $template = preg_replace('/\{\%\s*if\s+not\s+success\s*\%\}.*?\{\%\s*endif\s*\%\}/s', '', $template);
        
        // Remplacer les variables
        $template = str_replace('{{ success }}', htmlspecialchars($message), $template);
        $template = str_replace('{{ email ?? \'\' }}', '', $template);
        $template = str_replace('{{ error }}', '', $template);
        $template = str_replace('{{ info }}', '', $template);
        
        echo $template;
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
