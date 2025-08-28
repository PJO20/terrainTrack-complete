<?php

namespace App\Repository;

use PDO;

class UserSettingsRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Getter pour la connexion PDO
     */
    public function getConnection(): PDO
    {
        return $this->db;
    }

    /**
     * Récupère les paramètres d'un utilisateur
     */
    public function findByUserId(int $userId): ?array
    {
        $query = "SELECT * FROM user_settings WHERE user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Met à jour les paramètres d'un utilisateur
     */
    public function updateProfile(int $userId, array $data): bool
    {
        try {
            $query = "UPDATE user_settings SET 
                        full_name = :full_name,
                        email = :email,
                        phone = :phone,
                        role = :role,
                        department = :department,
                        location = :location,
                        timezone = :timezone,
                        language = :language,
                        updated_at = CURRENT_TIMESTAMP
                      WHERE user_id = :user_id";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                'user_id' => $userId,
                'full_name' => $data['fullname'] ?? '',
                'email' => $data['email'] ?? '',
                'phone' => $data['phone'] ?? '',
                'role' => $data['role'] ?? 'operator',
                'department' => $data['department'] ?? '',
                'location' => $data['location'] ?? '',
                'timezone' => $data['timezone'] ?? 'Europe/Paris',
                'language' => $data['language'] ?? 'fr'
            ]);
            
            if ($result && $stmt->rowCount() > 0) {
                error_log("Profil utilisateur ID $userId mis à jour avec succès");
                return true;
            }
            
            // Si aucune ligne n'a été affectée, l'utilisateur n'existe peut-être pas
            // Essayons de créer un nouvel enregistrement
            if ($stmt->rowCount() === 0) {
                return $this->createProfile($userId, $data);
            }
            
            return false;
        } catch (\PDOException $e) {
            error_log("Erreur dans UserSettingsRepository::updateProfile : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crée un nouveau profil utilisateur
     */
    private function createProfile(int $userId, array $data): bool
    {
        try {
            $query = "INSERT INTO user_settings (user_id, full_name, email, phone, role, department, location, timezone, language) 
                      VALUES (:user_id, :full_name, :email, :phone, :role, :department, :location, :timezone, :language)";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                'user_id' => $userId,
                'full_name' => $data['fullname'] ?? '',
                'email' => $data['email'] ?? '',
                'phone' => $data['phone'] ?? '',
                'role' => $data['role'] ?? 'operator',
                'department' => $data['department'] ?? '',
                'location' => $data['location'] ?? '',
                'timezone' => $data['timezone'] ?? 'Europe/Paris',
                'language' => $data['language'] ?? 'fr'
            ]);
            
            if ($result) {
                error_log("Nouveau profil utilisateur ID $userId créé avec succès");
                return true;
            }
            
            return false;
        } catch (\PDOException $e) {
            error_log("Erreur dans UserSettingsRepository::createProfile : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Génère les initiales à partir du nom complet
     */
    public function generateInitials(string $fullName): string
    {
        $words = explode(' ', trim($fullName));
        $initials = '';
        
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
                if (strlen($initials) >= 2) {
                    break;
                }
            }
        }
        
        return $initials ?: 'U';
    }
} 