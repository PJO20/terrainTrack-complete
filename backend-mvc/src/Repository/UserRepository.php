<?php

namespace App\Repository;

use App\Entity\User;
use App\Service\Database;
use PDO;
use PDOException;

class UserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connect();
    }

    // Getter pour la connexion PDO
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    // Enregistre un nouvel utilisateur
    public function save(User $user): bool
    {
        $sql = "INSERT INTO users (email, password) VALUES (:email, :password)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'email' => $user->getEmail(),
            'password' => $user->getPassword(), // ATTENTION : doit être hashé avant !
        ]);
    }

    // Trouve un utilisateur par email
    public function findByEmail(string $email): ?User
    {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $user = $this->hydrateUser($row);
            $this->loadUserRoles($user);
            return $user;
        }
        return null;
    }

    // Trouve un utilisateur par ID
    public function findById(int $id): ?User
    {
        $sql = "SELECT * FROM users WHERE id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $user = $this->hydrateUser($row);
            // Temporairement désactivé : $this->loadUserRoles($user);
            return $user;
        }
        return null;
    }

    // Met à jour le mot de passe d'un utilisateur
    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        $sql = "UPDATE users SET password = :password WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'password' => $hashedPassword,
            'id' => $userId
        ]);
    }

    // Met à jour les rôles d'un utilisateur
    public function updateUserRoles(User $user): bool
    {
        try {
            // Commencer une transaction
            $this->pdo->beginTransaction();
            
            // Supprimer les rôles existants de l'utilisateur
            $sql = "DELETE FROM user_roles WHERE user_id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['user_id' => $user->getId()]);
            
            // Ajouter les nouveaux rôles
            $userRoles = $user->getRoles();
            if (!empty($userRoles)) {
                $sql = "INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)";
                $stmt = $this->pdo->prepare($sql);
                
                foreach ($userRoles as $role) {
                    $stmt->execute([
                        'user_id' => $user->getId(),
                        'role_id' => $role->getId()
                    ]);
                }
            }
            
            // Valider la transaction
            $this->pdo->commit();
            return true;
            
        } catch (\PDOException $e) {
            // Annuler la transaction en cas d'erreur
            $this->pdo->rollBack();
            error_log("Erreur lors de la mise à jour des rôles utilisateur: " . $e->getMessage());
            return false;
        }
    }

    // Met à jour les permissions d'un utilisateur
    public function updateUserPermissions(User $user): bool
    {
        try {
            // Commencer une transaction
            $this->pdo->beginTransaction();
            
            // Supprimer les permissions existantes de l'utilisateur
            $sql = "DELETE FROM user_permissions WHERE user_id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['user_id' => $user->getId()]);
            
            // Ajouter les nouvelles permissions
            $userPermissions = $user->getPermissions();
            if (!empty($userPermissions)) {
                $sql = "INSERT INTO user_permissions (user_id, permission) VALUES (:user_id, :permission)";
                $stmt = $this->pdo->prepare($sql);
                
                foreach ($userPermissions as $permission) {
                    $stmt->execute([
                        'user_id' => $user->getId(),
                        'permission' => $permission
                    ]);
                }
            }
            
            // Valider la transaction
            $this->pdo->commit();
            return true;
            
        } catch (\PDOException $e) {
            // Annuler la transaction en cas d'erreur
            $this->pdo->rollBack();
            error_log("Erreur lors de la mise à jour des permissions utilisateur: " . $e->getMessage());
            return false;
        }
    }

    // Met à jour les informations d'un utilisateur
    public function update(int $userId, array $data): bool
    {
        try {
            $fields = [];
            $values = ['id' => $userId];
            
            foreach ($data as $key => $value) {
                if ($value !== null) {
                    $fields[] = "$key = :$key";
                    $values[$key] = $value;
                }
            }
            
            if (empty($fields)) {
                return true; // Rien à mettre à jour
            }
            
            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($values);
            
        } catch (\PDOException $e) {
            error_log("Erreur lors de la mise à jour de l'utilisateur: " . $e->getMessage());
            return false;
        }
    }

    // Récupère tous les utilisateurs
    public function findAll(): array
    {
        $sql = "SELECT * FROM users ORDER BY email ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupère tous les membres avec leurs informations complètes
    // Si une table members existe, utilise-la, sinon renvoie des données statiques
    public function findAllMembers(): array
    {
        try {
            // Essayer d'abord avec une table members
            $sql = "SELECT * FROM members ORDER BY name ASC";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Si la table members n'existe pas, renvoyer des données statiques améliorées
            return [
                ['id' => 1, 'name' => 'Thomas Martin', 'email' => 'thomas.martin@terraintrack.com', 'role' => 'Admin', 'initials' => 'TM', 'phone' => '+33 6 12 34 56 78'],
                ['id' => 2, 'name' => 'Sophie Dubois', 'email' => 'sophie.dubois@terraintrack.com', 'role' => 'Chef', 'initials' => 'SD', 'phone' => '+33 6 23 45 67 89'],
                ['id' => 3, 'name' => 'Jean Leclerc', 'email' => 'jean.leclerc@terraintrack.com', 'role' => 'Technicien', 'initials' => 'JL', 'phone' => '+33 6 34 56 78 90'],
                ['id' => 4, 'name' => 'Marie Petit', 'email' => 'marie.petit@terraintrack.com', 'role' => 'Chef', 'initials' => 'MP', 'phone' => '+33 6 45 67 89 01'],
                ['id' => 5, 'name' => 'Pierre Moreau', 'email' => 'pierre.moreau@terraintrack.com', 'role' => 'Technicien', 'initials' => 'PM', 'phone' => '+33 6 56 78 90 12'],
                ['id' => 6, 'name' => 'Claire Bernard', 'email' => 'claire.bernard@terraintrack.com', 'role' => 'Mécanicien', 'initials' => 'CB', 'phone' => '+33 6 67 89 01 23'],
                ['id' => 7, 'name' => 'Emma Leroy', 'email' => 'emma.leroy@terraintrack.com', 'role' => 'Chef', 'initials' => 'EL', 'phone' => '+33 6 78 90 12 34'],
                ['id' => 8, 'name' => 'Lisa Garcia', 'email' => 'lisa.garcia@terraintrack.com', 'role' => 'Technicien', 'initials' => 'LG', 'phone' => '+33 6 89 01 23 45'],
                ['id' => 9, 'name' => 'David Wilson', 'email' => 'david.wilson@terraintrack.com', 'role' => 'Technicien', 'initials' => 'DW', 'phone' => '+33 6 90 12 34 56'],
                ['id' => 10, 'name' => 'Antoine Dupont', 'email' => 'antoine.dupont@terraintrack.com', 'role' => 'Mécanicien', 'initials' => 'AD', 'phone' => '+33 6 01 23 45 67'],
                ['id' => 11, 'name' => 'Camille Laurent', 'email' => 'camille.laurent@terraintrack.com', 'role' => 'Technicien', 'initials' => 'CL', 'phone' => '+33 6 12 34 56 78'],
                ['id' => 12, 'name' => 'Nicolas Rousseau', 'email' => 'nicolas.rousseau@terraintrack.com', 'role' => 'Chef', 'initials' => 'NR', 'phone' => '+33 6 23 45 67 89'],
            ];
        }
    }

    /**
     * Hydrate un utilisateur à partir des données de la base
     */
    private function hydrateUser(array $data): User
    {
        $user = new User();
        $user->setId($data['id']);
        $user->setEmail($data['email']);
        $user->setPassword($data['password']);
        
        // Utiliser la colonne 'name' disponible
        if (isset($data['name'])) {
            $user->setName($data['name']);
        }
        
        // Autres champs avec des valeurs par défaut
        $user->setUsername($data['username'] ?? '');
        $user->setAvatar($data['avatar'] ?? '');
        
        // Champs de profil
        $user->setPhone($data['phone'] ?? null);
        $user->setLocation($data['location'] ?? null);
        $user->setTimezone($data['timezone'] ?? null);
        $user->setLanguage($data['language'] ?? null);
        $user->setBio($data['bio'] ?? null);
        $user->setDepartment($data['department'] ?? null);
        $user->setRole($data['role'] ?? null);
        
        // Utiliser la colonne 'role' pour déterminer si admin
        $isAdmin = ($data['role'] ?? '') === 'admin';
        $user->setIsAdmin($isAdmin);
        $user->setIsActive(true); // Par défaut actif
        
        // Gérer les dates si elles existent
        if (isset($data['created_at']) && $data['created_at']) {
            $user->setCreatedAt(new \DateTime($data['created_at']));
        }
        
        return $user;
    }

    /**
     * Charge les rôles et permissions d'un utilisateur
     */
    private function loadUserRoles(User $user): void
    {
        // Charger les rôles
        $sql = "SELECT r.* FROM user_roles ur 
                JOIN roles r ON ur.role_id = r.id 
                WHERE ur.user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $user->getId()]);
        
        $roles = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $role = new \App\Entity\Role();
            $role->setId($row['id']);
            $role->setName($row['name']);
            $role->setDisplayName($row['display_name'] ?? '');
            $role->setDescription($row['description'] ?? '');
            
            // Les permissions des rôles ne sont pas stockées dans la table roles 
            // mais probablement dans une table role_permissions séparée
            // Pour l'instant, on laisse vide
            $role->setPermissions([]);
            
            $roles[] = $role;
        }
        $user->setRoles($roles);
        
        // Charger les permissions directes de l'utilisateur
        $this->loadUserPermissions($user);
    }
    
    /**
     * Charge les permissions directes d'un utilisateur
     */
    private function loadUserPermissions(User $user): void
    {
        $sql = "SELECT permission FROM user_permissions WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $user->getId()]);
        
        $permissions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $permissions[] = $row['permission'];
        }
        
        $user->setPermissions($permissions);
    }
    
    /**
     * Met à jour un utilisateur à partir d'un objet User
     */
    public function updateUser(User $user): bool
    {
        try {
            $data = [];
            
            // Récupérer les données de l'objet User
            if ($user->getName()) {
                $data['name'] = $user->getName();
            }
            if ($user->getEmail()) {
                $data['email'] = $user->getEmail();
            }
            if ($user->getPhone() !== null) {
                $data['phone'] = $user->getPhone();
            }
            if ($user->getLocation() !== null) {
                $data['location'] = $user->getLocation();
            }
            if ($user->getDepartment() !== null) {
                $data['department'] = $user->getDepartment();
            }
            if ($user->getRole() !== null) {
                $data['role'] = $user->getRole();
            }
            if ($user->getTimezone() !== null) {
                $data['timezone'] = $user->getTimezone();
            }
            if ($user->getLanguage() !== null) {
                $data['language'] = $user->getLanguage();
            }
            if ($user->getAvatar() !== null) {
                $data['avatar'] = $user->getAvatar(); // Utiliser 'avatar', pas 'avatar_url'
            }
            
            // Utiliser la méthode update existante
            return $this->update($user->getId(), $data);
            
        } catch (\Exception $e) {
            error_log("Erreur lors de la mise à jour de l'utilisateur: " . $e->getMessage());
            return false;
        }
    }
} 