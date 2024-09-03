<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\KilometrageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KilometrageRepository::class)]
#[ApiResource()]

class Kilometrage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column()]
    private ?int $annee = null;

    #[ORM\Column(nullable: true)]
    private ?int $janvier = null;

    #[ORM\Column(nullable: true)]
    private ?int $fevrier = null;

    #[ORM\Column(nullable: true)]
    private ?int $mars = null;

    #[ORM\Column(nullable: true)]
    private ?int $avril = null;

    #[ORM\Column(nullable: true)]
    private ?int $mai = null;

    #[ORM\Column(nullable: true)]
    private ?int $juin = null;

    #[ORM\Column(nullable: true)]
    private ?int $juillet = null;

    #[ORM\Column(nullable: true)]
    private ?int $aout = null;

    #[ORM\Column(nullable: true)]
    private ?int $septembre = null;

    #[ORM\Column(nullable: true)]
    private ?int $octobre = null;

    #[ORM\Column(nullable: true)]
    private ?int $novembre = null;

    #[ORM\Column(nullable: true)]
    private ?int $decembre = null;

    #[ORM\ManyToOne(inversedBy: 'Kilometrage')]
    private ?Vehicule $vehicule = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnnee(): ?int
    {
        return $this->annee;
    }

    public function setAnnee(int $annee): static
    {
        $this->annee = $annee;

        return $this;
    }

    public function getJanvier(): ?int
    {
        return $this->janvier;
    }

    public function setJanvier(?int $janvier): static
    {
        $this->janvier = $janvier;

        return $this;
    }

    public function getFevrier(): ?int
    {
        return $this->fevrier;
    }

    public function setFevrier(?int $fevrier): static
    {
        $this->fevrier = $fevrier;

        return $this;
    }

    public function getMars(): ?int
    {
        return $this->mars;
    }

    public function setMars(?int $mars): static
    {
        $this->mars = $mars;

        return $this;
    }

    public function getAvril(): ?int
    {
        return $this->avril;
    }

    public function setAvril(?int $avril): static
    {
        $this->avril = $avril;

        return $this;
    }

    public function getMai(): ?int
    {
        return $this->mai;
    }

    public function setMai(?int $mai): static
    {
        $this->mai = $mai;

        return $this;
    }

    public function getJuin(): ?int
    {
        return $this->juin;
    }

    public function setJuin(?int $juin): static
    {
        $this->juin = $juin;

        return $this;
    }

    public function getJuillet(): ?int
    {
        return $this->juillet;
    }

    public function setJuillet(?int $juillet): static
    {
        $this->juillet = $juillet;

        return $this;
    }

    public function getAout(): ?int
    {
        return $this->aout;
    }

    public function setAout(?int $aout): static
    {
        $this->aout = $aout;

        return $this;
    }

    public function getSeptembre(): ?int
    {
        return $this->septembre;
    }

    public function setSeptembre(?int $septembre): static
    {
        $this->septembre = $septembre;

        return $this;
    }

    public function getOctobre(): ?int
    {
        return $this->octobre;
    }

    public function setOctobre(?int $octobre): static
    {
        $this->octobre = $octobre;

        return $this;
    }

    public function getNovembre(): ?int
    {
        return $this->novembre;
    }

    public function setNovembre(?int $novembre): static
    {
        $this->novembre = $novembre;

        return $this;
    }

    public function getDecembre(): ?int
    {
        return $this->decembre;
    }

    public function setDecembre(?int $decembre): static
    {
        $this->decembre = $decembre;

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
