<?php

namespace Entity;

class Intervention
{
    private ?int $id = null;
    private string $technicien;
    private string $description;
    private float $latitude;
    private float $longitude;
    private string $photo;
    private ?string $created_at = null;

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
}