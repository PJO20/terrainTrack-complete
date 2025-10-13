<?php

declare(strict_types=1);

namespace App\Service;

class TokenService
{
    /**
     * Génère un token de réinitialisation de mot de passe sécurisé
     */
    public function generateResetToken(): string
    {
        // Générer un token cryptographiquement sécurisé
        $bytes = random_bytes(32);
        return bin2hex($bytes);
    }

    /**
     * Génère un token CSRF sécurisé
     */
    public function generateCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        
        return $token;
    }

    /**
     * Valide un token CSRF
     */
    public function validateCsrfToken(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Génère un token d'activation de compte
     */
    public function generateActivationToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Génère un token de vérification d'email
     */
    public function generateEmailVerificationToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
