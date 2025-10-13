<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\TokenService;
use PDO;

class ResetPasswordControllerSimple
{
    private UserRepository $userRepository;
    private TokenService $tokenService;
    private PDO $pdo;

    public function __construct(
        UserRepository $userRepository,
        TokenService $tokenService,
        PDO $pdo
    ) {
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

        // Afficher le formulaire
        $this->showForm($token);

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
            // Utiliser la timezone de la base de données (SYSTEM = heure française)
            $stmt = $this->pdo->prepare("
                SELECT user_id, expires_at 
                FROM password_reset_tokens 
                WHERE token = ? AND expires_at > NOW()
            ");
            $stmt->execute([$token]);
            $result = $stmt->fetch();

            if ($result === false) {
                return null;
            }

            return $result;
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
     * Affiche le formulaire
     */
    private function showForm(string $token): void
    {
        echo "
        <!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <title>Réinitialiser votre mot de passe - TerrainTrack</title>
            <link rel='stylesheet' href='/assets/css/style.css'>
            <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0;
                    padding: 20px;
                }
                
                .container {
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                    padding: 40px;
                    width: 100%;
                    max-width: 450px;
                }
                
                .logo {
                    text-align: center;
                    margin-bottom: 30px;
                }
                
                .logo i {
                    font-size: 48px;
                    color: #2346a9;
                }
                
                .title {
                    font-size: 24px;
                    font-weight: bold;
                    text-align: center;
                    margin-bottom: 10px;
                    color: #333;
                }
                
                .subtitle {
                    text-align: center;
                    color: #666;
                    margin-bottom: 30px;
                    line-height: 1.5;
                }
                
                .form-group {
                    margin-bottom: 20px;
                }
                
                .form-group label {
                    display: block;
                    margin-bottom: 8px;
                    font-weight: 600;
                    color: #333;
                }
                
                .form-group input {
                    width: 100%;
                    padding: 12px 16px;
                    border: 2px solid #e1e5e9;
                    border-radius: 8px;
                    font-size: 16px;
                    transition: border-color 0.3s ease;
                    box-sizing: border-box;
                }
                
                .form-group input:focus {
                    outline: none;
                    border-color: #2346a9;
                    box-shadow: 0 0 0 3px rgba(35, 70, 169, 0.1);
                }
                
                .submit-btn {
                    width: 100%;
                    background: #2346a9;
                    color: white;
                    border: none;
                    padding: 14px;
                    border-radius: 8px;
                    font-size: 16px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: background-color 0.3s ease;
                    margin-top: 10px;
                }
                
                .submit-btn:hover {
                    background: #1d357a;
                }
                
                .back-to-login {
                    text-align: center;
                    margin-top: 20px;
                }
                
                .back-to-login a {
                    color: #2346a9;
                    text-decoration: none;
                    font-weight: 500;
                }
                
                .back-to-login a:hover {
                    text-decoration: underline;
                }
                
                .alert {
                    padding: 15px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    border: 1px solid;
                }
                
                .alert-error {
                    background: #f8d7da;
                    color: #721c24;
                    border-color: #f5c6cb;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='logo'>
                    <i class='fa-solid fa-key'></i>
                </div>
                <div class='title'>Réinitialiser votre mot de passe</div>
                <div class='subtitle'>Saisissez votre nouveau mot de passe ci-dessous</div>

                <form method='POST' id='resetPasswordForm'>
                    <input type='hidden' name='token' value='$token'>
                    
                    <div class='form-group'>
                        <label for='password'>Nouveau mot de passe</label>
                        <input id='password' type='password' name='password' placeholder='Saisissez votre nouveau mot de passe' required>
                    </div>
                    
                    <div class='form-group'>
                        <label for='confirm_password'>Confirmer le mot de passe</label>
                        <input id='confirm_password' type='password' name='confirm_password' placeholder='Confirmez votre nouveau mot de passe' required>
                    </div>
                    
                    <button class='submit-btn' type='submit'>
                        <i class='fa-solid fa-key' style='margin-right: 0.5rem;'></i>
                        Réinitialiser le mot de passe
                    </button>
                </form>
                
                <div class='back-to-login'>
                    <a href='/login'>← Retour à la connexion</a>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Affiche une erreur
     */
    private function showError(string $message): void
    {
        echo "
        <!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <title>Erreur - TerrainTrack</title>
            <link rel='stylesheet' href='/assets/css/style.css'>
            <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
            <style>
                body { font-family: Arial, sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; }
                .alert-error { background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; border: 1px solid #f5c6cb; }
                .back-link { color: #2346a9; text-decoration: none; }
                .back-link:hover { text-decoration: underline; }
            </style>
        </head>
        <body>
            <div class='alert-error'>
                <i class='fa-solid fa-exclamation-circle' style='margin-right: 0.5rem;'></i>
                $message
            </div>
            <p><a href='/login' class='back-link'>← Retour à la connexion</a></p>
        </body>
        </html>
        ";
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
