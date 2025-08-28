<?php

namespace App\Entity;

class Notification
{
    private ?int $id = null;
    private string $title;
    private string $description;
    private string $type = 'Information';
    private string $typeClass = 'info';
    private string $icon = 'bx-info-circle';
    private ?string $relatedTo = null;
    private ?int $relatedId = null;
    private ?string $relatedType = null;
    private ?int $recipientId = null;
    private bool $isRead = false;
    private string $priority = 'medium';
    private ?\DateTime $expiresAt = null;
    private ?array $metadata = null;
    private ?\DateTime $createdAt = null;
    private ?\DateTime $updatedAt = null;

    // Constantes pour les types
    public const TYPE_INFO = 'Information';
    public const TYPE_WARNING = 'Avertissement';
    public const TYPE_ALERT = 'Alerte';
    public const TYPE_SUCCESS = 'Succès';

    // Constantes pour les classes CSS
    public const CLASS_INFO = 'info';
    public const CLASS_WARNING = 'warning';
    public const CLASS_DANGER = 'danger';
    public const CLASS_SUCCESS = 'success';

    // Constantes pour les priorités
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_CRITICAL = 'critical';

    // Constantes pour les types d'entités liées
    public const RELATED_VEHICLE = 'vehicle';
    public const RELATED_INTERVENTION = 'intervention';
    public const RELATED_TEAM = 'team';
    public const RELATED_USER = 'user';
    public const RELATED_SYSTEM = 'system';

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTypeClass(): string
    {
        return $this->typeClass;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getRelatedTo(): ?string
    {
        return $this->relatedTo;
    }

    public function getRelatedId(): ?int
    {
        return $this->relatedId;
    }

    public function getRelatedType(): ?string
    {
        return $this->relatedType;
    }

    public function getRecipientId(): ?int
    {
        return $this->recipientId;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function getExpiresAt(): ?\DateTime
    {
        return $this->expiresAt;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    // Setters
    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function setTypeClass(string $typeClass): self
    {
        $this->typeClass = $typeClass;
        return $this;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function setRelatedTo(?string $relatedTo): self
    {
        $this->relatedTo = $relatedTo;
        return $this;
    }

    public function setRelatedId(?int $relatedId): self
    {
        $this->relatedId = $relatedId;
        return $this;
    }

    public function setRelatedType(?string $relatedType): self
    {
        $this->relatedType = $relatedType;
        return $this;
    }

    public function setRecipientId(?int $recipientId): self
    {
        $this->recipientId = $recipientId;
        return $this;
    }

    public function setIsRead(bool $isRead): self
    {
        $this->isRead = $isRead;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function setPriority(string $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function setExpiresAt(?\DateTime $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function setCreatedAt(?\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // Méthodes utilitaires
    public function markAsRead(): self
    {
        return $this->setIsRead(true);
    }

    public function markAsUnread(): self
    {
        return $this->setIsRead(false);
    }

    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }
        return $this->expiresAt < new \DateTime();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'type_class' => $this->typeClass,
            'icon' => $this->icon,
            'related_to' => $this->relatedTo,
            'related_id' => $this->relatedId,
            'related_type' => $this->relatedType,
            'recipient_id' => $this->recipientId,
            'read' => $this->isRead,
            'priority' => $this->priority,
            'expires_at' => $this->expiresAt?->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'date' => $this->createdAt?->format('d/m/Y'), // Format compatible avec l'interface
        ];
    }

    /**
     * Méthodes statiques pour créer des notifications spécifiques
     */
    public static function createInfoNotification(string $title, string $description, ?string $relatedTo = null): self
    {
        $notification = new self();
        $notification->setTitle($title)
                    ->setDescription($description)
                    ->setType(self::TYPE_INFO)
                    ->setTypeClass(self::CLASS_INFO)
                    ->setIcon('bx-info-circle')
                    ->setRelatedTo($relatedTo);
        return $notification;
    }

    public static function createWarningNotification(string $title, string $description, ?string $relatedTo = null): self
    {
        $notification = new self();
        $notification->setTitle($title)
                    ->setDescription($description)
                    ->setType(self::TYPE_WARNING)
                    ->setTypeClass(self::CLASS_WARNING)
                    ->setIcon('bx-error')
                    ->setPriority(self::PRIORITY_HIGH)
                    ->setRelatedTo($relatedTo);
        return $notification;
    }

    public static function createAlertNotification(string $title, string $description, ?string $relatedTo = null): self
    {
        $notification = new self();
        $notification->setTitle($title)
                    ->setDescription($description)
                    ->setType(self::TYPE_ALERT)
                    ->setTypeClass(self::CLASS_DANGER)
                    ->setIcon('bx-error-circle')
                    ->setPriority(self::PRIORITY_CRITICAL)
                    ->setRelatedTo($relatedTo);
        return $notification;
    }

    public static function createSuccessNotification(string $title, string $description, ?string $relatedTo = null): self
    {
        $notification = new self();
        $notification->setTitle($title)
                    ->setDescription($description)
                    ->setType(self::TYPE_SUCCESS)
                    ->setTypeClass(self::CLASS_SUCCESS)
                    ->setIcon('bx-check-circle')
                    ->setRelatedTo($relatedTo);
        return $notification;
    }
} 