<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\EquippementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EquippementRepository::class)]
#[ApiResource()]

class Equippement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 25)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?int $coutMensuel = null;

    #[ORM\Column(nullable: true)]
    private ?int $coutAnnuel = null;

    #[ORM\ManyToOne(inversedBy: 'Equippement')]
    private ?Vehicule $vehicule = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCoutMensuel(): ?int
    {
        return $this->coutMensuel;
    }

    public function setCoutMensuel(?int $coutMensuel): static
    {
        $this->coutMensuel = $coutMensuel;

        return $this;
    }

    public function getCoutAnnuel(): ?int
    {
        return $this->coutAnnuel;
    }

    public function setCoutAnnuel(?int $coutAnnuel): static
    {
        $this->coutAnnuel = $coutAnnuel;

        return $this;
    }

    public function getVehicule(): ?Vehicule
    {
        return $this->vehicule;
    }

    public function setVehicule(?Vehicule $vehicule): static
    {
        $this->vehicule = $vehicule;

        return $this;
    }
}
