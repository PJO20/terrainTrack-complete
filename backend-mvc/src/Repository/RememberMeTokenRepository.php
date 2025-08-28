<?php

namespace App\Repository;

use PDO;
use App\Service\Database;

class RememberMeTokenRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connect();
    }

    /**
     * Ajoute un token pour un utilisateur
     */
    public function addToken(int $userId, string $token, string $expiresAt): bool
    {
        $sql = "INSERT INTO user_remember_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => $expiresAt
        ]);
    }

    /**
     * Trouve un token valide
     */
    public function findValidToken(string $token): ?array
    {
        $sql = "SELECT * FROM user_remember_tokens WHERE token = :token AND expires_at > NOW() LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Supprime un token
     */
    public function deleteToken(string $token): void
    {
        $sql = "DELETE FROM user_remember_tokens WHERE token = :token";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['token' => $token]);
    }

    /**
     * Supprime tous les tokens d'un utilisateur (optionnel)
     */
    public function deleteAllTokensForUser(int $userId): void
    {
        $sql = "DELETE FROM user_remember_tokens WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
    }
} 