<?php

namespace App\Entity;

class User
{
    private int $id;
    private string $email;
    private string $password;
    private string $username;
    private string $firstName;
    private string $lastName;
    private string $avatar;
    private bool $isAdmin;
    private bool $isActive;
    private \DateTime $lastLogin;
    private \DateTime $createdAt;
    private \DateTime $updatedAt;
    private array $roles = [];
    private array $permissions = [];

    public function __construct()
    {
        $this->roles = [];
        $this->permissions = [];
        $this->username = '';
        $this->email = '';
        $this->password = '';
        $this->firstName = '';
        $this->lastName = '';
        $this->avatar = '';
        $this->isAdmin = false;
        $this->isActive = true;
        $this->lastLogin = new \DateTime();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // Getters
    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getName(): string
    {
        // Utiliser first_name et last_name si disponibles, sinon email
        if (!empty($this->firstName) && !empty($this->lastName)) {
            return trim($this->firstName . ' ' . $this->lastName);
        } elseif (!empty($this->firstName)) {
            return $this->firstName;
        } elseif (!empty($this->lastName)) {
            return $this->lastName;
        } else {
            return $this->email;
        }
    }

    public function getRole(): string
    {
        // Utiliser is_admin pour déterminer le rôle
        return $this->isAdmin ? 'admin' : 'user';
    }

    public function getFullName(): string
    {
        return $this->name;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getAvatar(): string
    {
        return $this->avatar;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getLastLogin(): \DateTime
    {
        return $this->lastLogin;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    // Setters
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function setAvatar(string $avatar): void
    {
        $this->avatar = $avatar;
    }

    public function setIsAdmin(bool $isAdmin): void
    {
        $this->isAdmin = $isAdmin;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function setLastLogin(\DateTime $lastLogin): void
    {
        $this->lastLogin = $lastLogin;
    }

    public function setUpdatedAt(\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function setPermissions(array $permissions): void
    {
        $this->permissions = $permissions;
    }

    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    // Méthodes de gestion des rôles
    public function addRole(Role $role): void
    {
        if (!in_array($role, $this->roles)) {
            $this->roles[] = $role;
        }
    }

    public function removeRole(Role $role): void
    {
        $this->roles = array_filter($this->roles, function($r) use ($role) {
            return $r->getId() !== $role->getId();
        });
    }

    public function hasRole(string $roleName): bool
    {
        foreach ($this->roles as $role) {
            if ($role->getName() === $roleName) {
                return true;
            }
        }
        return false;
    }

    public function getRoleNames(): array
    {
        return array_map(function($role) {
            return $role->getName();
        }, $this->roles);
    }

    // Méthodes de gestion des permissions
    public function addPermission(string $permission): void
    {
        if (!in_array($permission, $this->permissions)) {
            $this->permissions[] = $permission;
        }
    }

    public function removePermission(string $permission): void
    {
        $this->permissions = array_filter($this->permissions, function($p) use ($permission) {
            return $p !== $permission;
        });
    }

    public function hasPermission(string $permission): bool
    {
        // Vérifier les permissions directes
        if (in_array($permission, $this->permissions)) {
            return true;
        }

        // Vérifier les permissions via les rôles
        foreach ($this->roles as $role) {
            if (in_array($permission, $role->getPermissions())) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function canAccessModule(string $module): bool
    {
        return $this->hasPermission($module . '.access') || 
               $this->hasPermission($module . '.read') || 
               $this->hasPermission($module . '.manage');
    }

    public function canManageUsers(): bool
    {
        return $this->hasPermission('users.manage');
    }

    public function canManageRoles(): bool
    {
        return $this->hasPermission('roles.manage');
    }
}




