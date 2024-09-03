<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\VehiculeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: VehiculeRepository::class)]

class Vehicule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['read', 'write'])]
    private ?string $immatriculation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateCT = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateCP = null;

    #[ORM\Column(nullable: true)]
    private ?bool $leasing = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFinDeService = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $datePrCirculation = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateIncorporation = null;

    #[ORM\Column(nullable: true)]
    private ?string $nbDePlace = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $typeDeVignette = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $numCarteGrise = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $numCarteCarburant = null;

    #[ORM\Column(nullable: true)]
    private ?string $puissanceFiscal = null;

    #[ORM\Column(nullable: true)]
    private ?int $prixAchat = null;

    #[ORM\Column(nullable: true)]
    private ?int $coutMensuel = null;

    #[ORM\Column(nullable: true)]
    private ?int $coutAnnuel = null;

    #[ORM\Column(nullable: true)]
    private ?int $coutCO2ParKm = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?User $Conducteur = null;

    #[ORM\ManyToOne(inversedBy: 'vehicules')]
    private ?Etablissement $Etablissement = null;

    #[ORM\OneToMany(mappedBy: 'vehicule', targetEntity: Kilometrage::class, cascade: ["persist", "remove"], orphanRemoval: true)]
    private Collection $Kilometrage;

    #[ORM\ManyToOne(inversedBy: 'vehicules')]
    private ?Modele $Modele = null;

    #[ORM\ManyToOne(inversedBy: 'vehicules')]
    private ?Fournisseur $Fournisseur = null;



    #[ORM\OneToMany(mappedBy: 'vehicule', targetEntity: Equippement::class, cascade: ["persist", "remove"], orphanRemoval: true)]
    private Collection $Equippement;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $fonction = null;

    #[ORM\ManyToOne(inversedBy: 'vehicules')]
    private ?Assurance $Assurance = null;

    #[ORM\ManyToOne(inversedBy: 'vehicules')]
    private ?Categorie $Categorie = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $permi = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $carburant = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateCTprev = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateCPprev = null;

    #[ORM\OneToMany(mappedBy: 'vehicule', targetEntity: Event::class, cascade: ["persist", "remove"], orphanRemoval: true)]
    private Collection $Event;

    #[ORM\Column(nullable: true)]
    private ?int $km_init = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateValidite = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFindeValidite = null;

    #[ORM\Column(length: 1100, nullable: true)]
    private ?string $assuranceDesc = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fournDossier = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $assurDossier = null;

    #[ORM\Column(nullable: true)]
    private ?int $coutLesMensuel = null;

    #[ORM\Column(nullable: true)]
    private ?int $coutLesAnnuel = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateAchat = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateLeas = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateLeasFin = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateAcquisition = null;

    #[ORM\Column(length: 1100, nullable: true)]
    private ?string $descLeas = null;

    #[ORM\Column(length: 1100, nullable: true)]
    private ?string $descFourn = null;

    #[ORM\Column(nullable: true)]
    private ?int $lastKilometrage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;



    public function __construct()
    {
        $this->Kilometrage = new ArrayCollection();
        $this->Equippement = new ArrayCollection();
        $this->Event = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImmatriculation(): ?string
    {
        return $this->immatriculation;
    }

    public function setImmatriculation(?string $immatriculation): static
    {
        $this->immatriculation = $immatriculation;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getDateCT(): ?\DateTimeInterface
    {
        return $this->dateCT;
    }

    public function setDateCT(?\DateTimeInterface $dateCT): static
    {
        $this->dateCT = $dateCT;

        return $this;
    }

    public function getDateCP(): ?\DateTimeInterface
    {
        return $this->dateCP;
    }

    public function setDateCP(?\DateTimeInterface $dateCP): static
    {
        $this->dateCP = $dateCP;

        return $this;
    }

    public function isLeasing(): ?bool
    {
        return $this->leasing;
    }

    public function setLeasing(?bool $leasing): static
    {
        $this->leasing = $leasing;

        return $this;
    }

    public function getDateFinDeService(): ?\DateTimeInterface
    {
        return $this->dateFinDeService;
    }

    public function setDateFinDeService(?\DateTimeInterface $dateFinDeService): static
    {
        $this->dateFinDeService = $dateFinDeService;

        return $this;
    }

    public function getDatePrCirculation(): ?\DateTimeInterface
    {
        return $this->datePrCirculation;
    }

    public function setDatePrCirculation(?\DateTimeInterface $datePrCirculation): static
    {
        $this->datePrCirculation = $datePrCirculation;

        return $this;
    }

    public function getDateIncorporation(): ?\DateTimeInterface
    {
        return $this->dateIncorporation;
    }

    public function setDateIncorporation(?\DateTimeInterface $dateIncorporation): static
    {
        $this->dateIncorporation = $dateIncorporation;

        return $this;
    }

    public function getNbDePlace(): ?string
    {
        return $this->nbDePlace;
    }

    public function setNbDePlace(?string $nbDePlace): static
    {
        $this->nbDePlace = $nbDePlace;

        return $this;
    }

    public function getTypeDeVignette(): ?string
    {
        return $this->typeDeVignette;
    }

    public function setTypeDeVignette(?string $typeDeVignette): static
    {
        $this->typeDeVignette = $typeDeVignette;

        return $this;
    }

    public function getNumCarteGrise(): ?string
    {
        return $this->numCarteGrise;
    }

    public function setNumCarteGrise(?string $numCarteGrise): static
    {
        $this->numCarteGrise = $numCarteGrise;

        return $this;
    }

    public function getNumCarteCarburant(): ?string
    {
        return $this->numCarteCarburant;
    }

    public function setNumCarteCarburant(?string $numCarteCarburant): static
    {
        $this->numCarteCarburant = $numCarteCarburant;

        return $this;
    }

    public function getPuissanceFiscal(): ?string
    {
        return $this->puissanceFiscal;
    }

    public function setPuissanceFiscal(?string $puissanceFiscal): static
    {
        $this->puissanceFiscal = $puissanceFiscal;

        return $this;
    }

    public function getPrixAchat(): ?int
    {
        return $this->prixAchat;
    }

    public function setPrixAchat(?int $prixAchat): static
    {
        $this->prixAchat = $prixAchat;

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

    public function getCoutCO2ParKm(): ?int
    {
        return $this->coutCO2ParKm;
    }

    public function setCoutCO2ParKm(?int $coutCO2ParKm): static
    {
        $this->coutCO2ParKm = $coutCO2ParKm;

        return $this;
    }

    public function getConducteur(): ?User
    {
        return $this->Conducteur;
    }

    public function setConducteur(?User $Conducteur): static
    {
        $this->Conducteur = $Conducteur;

        return $this;
    }

    public function getEtablissement(): ?Etablissement
    {
        return $this->Etablissement;
    }

    public function setEtablissement(?Etablissement $Etablissement): static
    {
        $this->Etablissement = $Etablissement;

        return $this;
    }

    /**
     * @return Collection<int, Kilometrage>
     */
    public function getKilometrage(): Collection
    {
        return $this->Kilometrage;
    }

    public function addKilometrage(Kilometrage $kilometrage): static
    {
        if (!$this->Kilometrage->contains($kilometrage)) {
            $this->Kilometrage->add($kilometrage);
            $kilometrage->setVehicule($this);
        }

        return $this;
    }

    public function removeKilometrage(Kilometrage $kilometrage): static
    {
        if ($this->Kilometrage->removeElement($kilometrage)) {
            // set the owning side to null (unless already changed)
            if ($kilometrage->getVehicule() === $this) {
                $kilometrage->setVehicule(null);
            }
        }

        return $this;
    }

    public function getModele(): ?Modele
    {
        return $this->Modele;
    }

    public function setModele(?Modele $Modele): static
    {
        $this->Modele = $Modele;

        return $this;
    }

    public function getFournisseur(): ?Fournisseur
    {
        return $this->Fournisseur;
    }

    public function setFournisseur(?Fournisseur $Fournisseur): static
    {
        $this->Fournisseur = $Fournisseur;

        return $this;
    }



    /**
     * @return Collection<int, Equippement>
     */
    public function getEquippement(): Collection
    {
        return $this->Equippement;
    }

    public function addEquippement(Equippement $equippement): static
    {
        if (!$this->Equippement->contains($equippement)) {
            $this->Equippement->add($equippement);
            $equippement->setVehicule($this);
        }

        return $this;
    }

    public function removeEquippement(Equippement $equippement): static
    {
        if ($this->Equippement->removeElement($equippement)) {
            // set the owning side to null (unless already changed)
            if ($equippement->getVehicule() === $this) {
                $equippement->setVehicule(null);
            }
        }

        return $this;
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

    public function getFonction(): ?string
    {
        return $this->fonction;
    }

    public function setFonction(?string $fonction): static
    {
        $this->fonction = $fonction;

        return $this;
    }

    public function getAssurance(): ?Assurance
    {
        return $this->Assurance;
    }

    public function setAssurance(?Assurance $Assurance): static
    {
        $this->Assurance = $Assurance;

        return $this;
    }

    public function getCategorie(): ?Categorie
    {
        return $this->Categorie;
    }

    public function setCategorie(?Categorie $Categorie): static
    {
        $this->Categorie = $Categorie;

        return $this;
    }

    public function getPermi(): ?string
    {
        return $this->permi;
    }

    public function setPermi(?string $permi): static
    {
        $this->permi = $permi;

        return $this;
    }

    public function getCarburant(): ?string
    {
        return $this->carburant;
    }

    public function setCarburant(?string $carburant): static
    {
        $this->carburant = $carburant;

        return $this;
    }

    public function getDateCTprev(): ?\DateTimeInterface
    {
        return $this->dateCTprev;
    }

    public function setDateCTprev(?\DateTimeInterface $dateCTprev): static
    {
        $this->dateCTprev = $dateCTprev;

        return $this;
    }

    public function getDateCPprev(): ?\DateTimeInterface
    {
        return $this->dateCPprev;
    }

    public function setDateCPprev(?\DateTimeInterface $dateCPprev): static
    {
        $this->dateCPprev = $dateCPprev;

        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvent(): Collection
    {
        return $this->Event;
    }

    public function addEvent(Event $event): static
    {
        if (!$this->Event->contains($event)) {
            $this->Event->add($event);
            $event->setVehicule($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->Event->removeElement($event)) {
            // set the owning side to null (unless already changed)
            if ($event->getVehicule() === $this) {
                $event->setVehicule(null);
            }
        }

        return $this;
    }

    public function getKmInit(): ?int
    {
        return $this->km_init;
    }

    public function setKmInit(?int $km_init): static
    {
        $this->km_init = $km_init;

        return $this;
    }

    public function getDateValidite(): ?\DateTimeInterface
    {
        return $this->dateValidite;
    }

    public function setDateValidite(?\DateTimeInterface $dateValidite): static
    {
        $this->dateValidite = $dateValidite;

        return $this;
    }

    public function getDateFindeValidite(): ?\DateTimeInterface
    {
        return $this->dateFindeValidite;
    }

    public function setDateFindeValidite(?\DateTimeInterface $dateFindeValidite): static
    {
        $this->dateFindeValidite = $dateFindeValidite;

        return $this;
    }

    public function getAssuranceDesc(): ?string
    {
        return $this->assuranceDesc;
    }

    public function setAssuranceDesc(?string $assuranceDesc): static
    {
        $this->assuranceDesc = $assuranceDesc;

        return $this;
    }

    public function getFournDossier(): ?string
    {
        return $this->fournDossier;
    }

    public function setFournDossier(?string $fournDossier): static
    {
        $this->fournDossier = $fournDossier;

        return $this;
    }

    public function getAssurDossier(): ?string
    {
        return $this->assurDossier;
    }

    public function setAssurDossier(?string $assurDossier): static
    {
        $this->assurDossier = $assurDossier;

        return $this;
    }

    public function getCoutLesMensuel(): ?int
    {
        return $this->coutLesMensuel;
    }

    public function setCoutLesMensuel(?int $coutLesMensuel): static
    {
        $this->coutLesMensuel = $coutLesMensuel;

        return $this;
    }

    public function getCoutLesAnnuel(): ?int
    {
        return $this->coutLesAnnuel;
    }

    public function setCoutLesAnnuel(?int $coutLesAnnuel): static
    {
        $this->coutLesAnnuel = $coutLesAnnuel;

        return $this;
    }

    public function getDateAchat(): ?\DateTimeInterface
    {
        return $this->dateAchat;
    }

    public function setDateAchat(?\DateTimeInterface $dateAchat): static
    {
        $this->dateAchat = $dateAchat;

        return $this;
    }

    public function getDateLeas(): ?\DateTimeInterface
    {
        return $this->dateLeas;
    }

    public function setDateLeas(?\DateTimeInterface $dateLeas): static
    {
        $this->dateLeas = $dateLeas;

        return $this;
    }

    public function getDateLeasFin(): ?\DateTimeInterface
    {
        return $this->dateLeasFin;
    }

    public function setDateLeasFin(?\DateTimeInterface $dateLeasFin): static
    {
        $this->dateLeasFin = $dateLeasFin;

        return $this;
    }

    public function getDateAcquisition(): ?\DateTimeInterface
    {
        return $this->dateAcquisition;
    }

    public function setDateAcquisition(?\DateTimeInterface $dateAcquisition): static
    {
        $this->dateAcquisition = $dateAcquisition;

        return $this;
    }

    public function getDescLeas(): ?string
    {
        return $this->descLeas;
    }

    public function setDescLeas(?string $descLeas): static
    {
        $this->descLeas = $descLeas;

        return $this;
    }

    public function getDescFourn(): ?string
    {
        return $this->descFourn;
    }

    public function setDescFourn(?string $descFourn): static
    {
        $this->descFourn = $descFourn;

        return $this;
    }

    public function getLastKilometrage(): ?int
    {
        return $this->lastKilometrage;
    }

    public function setLastKilometrage(?int $lastKilometrage): static
    {
        $this->lastKilometrage = $lastKilometrage;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }
}
