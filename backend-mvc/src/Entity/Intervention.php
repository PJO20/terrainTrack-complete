<?php

namespace App\Entity;

class Intervention
{
    private ?int $id = null;
    private string $technicien;
    private string $description;
    private float $latitude;
    private float $longitude;
    private string $photo;
    private ?string $created_at = null;
    private ?int $vehicleId = null;
    private ?string $type = null;
    private ?string $priority = null;
    private ?string $scheduledDate = null;
    private ?string $status = null;
    private ?string $title = null;
    private ?string $team = null;

    // ID
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    // Technicien
    public function getTechnicien(): string
    {
        return $this->technicien;
    }

    public function setTechnicien(string $technicien): void
    {
        $this->technicien = $technicien;
    }

    // Description
    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    // Latitude
    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): void
    {
        $this->latitude = $latitude;
    }

    // Longitude
    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): void
    {
        $this->longitude = $longitude;
    }

    // Photo
    public function getPhoto(): string
    {
        return $this->photo;
    }

    public function setPhoto(string $photo): void
    {
        $this->photo = $photo;
    }

    // Created_at
    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    public function setCreatedAt(string $created_at): void
    {
        $this->created_at = $created_at;
    }

    public function getVehicleId(): ?int
    {
        return $this->vehicleId;
    }

    public function setVehicleId(?int $vehicleId): void
    {
        $this->vehicleId = $vehicleId;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(?string $priority): void
    {
        $this->priority = $priority;
    }

    public function getScheduledDate(): ?string
    {
        return $this->scheduledDate;
    }

    public function setScheduledDate(?string $scheduledDate): void
    {
        $this->scheduledDate = $scheduledDate;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTeam(): ?string
    {
        return $this->team;
    }

    public function setTeam(?string $team): void
    {
        $this->team = $team;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'technicien' => $this->getTechnicien(),
            'description' => $this->getDescription(),
            'latitude' => $this->getLatitude(),
            'longitude' => $this->getLongitude(),
            'photo' => $this->getPhoto(),
            'created_at' => $this->getCreatedAt(),
            'vehicleId' => $this->getVehicleId(),
            'type' => $this->getType(),
            'priority' => $this->getPriority(),
            'scheduledDate' => $this->getScheduledDate(),
            'status' => $this->getStatus(),
            'title' => $this->getTitle(),
            'team' => $this->getTeam()
        ];
    }
}