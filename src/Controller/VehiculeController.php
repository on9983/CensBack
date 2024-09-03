<?php

namespace App\Controller;

use App\Entity\Assurance;
use App\Entity\Categorie;
use App\Entity\Equippement;
use App\Entity\Event;
use App\Entity\Fournisseur;
use App\Entity\Kilometrage;
use App\Entity\Modele;
use App\Entity\User;
use App\Entity\Vehicule;
use App\Repository\AssuranceRepository;
use App\Repository\CategorieRepository;
use App\Repository\EquippementRepository;
use App\Repository\EventRepository;
use App\Repository\FournisseurRepository;
use App\Repository\KilometrageRepository;
use App\Repository\ModeleRepository;
use App\Repository\UserRepository;
use App\Repository\VehiculeRepository;
use App\Service\DateVerifieur;
use App\Service\mongoDb\MongoClient;
use App\Service\Validator;
use App\Service\DevOnly;
use App\Service\FileUploader;
use App\Service\FireWallTokenJWT;
use App\Service\IdGenerator;
use MongoDB\Collection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class VehiculeController extends AbstractController
{
    public function __construct(
        private MongoClient $mongoClient,
        private FireWallTokenJWT $fireWallTokenJWT,
        private SerializerInterface $serializer,
        private DevOnly $devOnly,
        private FileUploader $fileUploader,
        private IdGenerator $idGenerator,
        private Validator $validator,

        private VehiculeRepository $vehiculeRepository,
        private FournisseurRepository $fournisseurRepository,
        private AssuranceRepository $assuranceRepository,
        private EventRepository $eventRepository,
        private KilometrageRepository $kilometrageRepository,
        private CategorieRepository $categorieRepository,
        private ModeleRepository $modeleRepository,
        private EquippementRepository $equippementRepository,
    ) {
        $this->mongoClient = $mongoClient;
        $this->fireWallTokenJWT = $fireWallTokenJWT;
        $this->serializer = $serializer;
        $this->devOnly = $devOnly;
        $this->fileUploader = $fileUploader;
        $this->idGenerator = $idGenerator;
        $this->validator = $validator;

        $this->vehiculeRepository = $vehiculeRepository;
        $this->fournisseurRepository = $fournisseurRepository;
        $this->assuranceRepository = $assuranceRepository;
        $this->eventRepository = $eventRepository;
        $this->kilometrageRepository = $kilometrageRepository;
        $this->categorieRepository = $categorieRepository;
        $this->modeleRepository = $modeleRepository;
        $this->equippementRepository = $equippementRepository;

    }


    #[Route('/server-be/vehicules', name: 'app_vehicules')]
    public function index(
        Request $request,
        VehiculeRepository $vehiculeRepo,
        EventRepository $eventRepository,
        DateVerifieur $dateVerifieur
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        try {
            $fireWall = $this->fireWallTokenJWT->checkAccessToken($request, $data["accessToken"]);
            if ($fireWall instanceof JsonResponse) {
                return $fireWall;
            }
            if ($fireWall['JWT valide']) {
                /** @var User $user */
                $user = $fireWall['user'];

                $etablissement = $this->fireWallTokenJWT->checkEspace($user, $data['espace']);
                if ($etablissement instanceof JsonResponse) {
                    return $etablissement;
                }

                //VEHICULES

                $vehicules = $vehiculeRepo->FindByEtablissement($etablissement);

                if ($vehicules !== []) {
                    $lines[0] = ["", "Marque/Modèle/Immatricule", "Statut", "Urgence", "Tâche à faire"];
                    $img[] = [];
                    /** @var Vehicule $vehicule */
                    foreach ($vehicules as $key => $vehicule) {

                        // STATUT

                        if ($vehicule->getStatus() === null) {
                            $statut = "OK";
                        } else {
                            $statut = $vehicule->getStatus();
                        }

                        if ($statut === "RETIRED") {
                            $urgence = "RETIRED";
                            $tacheAFaire = ["Véhicule déactivé"];
                        } else {

                            $tacheAFaire = [];
                            $urgenceMaxLevel = 0;

                            // URGENCE CT Mecanique
                            $info = $dateVerifieur->TacheAFaireGen("Contrôle technique mécanique", $vehicule->getDateCT(), $tacheAFaire, $urgenceMaxLevel);
                            $tacheAFaire = $info["tacheAFaire"];
                            $urgenceMaxLevel = $info["urgenceMaxLevel"];

                            // URGENCE CT Pollution
                            $info = $dateVerifieur->TacheAFaireGen("Contrôle de pollution", $vehicule->getDateCP(), $tacheAFaire, $urgenceMaxLevel);
                            $tacheAFaire = $info["tacheAFaire"];
                            $urgenceMaxLevel = $info["urgenceMaxLevel"];

                            // URGENCE DES EVENTS
                            $events = $eventRepository->findBy(['vehicule' => $vehicule->getId()]);
                            foreach ($events as $event) {
                                if (!$event->isEffectued()) {
                                    $info = $dateVerifieur->TacheAFaireGen($event->getNom(), $event->getDate(), $tacheAFaire, $urgenceMaxLevel);
                                    $tacheAFaire = $info["tacheAFaire"];
                                    $urgenceMaxLevel = $info["urgenceMaxLevel"];
                                }
                            }

                            if ($tacheAFaire === []) {
                                $tacheAFaire = "OK";
                            }

                            // Résultante des urgences

                            $urgence = $dateVerifieur->ConvertToString($urgenceMaxLevel);
                            if ($urgence === "PERIMED") {
                                $statut = "PERIMED";
                            }

                        }



                        // AFFICHAGE DES INFORMATIONS

                        $lines[$vehicule->getNom()] = [
                            0 => "",
                            1 => $vehicule->getModele()?->getMarque() . "/ " . $vehicule->getModele()?->getNom() . "/ " . $vehicule->getImmatriculation(),
                            2 => $statut,
                            3 => $urgence,
                            4 => $tacheAFaire,
                        ];

                        $image = null;
                        if ($vehicule->getImage()) {
                            $image = $this->fileUploader->convert_image_to_base64($vehicule->getImage());
                        }

                        $img[$vehicule->getNom()] = [
                            'titre' => $vehicule->getImmatriculation(),
                            'imgUrl' => $image,
                            'imgAlt' => "Image d'un vehicule.",
                            'desc' => "Image d'un vehicule.",
                        ];

                    }


                    return new JsonResponse([
                        'traited' => true,
                        'data' => [
                            "vehicules" => $lines,
                            "images" => $img,
                        ]
                    ]);
                } else {
                    return new JsonResponse([
                        'traited' => true,
                        'data' => [
                            "vehicules" => [],
                            "images" => [],
                        ]
                    ]);
                }
            }

            return new JsonResponse([
                'error' => 'error'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'critique' => $this->devOnly->displayError($e->getMessage())
            ]);
        }
    }

    #[Route('/server-be/vehicules/add', name: 'app_vehicules_add')]
    public function add(Request $request, VehiculeRepository $vehiculeRepository, ModeleRepository $modeleRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        try {
            $fireWall = $this->fireWallTokenJWT->checkAccessToken($request, $data["accessToken"]);
            if ($fireWall instanceof JsonResponse) {
                return $fireWall;
            }
            if ($fireWall['JWT valide']) {
                /** @var User $user */
                $user = $fireWall['user'];

                $etablissement = $this->fireWallTokenJWT->checkEspace($user, $data['espace']);
                if ($etablissement instanceof JsonResponse) {
                    return $etablissement;
                }

                $data = $data['dataForm'];

                // FORM DATA

                $vehicule = new Vehicule();
                $vehicule->setEtablissement($etablissement);

                // SET Model

                $ent_ = $this->modeleRepository->findOneByNom($data['model']);
                if ($ent_) {
                    $vehicule->setModele($ent_);
                } else {
                    $ent = new Modele();
                    $ent->setNom($data['model']);
                    $ent->setMarque($data['marque']);
                    $this->modeleRepository->save($ent, true);
                    $vehicule->setModele($ent);
                }

                // ID

                $this->idGenerator->generateNom($vehicule, $vehiculeRepository->findAll());

                // IMAGE

                if (array_key_exists('imageData', $data)) {
                    $file = $this->fileUploader->convert_base64_to_uploadedFile_file($data['imgData']);
                    $imageNameData = $this->fileUploader->upload($file, "/user" . "/" . $user->getEmail() . "/vehicules", "", "Photo");
                    $vehicule->setImage($imageNameData['fullFileName']);
                }

                //SAVE

                $vehiculeRepository->save($vehicule, true);

                return new JsonResponse([
                    'traited' => true,
                    'message' => "Véhicule ajouté avec success.",
                    'vid' => $vehicule->getNom(),
                ]);

            }

            return new JsonResponse([
                'error' => 'error'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'critique' => $this->devOnly->displayError($e->getMessage())
            ]);
        }
    }

    #[Route('/server-be/vehicules/suppr', name: 'app_vehicules_suppr')]
    public function suppr(Request $request, VehiculeRepository $vehiculeRepository, UserRepository $userRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $fireWall = $this->fireWallTokenJWT->checkAccessToken($request, $data["accessToken"]);
            if ($fireWall instanceof JsonResponse) {
                return $fireWall;
            }
            if ($fireWall['JWT valide']) {
                /** @var User $user */
                $user = $fireWall['user'];
                $vehicule = $vehiculeRepository->findOneBy(['nom' => $data['vid']]);
                $etablissement = $this->fireWallTokenJWT->checkEspace($user, $vehicule->getEtablissement()->getEid());
                if ($etablissement instanceof JsonResponse) {
                    return $etablissement;
                }

                if ($vehicule->getImage()) {
                    $this->fileUploader->delete($vehicule->getImage());
                }
                $vehiculeRepository->remove($vehicule, true);

                return new JsonResponse([
                    'traited' => true,
                    'message' => "Vehicule supprimé avec success.",
                ]);

            }

            return new JsonResponse([
                'error' => 'error'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'critique' => $this->devOnly->displayError($e->getMessage())
            ]);
        }
    }

    #[Route('/server-be/vehicule', name: 'app_vehicule')]
    public function vehiculeInfo(Request $request, VehiculeRepository $vehiculeRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $fireWall = $this->fireWallTokenJWT->checkAccessToken($request, $data["accessToken"]);
            if ($fireWall instanceof JsonResponse) {
                return $fireWall;
            }
            if ($fireWall['JWT valide']) {
                /** @var User $user */
                $user = $fireWall['user'];

                $vehicule = $vehiculeRepository->findOneBy(["nom" => $data['vehicule']]);

                $etablissement = $this->fireWallTokenJWT->checkEspace($user, $vehicule->getEtablissement()?->getEid());
                if ($etablissement instanceof JsonResponse) {
                    return $etablissement;
                }


                // TRAITEMENT

                switch ($data['cat']) {
                    case "set":
                        $informations = $this->vehiculeEditor($data['dataForm'], $vehicule, $user);
                        return new JsonResponse([
                            'traited' => true,
                            'data' => $informations,
                        ]);
                    case "info":
                        $conducteur = $vehicule->getConducteur()?->getEmail();
                        $image = null;
                        if ($vehicule->getImage()) {
                            $image = $this->fileUploader->convert_image_to_base64($vehicule->getImage());
                        }
                        $informations = [
                            "Image" => [
                                'titre' => $vehicule->getImmatriculation(),
                                'imgUrl' => $image,
                                'imgAlt' => "Image d'un vehicule.",
                                'desc' => "Image d'un vehicule.",
                            ],
                            "Administratif" => [
                                "Fonction" => $vehicule->getFonction(),
                                "Incorporation" => $vehicule->getDateIncorporation()?->format('d/m/Y'),
                                "Pr. Circulation" => $vehicule->getDatePrCirculation()?->format('d/m/Y'),
                                "Puis. Fiscal" => $vehicule->getPuissanceFiscal(),
                                "Immatricule" => $vehicule->getImmatriculation(),
                                "Carte grise" => $vehicule->getNumCarteGrise(),
                                "Carte Carbu." => $vehicule->getNumCarteCarburant(),
                                "Fournisseur" => $vehicule->getFournisseur()?->getNom(),
                                "Assureur" => $vehicule->getAssurance()?->getNom(),
                            ],
                            "Caractéristique" => [
                                "Catégorie" => $vehicule->getCategorie()?->getNom(),
                                "Type" => $vehicule->getType(),
                                "Permi" => $vehicule->getPermi(),
                                "Marque" => $vehicule->getModele()?->getMarque(),
                                "Model" => $vehicule->getModele()?->getNom(),
                                "Co2 au km" => $vehicule->getCoutCO2ParKm(),
                                "Carburant" => $vehicule->getCarburant(),
                                "Crit'Air" => $vehicule->getTypeDeVignette(),
                                "Nb de places" => $vehicule->getNbDePlace(),
                            ],
                            "Statut" => [
                                "Conducteur" => $conducteur,
                                "Statut" => $vehicule->getStatus(),
                            ],
                        ];
                        return new JsonResponse([
                            'traited' => true,
                            'data' => $informations,
                        ]);
                    case "plan":
                        $events = [];
                        foreach ($vehicule->getEvent()?->toArray() as $event) {
                            $events[] = [
                                "nom" => $event->getNom(),
                                "date" => $event->getDate()?->format('d/m/Y'),
                                "dateF" => $event->getDataEffectif()?->format('d/m/Y'),
                                "type" => $event->getType(),
                                "frais" => $event->getFrais(),
                                "effectued" => $event->isEffectued(),
                            ];
                        }
                        ;
                        $events = array_reverse($events);
                        $informations = [
                            "Évènements" => [
                                "entity" => "Event",
                                "historiques" => $events,
                            ],

                            "Contrôle" => [
                                "Contrôles effectués" => [
                                    "Technique" => $vehicule->getDateCTprev()?->format('d/m/Y'),
                                    "Pollution" => $vehicule->getDateCPprev()?->format('d/m/Y'),
                                ],
                                "Contrôles prévu" => [
                                    "Technique" => $vehicule->getDateCT()?->format('d/m/Y'),
                                    "Pollution" => $vehicule->getDateCP()?->format('d/m/Y'),
                                ],
                            ],
                        ];
                        return new JsonResponse([
                            'traited' => true,
                            'data' => $informations,
                        ]);
                    case "km":
                        $kilometrages = [];
                        foreach ($vehicule->getKilometrage()?->toArray() as $kilometrage) {
                            $kilometrages[$kilometrage->getAnnee()] = [
                                "Janvier" => $kilometrage->getJanvier(),
                                "Février" => $kilometrage->getFevrier(),
                                "Mars" => $kilometrage->getMars(),
                                "Avril" => $kilometrage->getAvril(),
                                "Mai" => $kilometrage->getMai(),
                                "Juin" => $kilometrage->getJuin(),
                                "Juillet" => $kilometrage->getJuillet(),
                                "Aout" => $kilometrage->getAout(),
                                "Septembre" => $kilometrage->getSeptembre(),
                                "Octobre" => $kilometrage->getOctobre(),
                                "Novembre" => $kilometrage->getNovembre(),
                                "Décembre" => $kilometrage->getDecembre(),
                            ];
                        }
                        ;
                        $informations = [
                            "Informations" => [
                                "Km (maximum saisi)" => $vehicule->getLastKilometrage(),
                                "Km initial" => $vehicule->getKmInit(),
                                "Incorporation" => $vehicule->getDateIncorporation()?->format('d/m/Y'),
                                "Pr. Circulation" => $vehicule->getDatePrCirculation()?->format('d/m/Y'),
                            ],
                            "Kilometrages" => $kilometrages,
                        ];
                        return new JsonResponse([
                            'traited' => true,
                            'data' => $informations,
                        ]);
                    case "assur":
                        $informations = [
                            "Assureur" => [
                                "Nom de l'assureur" => $vehicule->getAssurance()?->getNom(),
                                "n° de dossier" => $vehicule->getAssurDossier(),
                            ],
                            "Tarif" => [
                                "Coût mensuel" => $vehicule->getCoutMensuel(),
                                "Coût annuel" => $vehicule->getCoutAnnuel(),
                                "Début de validité" => $vehicule->getDateValidite()?->format('d/m/Y'),
                                "Fin de validité" => $vehicule->getDateFindeValidite()?->format('d/m/Y'),
                            ],
                            "Description" => $vehicule->getAssuranceDesc(),
                        ];
                        return new JsonResponse([
                            'traited' => true,
                            'data' => $informations,
                        ]);
                    case "achat":
                        if ($vehicule->isLeasing()) {
                            $isLeasing = "leasing";
                        } else {
                            $isLeasing = "achat";
                        }
                        ;
                        $informations = [
                            "Fournisseur" => [
                                "Nom du Fournisseur" => $vehicule->getFournisseur()?->getNom(),
                                "Le n° de siret" => $vehicule->getFournisseur()?->getSiret(),
                                "Le n° de dossier" => $vehicule->getFournDossier(),
                                "Téléphone" => $vehicule->getFournisseur()?->getTelephone(),
                            ],
                            "Acquisition" => [
                                "Type d'acquisition" => $isLeasing,
                                "Prix d'achat" => $vehicule->getPrixAchat(),
                                "Coût mensuel" => $vehicule->getCoutLesMensuel(),
                                "Coût annuel" => $vehicule->getCoutLesAnnuel(),
                                "Début du leasing" => $vehicule->getDateLeas()?->format('d/m/Y'),
                                "Fin du leasing" => $vehicule->getDateLeasFin()?->format('d/m/Y'),
                                "Date d'achat" => $vehicule->getDateAchat()?->format('d/m/Y'),
                                "Acquisition du véh." => $vehicule->getDateAcquisition()?->format('d/m/Y'),
                            ],
                            "Description du leasing" => $vehicule->getDescFourn(),
                        ];
                        return new JsonResponse([
                            'traited' => true,
                            'data' => $informations,
                        ]);
                    case "equip":
                        $data = $this->mongoClient->GetVehData($vehicule->getNom());
                        //dd($data['data']);
                        if ($data) {
                            $tableau_vide = [
                                "0" => ["", "", "", "", "", ""],
                                "1" => ["", "", "", "", "", ""],
                                "2" => ["", "", "", "", "", ""],
                                "3" => ["", "", "", "", "", ""],
                                "4" => ["", "", "", "", "", ""],
                                "5" => ["", "", "", "", "", ""],
                                "6" => ["", "", "", "", "", ""],
                                "7" => ["", "", "", "", "", ""],
                                "8" => ["", "", "", "", "", ""],
                                "9" => ["", "", "", "", "", ""],
                                "10" => ["", "", "", "", "", ""],
                                "11" => ["", "", "", "", "", ""],
                            ];
                            $info = [];
                            foreach ($data['data'] as $document => $tableau) {
                                if ($tableau) {
                                    $info[$document] = array_replace_recursive($tableau_vide, $tableau);
                                } else {
                                    $info[$document] = null;
                                }

                            }
                        } else {
                            $info = null;
                        }

                        //dd($info);
                        $informations = [
                            "Informations" => $info,
                        ];
                        return new JsonResponse([
                            'traited' => true,
                            'data' => $informations,
                        ]);
                }

                return new JsonResponse([
                    'error' => 'error9'
                ]);
            }

            return new JsonResponse([
                'error' => 'error8'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'critique' => $this->devOnly->displayError($e->getMessage())
            ]);
        }
    }


    private function vehiculeEditor(array $dataForm, Vehicule $vehicule, User $user): array
    {
        switch ($dataForm["tab"]) {
            case "INFO": {
                switch ($dataForm["id"]) {
                    case 'imgData': {
                        $file = $this->fileUploader->convert_base64_to_uploadedFile_file($dataForm['value']);

                        $path = "/user" . "/" . $user->getEmail() . "/vehicules";

                        $this->fileUploader->delete($vehicule->getImage());
                        $imageNameData = $this->fileUploader->upload($file, $path, "", "Photo");
                        $vehicule->setImage($imageNameData['fullFileName']);
                        $this->vehiculeRepository->save($vehicule, true);
                        return ["traited" => true];
                    }
                    case ["Statut", "Statut"]: {
                        $vehicule->setStatus($dataForm['value']);

                        $this->vehiculeRepository->save($vehicule, true);
                        return ["traited" => true];
                    }
                    case ["Administratif", "Fonction"]: {
                        $vehicule->setFonction($dataForm['value']);
                        $this->vehiculeRepository->save($vehicule, true);
                        return ["traited" => true];
                    }
                    case ["Administratif", "Incorporation"]: {
                        if ($this->validator->dateIsValid($dataForm['value'])) {
                            $vehicule->setDateIncorporation(date_create_from_format('d/m/Y', $dataForm['value']));
                            $this->vehiculeRepository->save($vehicule, true);
                            return ["traited" => true];
                        }
                        return ["error" => "error", "message" => "Date invalide. Doit être au format dd/mm/aaaa."];
                    }
                    case ["Administratif", "Pr. Circulation"]: {
                        if ($this->validator->dateIsValid($dataForm['value'])) {
                            $vehicule->setDatePrCirculation(date_create_from_format('d/m/Y', $dataForm['value']));
                            $this->vehiculeRepository->save($vehicule, true);
                            return ["traited" => true];
                        }
                        return ["error" => "error", "message" => "Date invalide. Doit être au format dd/mm/aaaa."];
                    }
                    case ["Administratif", "Puis. Fiscal"]: {
                        $vehicule->setPuissanceFiscal($dataForm['value']);

                        $this->vehiculeRepository->save($vehicule, true);
                        return ["traited" => true];
                    }
                    case ["Administratif", "Immatricule"]: {
                        $vehicule->setImmatriculation($dataForm['value']);

                        $this->vehiculeRepository->save($vehicule, true);
                        return ["traited" => true];
                    }
                    case ["Administratif", "Carte grise"]: {
                        $vehicule->setNumCarteGrise($dataForm['value']);

                        $this->vehiculeRepository->save($vehicule, true);
                        return ["traited" => true];
                    }
                    case ["Administratif", "Carte Carbu."]: {
                        $vehicule->setNumCarteCarburant($dataForm['value']);

                        $this->vehiculeRepository->save($vehicule, true);
                        return ["traited" => true];
                    }
                    case ["Administratif", "Fournisseur"]: {
                        $ent_ = $this->fournisseurRepository->findOneByNom($dataForm['value']);
                        if ($ent_) {
                            $vehicule->setFournisseur($ent_);
                        } else {
                            $ent = new Fournisseur();
                            $ent->setNom($dataForm['value']);
                            $this->fournisseurRepository->save($ent, true);
                            $vehicule->setFournisseur($ent);
                        }
                        $this->vehiculeRepository->save($vehicule, true);
                        return ["traited" => true];
                    }
                    case ["Administratif", "Assureur"]: {
                        $ent_ = $this->assuranceRepository->findOneByNom($dataForm['value']);
                        if ($ent_) {
                            $vehicule->setAssurance($ent_);
                        } else {
                            $ent = new Assurance();
                            $ent->setNom($dataForm['value']);
                            $this->assuranceRepository->save($ent, true);
                            $vehicule->setAssurance($ent);
                        }
                        $this->vehiculeRepository->save($vehicule, true);
                        return ["traited" => true];
                    }
                    case ["Caracteristiques", "Catégorie"]: {
                        $ent_ = $this->categorieRepository->findOneByNom($dataForm['value']);
                        if ($ent_) {
                            $vehicule->setCategorie($ent_);
                        } else {
                            $ent = new Categorie();
                            $ent->setNom($dataForm['value']);
                            $this->categorieRepository->save($ent, true);
                            $vehicule->setCategorie($ent);
                        }
                        $this->vehiculeRepository->save($vehicule, true);
                        return ["traited" => true];
                    }
                    case ["Caracteristiques", "Type"]: {
                        $vehicule->setType($dataForm['value']);

                        $this->vehiculeRepository->save($vehicule, true);
                        return ["traited" => true];
                    }
                    case ["Caracteristiques", "Permi"]: {
                        $vehicule->setPermi($dataForm['value']);

                        $this->vehiculeRepository->save($vehicule, true);
                        return ["traited" => true];
                    }
                    case ["Caracteristiques", "Marque"]: {
                        $ent_ = $vehicule->getModele();
                        if ($ent_) {
                            $ent_->setMarque($dataForm['value']);
                            $this->modeleRepository->save($ent_, true);
                            $this->vehiculeRepository->save($vehicule, true);
                            return ["traited" => true];
                        }
                        return [
                            "error" => "error",
                            "message" => "Veuillez indiquez le nom du model avant."
                        ];
                    }
                    case ["Caracteristiques", "Model"]: {
                        $ent_ = $this->modeleRepository->findOneByNom($dataForm['value']);
                        if ($ent_) {
                            $vehicule->setModele($ent_);
                        } else {
                            $ent = new Modele();
                            $ent->setNom($dataForm['value']);
                            $this->modeleRepository->save($ent, true);
                            $vehicule->setModele($ent);
                        }
                        $this->vehiculeRepository->save($vehicule, true);
                        return ["traited" => true];
                    }
                    case ["Caracteristiques", "Co2 au km"]: {
                        $vehicule->setCoutCO2ParKm($dataForm['value']);

                        $this->vehiculeRepository->save($vehicule, true);
                        return ["traited" => true];
                    }
                    case ["Caracteristiques", "Carburant"]: {
                        $vehicule->setCarburant($dataForm['value']);

                        $this->vehiculeRepository->save($vehicule, true);
                        return ["traited" => true];
                    }
                    case ["Caracteristiques", "Crit'Air"]: {
                        $vehicule->setTypeDeVignette($dataForm['value']);

                        $this->vehiculeRepository->save($vehicule, true);
                        return ["traited" => true];
                    }
                    case ["Caracteristiques", "Nb de places"]: {
                        $vehicule->setNbDePlace($dataForm['value']);

                        $this->vehiculeRepository->save($vehicule, true);
                        return ["traited" => true];
                    }

                }
                break;
            }
            case "KM": {
                if (array_key_exists('année', $dataForm)) {
                    if (!$this->validator->AnneeIsValid($dataForm['année'])) {
                        return ["error" => "error", "message" => "Année invalide. Doit être au format aaaa."];
                    }
                    if (!$this->validator->IntegerIsValid($dataForm['value'])) {
                        return ["error" => "error", "message" => "Kilometrage invalide. Doit être un nombre entier."];
                    }

                    $ents = $vehicule->getKilometrage();
                    $ent_ = null;
                    foreach ($ents->toArray() as $ent) {
                        if ($ent->getAnnee() === (int) $dataForm['année']) {
                            $ent_ = $ent;
                            break;
                        }
                    }
                    if (!$ent_) {
                        $ent_ = new Kilometrage();
                        $ent_->setAnnee($dataForm['année']);
                        $vehicule->addKilometrage($ent_);
                    }
                    switch ($dataForm['id']) {
                        case "Janvier":
                            $ent_->setJanvier($dataForm['value']);
                            break;
                        case "Février":
                            $ent_->setFevrier($dataForm['value']);
                            break;
                        case "Mars":
                            $ent_->setMars($dataForm['value']);
                            break;
                        case "Avril":
                            $ent_->setAvril($dataForm['value']);
                            break;
                        case "Mai":
                            $ent_->setMai($dataForm['value']);
                            break;
                        case "Juin":
                            $ent_->setJuin($dataForm['value']);
                            break;
                        case "Juillet":
                            $ent_->setJuillet($dataForm['value']);
                            break;
                        case "Aout":
                            $ent_->setAout($dataForm['value']);
                            break;
                        case "Septembre":
                            $ent_->setSeptembre($dataForm['value']);
                            break;
                        case "Octobre":
                            $ent_->setOctobre($dataForm['value']);
                            break;
                        case "Novembre":
                            $ent_->setNovembre($dataForm['value']);
                            break;
                        case "Décembre":
                            $ent_->setDecembre($dataForm['value']);
                            break;
                    }
                    if ($vehicule->getLastKilometrage() < $dataForm['value']) {
                        $vehicule->setLastKilometrage($dataForm['value']);
                    }
                    $this->kilometrageRepository->save($ent_, true);
                    return ["traited" => true];
                }
                switch ($dataForm["id"]) {
                    case "Km (maximum saisi)":
                        if ($this->validator->IntegerIsValid($dataForm['value'])) {
                            $vehicule->setLastKilometrage($dataForm['value']);
                            $this->vehiculeRepository->save($vehicule, true);
                            return ["traited" => true];
                        }
                        return ["error" => "error", "message" => "Valeur numérique invalide.Doit être une valeur entière."];
                    case "Km initial":
                        if ($this->validator->IntegerIsValid($dataForm['value'])) {
                            $vehicule->setKmInit($dataForm['value']);
                            $this->vehiculeRepository->save($vehicule, true);
                            return ["traited" => true];
                        }
                        return ["error" => "error", "message" => "Valeur numérique invalide.Doit être une valeur entière."];
                    case "Incorporation":
                        if ($this->validator->dateIsValid($dataForm['value'])) {
                            $vehicule->setDateIncorporation(date_create_from_format('d/m/Y', $dataForm['value']));

                            $this->vehiculeRepository->save($vehicule, true);
                            return ["traited" => true];
                        }
                        return ["error" => "error", "message" => "Date invalide. Doit être au format dd/mm/aaaa."];
                    case "Pr. Circulation":
                        if ($this->validator->dateIsValid($dataForm['value'])) {
                            $vehicule->setDatePrCirculation(date_create_from_format('d/m/Y', $dataForm['value']));

                            $this->vehiculeRepository->save($vehicule, true);
                            return ["traited" => true];
                        }
                        return ["error" => "error", "message" => "Date invalide. Doit être au format dd/mm/aaaa."];
                }
                break;
            }
            case "ACHAT": {
                switch ($dataForm["id"]) {
                    case ["Fournisseur", "Nom du Fournisseur"]:
                        $ent_ = $this->fournisseurRepository->findOneByNom($dataForm['value']);
                        if ($ent_) {
                            $vehicule->setFournisseur($ent_);
                        } else {
                            $ent = new Fournisseur();
                            $ent->setNom($dataForm['value']);
                            $this->fournisseurRepository->save($ent, true);
                            $vehicule->setFournisseur($ent);
                        }
                        $this->vehiculeRepository->save($vehicule, true);
                        return ["traited" => true];

                    case ["Fournisseur", "Le n° de siret"]:
                        $ent_ = $vehicule->getFournisseur();
                        if ($ent_) {
                            $ent_->setSiret($dataForm['value']);
                            $this->fournisseurRepository->save($ent_, true);

                            $this->vehiculeRepository->save($vehicule, true);
                            return ["traited" => true];
                        } else {
                            return [
                                "error" => "error",
                                "message" => "Veuillez indiquez le nom du fournisseur avant."
                            ];
                        }

                    case ["Fournisseur", "Le n° de dossier"]:
                        $vehicule->setFournDossier($dataForm['value']);

                        $this->vehiculeRepository->save($vehicule, true);
                        return ["traited" => true];

                    case ["Fournisseur", "Téléphone"]:
                        $ent_ = $vehicule->getFournisseur();
                        if ($ent_) {
                            $ent_->setTelephone($dataForm['value']);
                            $this->fournisseurRepository->save($ent_, true);

                            $this->vehiculeRepository->save($vehicule, true);
                            return ["traited" => true];
                        } else {
                            return [
                                "error" => "error",
                                "message" => "Veuillez indiquez le nom du fournisseur avant."
                            ];
                        }

                    case "Description du leasing":
                        $vehicule->setDescFourn($dataForm['value']);

                        $this->vehiculeRepository->save($vehicule, true);
                        return ["traited" => true];

                    case ["Acquisition", "Type d'acquisition"]:
                        if ($dataForm['value'] === "leasing" || $dataForm['value'] === "Leasing") {
                            $vehicule->setLeasing(true);
                            $this->vehiculeRepository->save($vehicule, true);
                            return ["traited" => true];
                        }
                        if ($dataForm['value'] === "Achat" || $dataForm['value'] === "achat") {
                            $vehicule->setLeasing(false);
                            $this->vehiculeRepository->save($vehicule, true);
                            return ["traited" => true];
                        }

                        return [
                            "error" => "error",
                            "message" => "Veuillez indiquez 'achat' ou 'leasing'."
                        ];

                    case ["Acquisition", "Prix d'achat"]:
                        if ($this->validator->NumberIsValid($dataForm['value'])) {
                            $vehicule->setPrixAchat($dataForm['value']);

                            $this->vehiculeRepository->save($vehicule, true);
                            return ["traited" => true];
                        }
                        return ["error" => "error", "message" => "Valeur numérique invalide. Doit contenir des nombres avec un point comme virgule."];

                    case ["Acquisition", "Coût mensuel"]:
                        if ($this->validator->NumberIsValid($dataForm['value'])) {
                            $vehicule->setCoutLesMensuel($dataForm['value']);

                            $this->vehiculeRepository->save($vehicule, true);
                            return ["traited" => true];
                        }
                        return ["error" => "error", "message" => "Valeur numérique invalide. Doit contenir des nombres avec un point comme virgule."];

                    case ["Acquisition", "Coût annuel"]:
                        if ($this->validator->NumberIsValid($dataForm['value'])) {
                            $vehicule->setCoutLesAnnuel($dataForm['value']);

                            $this->vehiculeRepository->save($vehicule, true);
                            return ["traited" => true];
                        }
                        return ["error" => "error", "message" => "Valeur numérique invalide. Doit contenir des nombres avec un point comme virgule."];

                    case ["Acquisition", "Début du leasing"]:
                        if ($this->validator->dateIsValid($dataForm['value'])) {
                            $vehicule->setDateLeas(date_create_from_format('d/m/Y', $dataForm['value']));

                            $this->vehiculeRepository->save($vehicule, true);
                            return ["traited" => true];
                        }
                        return ["error" => "error", "message" => "Date invalide. Doit être au format dd/mm/aaaa."];


                    case ["Acquisition", "Fin du leasing"]:
                        if ($this->validator->dateIsValid($dataForm['value'])) {
                            $vehicule->setDateLeasFin(date_create_from_format('d/m/Y', $dataForm['value']));

                            $this->vehiculeRepository->save($vehicule, true);
                            return ["traited" => true];
                        }
                        return ["error" => "error", "message" => "Date invalide. Doit être au format dd/mm/aaaa."];


                    case ["Acquisition", "Date d'achat"]:
                        if ($this->validator->dateIsValid($dataForm['value'])) {
                            $vehicule->setDateAchat(date_create_from_format('d/m/Y', $dataForm['value']));

                            $this->vehiculeRepository->save($vehicule, true);
                            return ["traited" => true];
                        }
                        return ["error" => "error", "message" => "Date invalide. Doit être au format dd/mm/aaaa."];


                    case ["Acquisition", "Acquisition du véh."]:
                        if ($this->validator->dateIsValid($dataForm['value'])) {
                            $vehicule->setDateAcquisition(date_create_from_format('d/m/Y', $dataForm['value']));

                            $this->vehiculeRepository->save($vehicule, true);
                            return ["traited" => true];
                        }
                        return ["error" => "error", "message" => "Date invalide. Doit être au format dd/mm/aaaa."];

                }
                break;
            }
            case "ASSUR": {
                switch ($dataForm["id"]) {
                    case ["Assureur", "Nom de l'assureur"]:
                        $ent_ = $this->assuranceRepository->findOneByNom($dataForm['value']);
                        if ($ent_) {
                            $vehicule->setAssurance($ent_);
                        } else {
                            $ent = new Assurance();
                            $ent->setNom($dataForm['value']);
                            $this->assuranceRepository->save($ent, true);
                            $vehicule->setAssurance($ent);
                        }
                        $this->vehiculeRepository->save($vehicule, true);
                        return ["traited" => true];

                    case ["Assureur", "n° de dossier"]:
                        $vehicule->setAssurDossier($dataForm['value']);

                        $this->vehiculeRepository->save($vehicule, true);
                        return ["traited" => true];

                    case "Description du l'assurance":
                        $vehicule->setAssuranceDesc($dataForm['value']);

                        $this->vehiculeRepository->save($vehicule, true);
                        return ["traited" => true];

                    case ["Tarif", "Coût mensuel"]:
                        if ($this->validator->NumberIsValid($dataForm['value'])) {
                            $vehicule->setCoutMensuel($dataForm['value']);

                            $this->vehiculeRepository->save($vehicule, true);
                            return ["traited" => true];
                        }
                        return ["error" => "error", "message" => "Valeur numérique invalide. Doit contenir des nombres avec un point comme virgule."];

                    case ["Tarif", "Coût annuel"]:
                        if ($this->validator->NumberIsValid($dataForm['value'])) {
                            $vehicule->setCoutAnnuel($dataForm['value']);

                            $this->vehiculeRepository->save($vehicule, true);
                            return ["traited" => true];
                        }
                        return ["error" => "error", "message" => "Valeur numérique invalide. Doit contenir des nombres avec un point comme virgule."];

                    case ["Tarif", "Début de validité"]:
                        if ($this->validator->dateIsValid($dataForm['value'])) {
                            $vehicule->setDateValidite(date_create_from_format('d/m/Y', $dataForm['value']));
                            $this->vehiculeRepository->save($vehicule, true);
                            return ["traited" => true];
                        }
                        return ["error" => "error", "message" => "Date invalide. Doit être au format dd/mm/aaaa."];

                    case ["Tarif", "Fin de validité"]:
                        if ($this->validator->dateIsValid($dataForm['value'])) {
                            $vehicule->setDateFindeValidite(date_create_from_format('d/m/Y', $dataForm['value']));
                            $this->vehiculeRepository->save($vehicule, true);
                            return ["traited" => true];
                        }
                        return ["error" => "error", "message" => "Date invalide. Doit être au format dd/mm/aaaa."];
                }
                break;
            }
            case "PLAN": {
                if (array_key_exists('addEvent', $dataForm)) {
                    if ($this->validator->dateIsValid($dataForm['addEvent']['date'])) {
                        $ent_ = new Event();
                        $ent_->setNom($dataForm['addEvent']['nom']);
                        $ent_->setDate(date_create_from_format('d/m/Y', $dataForm['addEvent']['date']));
                        if ($dataForm['addEvent']['frais'] !== "") {
                            if ($this->validator->NumberIsValid($dataForm['addEvent']['frais'])) {
                                $ent_->setFrais($dataForm['addEvent']['frais']);
                            } else {
                                return ["error" => "error", "message" => "Valeur numérique invalide. Doit contenir des nombres avec un point comme virgule."];
                            }
                        }
                        $this->eventRepository->save($ent_, true);

                        $vehicule->addEvent($ent_);
                        $this->vehiculeRepository->save($vehicule, true);
                        return ["traited" => true];
                    }
                    return ["error" => "error", "message" => "Date invalide. Doit être au format dd/mm/aaaa."];
                }
                if (array_key_exists('id', $dataForm) && $dataForm['value'] !== null) {
                    if ($dataForm["id"][0] === "Event") {
                        $myId = $dataForm["id"][1];
                        $ent_ = null;
                        if ($vehicule->getEvent()) {
                            $ent_ = array_reverse($vehicule->getEvent()?->toArray())[$myId];
                        }

                        if ($ent_) {
                            $events = $vehicule->getEvent();
                            $ok = false;
                            foreach ($events->toArray() as $event) {
                                if ($event === $ent_) {
                                    $ok = true;
                                    break;
                                }
                            }
                            if ($ok) {
                                switch ($dataForm["id"][2]) {
                                    case "effectued":
                                        $ent_->setEffectued($dataForm['value']);
                                        $this->eventRepository->save($ent_, true);
                                        return ["traited" => true];
                                    case "nom":
                                        $ent_->setNom($dataForm['value']);
                                        $this->eventRepository->save($ent_, true);
                                        return ["traited" => true];
                                    case "date":
                                        if ($this->validator->dateIsValid($dataForm['value'])) {
                                            $ent_->setDate(date_create_from_format('d/m/Y', $dataForm['value']));
                                            $this->eventRepository->save($ent_, true);
                                            return ["traited" => true];
                                        }
                                        return ["error" => "error", "message" => "Date invalide. Doit être au format dd/mm/aaaa."];

                                    case "dateF":
                                        if ($this->validator->dateIsValid($dataForm['value'])) {
                                            $ent_->setDataEffectif(date_create_from_format('d/m/Y', $dataForm['value']));
                                            $this->eventRepository->save($ent_, true);
                                            return ["traited" => true];
                                        }
                                        return ["error" => "error", "message" => "Date invalide. Doit être au format dd/mm/aaaa."];

                                    case "type":
                                        $ent_->setType($dataForm['value']);
                                        $this->eventRepository->save($ent_, true);
                                        return ["traited" => true];
                                    case "frais":
                                        if ($this->validator->NumberIsValid($dataForm['value'])) {
                                            $ent_->setFrais($dataForm['value']);
                                            $this->eventRepository->save($ent_, true);
                                            return ["traited" => true];
                                        }
                                        return ["error" => "error", "message" => "Valeur numérique invalide. Doit contenir des nombres avec un point comme virgule."];

                                }

                            }

                        }
                        return ["error" => "error2"];
                    }
                    switch ($dataForm["id"]) {
                        case ["Contrôles effectués", "Technique"]:
                            if ($this->validator->dateIsValid($dataForm['value'])) {
                                $vehicule->setDateCTprev(date_create_from_format('d/m/Y', $dataForm['value']));

                                $this->vehiculeRepository->save($vehicule, true);
                                return ["traited" => true];
                            }
                            return ["error" => "error", "message" => "Date invalide. Doit être au format dd/mm/aaaa."];


                        case ["Contrôles effectués", "Pollution"]:
                            if ($this->validator->dateIsValid($dataForm['value'])) {
                                $vehicule->setDateCPprev(date_create_from_format('d/m/Y', $dataForm['value']));

                                $this->vehiculeRepository->save($vehicule, true);
                                return ["traited" => true];
                            }
                            return ["error" => "error", "message" => "Date invalide. Doit être au format dd/mm/aaaa."];


                        case ["Contrôles prévu", "Technique"]:
                            if ($this->validator->dateIsValid($dataForm['value'])) {
                                $vehicule->setDateCT(date_create_from_format('d/m/Y', $dataForm['value']));

                                $this->vehiculeRepository->save($vehicule, true);
                                return ["traited" => true];
                            }
                            return ["error" => "error", "message" => "Date invalide. Doit être au format dd/mm/aaaa."];

                        case ["Contrôles prévu", "Technique"]:
                            if ($this->validator->dateIsValid($dataForm['value'])) {
                                $vehicule->setDateCT(date_create_from_format('d/m/Y', $dataForm['value']));

                                $this->vehiculeRepository->save($vehicule, true);
                                return ["traited" => true];
                            }
                            return ["error" => "error", "message" => "Date invalide. Doit être au format dd/mm/aaaa."];

                    }

                }
                break;
            }
            case "EQUIP"; {
                $this->mongoClient->SetVehData($vehicule->getNom(), $dataForm['document'], $dataForm['id'][0], $dataForm['id'][1], $dataForm['value']);
                return ["traited" => true];
            }
        }

        return ["message" => "Champ invalide"];
    }


}
