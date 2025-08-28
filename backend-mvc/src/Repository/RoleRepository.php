<?php

namespace App\Repository;

use App\Entity\Role;
use PDO;

class RoleRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère un rôle par ID
     */
    public function findById(int $id): ?Role
    {
        $stmt = $this->pdo->prepare("SELECT * FROM roles WHERE id = ? AND is_active = 1");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        return $this->hydrateRole($data);
    }

    /**
     * Récupère un rôle par nom
     */
    public function findByName(string $name): ?Role
    {
        $stmt = $this->pdo->prepare("SELECT * FROM roles WHERE name = ? AND is_active = 1");
        $stmt->execute([$name]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        return $this->hydrateRole($data);
    }

    /**
     * Récupère tous les rôles
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM roles WHERE is_active = 1 ORDER BY name");
        $roles = [];
        
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $roles[] = $this->hydrateRole($data);
        }
        
        return $roles;
    }

    /**
     * Sauvegarde un rôle
     */
    public function save(Role $role): bool
    {
        if ($role->getId()) {
            return $this->update($role);
        } else {
            return $this->insert($role);
        }
    }

    /**
     * Insère un nouveau rôle
     */
    private function insert(Role $role): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO roles (name, display_name, description, permissions, is_active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $result = $stmt->execute([
            $role->getName(),
            $role->getDisplayName(),
            $role->getDescription(),
            json_encode($role->getPermissions()),
            $role->isActive() ? 1 : 0
        ]);
        
        if ($result) {
            $role->setId($this->pdo->lastInsertId());
        }
        
        return $result;
    }

    /**
     * Met à jour un rôle
     */
    private function update(Role $role): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE roles 
            SET name = ?, display_name = ?, description = ?, permissions = ?, 
                is_active = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $role->getName(),
            $role->getDisplayName(),
            $role->getDescription(),
            json_encode($role->getPermissions()),
            $role->isActive() ? 1 : 0,
            $role->getId()
        ]);
    }

    /**
     * Met à jour les permissions d'un rôle
     */
    public function updateRolePermissions(Role $role): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE roles 
            SET permissions = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        return $stmt->execute([
            json_encode($role->getPermissions()),
            $role->getId()
        ]);
    }

    /**
     * Supprime un rôle
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM roles WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Vérifie si un rôle est utilisé par des utilisateurs
     */
    public function isRoleInUse(int $roleId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_roles WHERE role_id = ?");
        $stmt->execute([$roleId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Récupère les rôles d'un utilisateur
     */
    public function getUserRoles(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT r.* 
            FROM roles r
            INNER JOIN user_roles ur ON r.id = ur.role_id
            WHERE ur.user_id = ? AND r.is_active = 1
            ORDER BY r.name
        ");
        $stmt->execute([$userId]);
        
        $roles = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $roles[] = $this->hydrateRole($data);
        }
        
        return $roles;
    }

    /**
     * Hydrate un rôle à partir des données de la base
     */
    private function hydrateRole(array $data): Role
    {
        $role = new Role();
        $role->setId($data['id']);
        $role->setName($data['name']);
        $role->setDisplayName($data['display_name']);
        $role->setDescription($data['description']);
        $role->setIsActive((bool)$data['is_active']);
        $role->setCreatedAt(new \DateTime($data['created_at']));
        $role->setUpdatedAt(new \DateTime($data['updated_at']));
        
        // Permissions
        if ($data['permissions']) {
            $permissions = json_decode($data['permissions'], true);
            $role->setPermissions($permissions);
        }
        
        return $role;
    }
} 