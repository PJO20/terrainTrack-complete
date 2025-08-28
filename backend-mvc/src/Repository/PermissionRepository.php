<?php

namespace App\Repository;

use App\Entity\Permission;
use PDO;

class PermissionRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère une permission par ID
     */
    public function findById(int $id): ?Permission
    {
        $stmt = $this->pdo->prepare("SELECT * FROM permissions WHERE id = ? AND is_active = 1");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        return $this->hydratePermission($data);
    }

    /**
     * Récupère une permission par nom
     */
    public function findByName(string $name): ?Permission
    {
        $stmt = $this->pdo->prepare("SELECT * FROM permissions WHERE name = ? AND is_active = 1");
        $stmt->execute([$name]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        return $this->hydratePermission($data);
    }

    /**
     * Récupère toutes les permissions
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM permissions WHERE is_active = 1 ORDER BY module, action");
        $permissions = [];
        
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $permissions[] = $this->hydratePermission($data);
        }
        
        return $permissions;
    }

    /**
     * Récupère les permissions par module
     */
    public function findByModule(string $module): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM permissions WHERE module = ? AND is_active = 1 ORDER BY action");
        $stmt->execute([$module]);
        $permissions = [];
        
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $permissions[] = $this->hydratePermission($data);
        }
        
        return $permissions;
    }

    /**
     * Sauvegarde une permission
     */
    public function save(Permission $permission): bool
    {
        if ($permission->getId()) {
            return $this->update($permission);
        } else {
            return $this->insert($permission);
        }
    }

    /**
     * Insère une nouvelle permission
     */
    private function insert(Permission $permission): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO permissions (name, display_name, description, module, action, is_active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $result = $stmt->execute([
            $permission->getName(),
            $permission->getDisplayName(),
            $permission->getDescription(),
            $permission->getModule(),
            $permission->getAction(),
            $permission->isActive() ? 1 : 0
        ]);
        
        if ($result) {
            $permission->setId($this->pdo->lastInsertId());
        }
        
        return $result;
    }

    /**
     * Met à jour une permission
     */
    private function update(Permission $permission): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE permissions 
            SET name = ?, display_name = ?, description = ?, module = ?, action = ?, 
                is_active = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $permission->getName(),
            $permission->getDisplayName(),
            $permission->getDescription(),
            $permission->getModule(),
            $permission->getAction(),
            $permission->isActive() ? 1 : 0,
            $permission->getId()
        ]);
    }

    /**
     * Supprime une permission
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM permissions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Hydrate une permission à partir des données de la base
     */
    private function hydratePermission(array $data): Permission
    {
        $permission = new Permission();
        $permission->setId($data['id']);
        $permission->setName($data['name']);
        $permission->setDisplayName($data['display_name']);
        $permission->setDescription($data['description']);
        $permission->setModule($data['module']);
        $permission->setAction($data['action']);
        $permission->setIsActive((bool)$data['is_active']);
        $permission->setCreatedAt(new \DateTime($data['created_at']));
        $permission->setUpdatedAt(new \DateTime($data['updated_at']));
        
        return $permission;
    }
} 