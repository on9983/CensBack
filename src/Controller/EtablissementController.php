<?php

namespace App\Controller;

use App\Entity\Modele;
use App\Repository\AssuranceRepository;
use App\Repository\CategorieRepository;
use App\Repository\EventRepository;
use App\Repository\FournisseurRepository;
use App\Repository\KilometrageRepository;
use App\Repository\ModeleRepository;
use App\Service\DevOnly;
use App\Service\FileUploader;
use App\Service\IdGenerator;
use App\Service\Validator;
use Illuminate\Support\Str;
use App\Entity\User;
use App\Entity\Etablissement;
use App\Entity\Vehicule;
use App\Repository\EtablissementRepository;
use App\Repository\UserRepository;
use App\Repository\VehiculeRepository;
use App\Service\FireWallTokenJWT;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWSProvider\JWSProviderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;
use Symfony\Component\Serializer\SerializerInterface;

class EtablissementController extends AbstractController
{
    public function __construct(
        private FireWallTokenJWT $fireWallTokenJWT,
        private SerializerInterface $serializer,
        private FileUploader $fileUploader,
        private KernelInterface $appKernel,
        private DevOnly $devOnly,
        private JWTTokenManagerInterface $JWTManager,
        private IdGenerator $idGenerator,
        private Validator $validator,

        private EtablissementRepository $etablissementRepository,
        private VehiculeRepository $vehiculeRepository,
        private FournisseurRepository $fournisseurRepository,
        private AssuranceRepository $assuranceRepository,
        private EventRepository $eventRepository,
        private KilometrageRepository $kilometrageRepository,
        private CategorieRepository $categorieRepository,
        private ModeleRepository $modeleRepository,
    ) {
        $this->fireWallTokenJWT = $fireWallTokenJWT;
        $this->serializer = $serializer;
        $this->fileUploader = $fileUploader;
        $this->appKernel = $appKernel;
        $this->devOnly = $devOnly;
        $this->JWTManager = $JWTManager;
        $this->idGenerator = $idGenerator;
        $this->validator = $validator;

        $this->etablissementRepository = $etablissementRepository;
        $this->vehiculeRepository = $vehiculeRepository;
        $this->fournisseurRepository = $fournisseurRepository;
        $this->assuranceRepository = $assuranceRepository;
        $this->eventRepository = $eventRepository;
        $this->kilometrageRepository = $kilometrageRepository;
        $this->categorieRepository = $categorieRepository;
        $this->modeleRepository = $modeleRepository;
    }


    #[Route('/server-be/etablissements', name: 'app_etablissements')]
    public function index(Request $request, EtablissementRepository $etablissementRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $fireWall = $this->fireWallTokenJWT->checkAccessToken($request, $data["accessToken"]);
            if ($fireWall['JWT valide']) {

                $user = $fireWall['user'];

                $etablissements = $etablissementRepository->FindByUser($user);
                //$etablissements = $etablissementRepository->findAll();

                //dd($etablissements);

                $etablissementsSlc = [];
                foreach ($etablissements as $key => $etablissement) {
                    if ($key >= $data['indexMin']) {
                        if ($key < $data['indexMax']) {
                            $image = null;
                            if ($etablissement->getImage()) {
                                $image = $this->fileUploader->convert_image_to_base64($etablissement->getImage());
                            }

                            $etablissementsSlc[] = [
                                'nom' => $etablissement->getNom(),
                                'eid' => $etablissement->getEid(),
                                'desc' => $etablissement->getDescription(),
                                'image' => $image,
                            ];
                        } else {
                            break;
                        }
                    }
                }

                return new JsonResponse([
                    'traited' => true,
                    'etablissements' => $etablissementsSlc
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

    #[Route('/server-be/etablissements/add', name: 'app_etablissements_add')]
    public function add(Request $request, EtablissementRepository $etablissementRepository, UserRepository $userRepository): JsonResponse
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

                $data = $data['espace'];

                $etablissements = $etablissementRepository->FindByUser($user);
                $etablissement = null;
                foreach ($etablissements as $etablissement_i) {
                    if ($etablissement_i->getNom() === $data['nom']) {
                        $etablissement = $etablissement_i;
                        break 1;
                    }
                }
                if (!$etablissement) {
                    $etablissement = new Etablissement();
                    $etablissement->setNom($data['nom']);
                    $etablissement->setDescription($data['desc']);

                    $etablissement->addUser($user);
                    $user->addEtablissement($etablissement);

                    // GENERATE EID

                    restart:
                    $name = bin2hex(random_bytes(8) . "_" . random_bytes(8));
                    $entities = $etablissementRepository->findAll();
                    $a = true;
                    foreach ($entities as $entity_i) {
                        if ($entity_i->getEid() === $name) {
                            $a = false;
                            break;
                        }
                    }
                    if ($a == false) {
                        goto restart;
                    } else {
                        $etablissement->setEid($name);
                    }

                    if (array_key_exists('imageData', $data)) {
                        $file = $this->fileUploader->convert_base64_to_uploadedFile_file($data['imageData']);
                        $imageNameData = $this->fileUploader->upload($file, "/user" . "/" . $user->getEmail(), "", "Photo");
                        $etablissement->setImage($imageNameData['fullFileName']);
                    }

                    $etablissementRepository->save($etablissement, true);
                    $userRepository->save($user, true);

                    return new JsonResponse([
                        'traited' => true,
                        'message' => "Etablissement ajouté avec success.",
                        'eid' => $etablissement->getEid(),
                    ]);

                } else {
                    return new JsonResponse([
                        'message' => "L'établissement déja existant.",
                        'eid' => $etablissement->getEid(),
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

    #[Route('/server-be/etablissements/suppr', name: 'app_etablissements_suppr')]
    public function suppr(Request $request, EtablissementRepository $etablissementRepository, UserRepository $userRepository): JsonResponse
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
                $etablissement = $this->fireWallTokenJWT->checkEspace($user, $data['eid']);
                if ($etablissement instanceof JsonResponse) {
                    return $etablissement;
                }

                $vehicules = $etablissement->getVehicules()->toArray();
                $etaVide = $this->fireWallTokenJWT->FindUserEspaceByName($user, "Vide");
                if (!$etaVide) {
                    $etaVide = new Etablissement();
                    $etaVide->setNom("Vide");
                    $etaVide->addUser($user);
                    $user->addEtablissement($etaVide);

                    // GENERATE EID

                    restart:
                    $name = bin2hex(random_bytes(8) . "_" . random_bytes(8));
                    $entities = $etablissementRepository->findAll();
                    $a = true;
                    foreach ($entities as $entity_i) {
                        if ($entity_i->getEid() === $name) {
                            $a = false;
                            break;
                        }
                    }
                    if ($a == false) {
                        goto restart;
                    } else {
                        $etaVide->setEid($name);
                    }

                    $etablissementRepository->save($etaVide, true);
                    $userRepository->save($user, true);
                }
                foreach ($vehicules as $vehicule) {
                    $vehicule->setEtablissement($etaVide);
                }
                if ($etablissement->getImage()) {
                    $this->fileUploader->delete($etablissement->getImage());
                }
                $etablissementRepository->remove($etablissement, true);

                return new JsonResponse([
                    'traited' => true,
                    'message' => "Etablissement supprimé avec success.",
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

    #[Route('/server-be/etablissements/exportAll', name: 'app_etablissements_exportAll')]
    public function exportAll(Request $request, EtablissementRepository $etablissementRepository, UserRepository $userRepository): JsonResponse
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

                $dataExport = [];

                $etablissements = $etablissementRepository->FindByUser($user);
                /** @var Etablissement $etablissement */
                foreach ($etablissements as $etablissement) {
                    $vehicules = $etablissement->getVehicules();
                    foreach ($vehicules->toArray() as $vehicule) {
                        $dataExport[] = [
                            "Affectation" => $etablissement->getNom() ? $etablissement->getNom() : "",
                            "Appartenance" => $vehicule->isLeasing() ? "Achat" : "Leasing",
                            "Carburant" => $vehicule->getCarburant() ? $vehicule->getCarburant() : "",
                            "Commentaire" => $vehicule->getDescFourn() ? $vehicule->getDescFourn() : "",
                            "Commentaire Appartenance" => $vehicule->getDescLeas() ? $vehicule->getDescLeas() : "",
                            "Commentaire affectation" => $vehicule->getAssuranceDesc() ? $vehicule->getAssuranceDesc() : "",
                            "Date de mise en service" => $vehicule->getDateIncorporation() ? $vehicule->getDateIncorporation()?->format('d/m/Y') : "",
                            "Date dernier contrôle technique" => $vehicule->getDateCTprev() ? $vehicule->getDateCTprev()?->format('d/m/Y') : "",
                            "Date dernière révision" => $vehicule->getDateCPprev() ? $vehicule->getDateCPprev()?->format('d/m/Y') : "",
                            "Date prochain contrôle technique" => $vehicule->getDateCT() ? $vehicule->getDateCT()?->format('d/m/Y') : "",
                            "Date prochaine révision" => $vehicule->getDateCP() ? $vehicule->getDateCP()?->format('d/m/Y') : "",
                            "Date renouvellement prévu" => $vehicule->getDateLeasFin() ? $vehicule->getDateLeasFin()?->format('d/m/Y') : "",
                            "Kilométrage (dernier saisi)" => $vehicule->getLastKilometrage() ? $vehicule->getLastKilometrage() : "",
                            "Marque" => $vehicule->getModele()?->getMarque() ? $vehicule->getModele()?->getMarque() : "",
                            "Modèle" => $vehicule->getModele()?->getNom() ? $vehicule->getModele()?->getNom() : "",
                            "Nombre de places" => $vehicule->getNbDePlace() ? $vehicule->getNbDePlace() : "",
                            "Numéro d'immatriculation" => $vehicule->getImmatriculation() ? $vehicule->getImmatriculation() : "",
                            "Permis" => $vehicule->getPermi() ? $vehicule->getPermi() : "",
                            "Type" => $vehicule->getType() ? $vehicule->getType() : "",
                            "Vid" => $vehicule->getNom() ? $vehicule->getNom() : "",
                        ];
                    }

                }

                return new JsonResponse([
                    'traited' => true,
                    'data' => $dataExport,
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

    #[Route('/server-be/etablissements/importAll', name: 'app_etablissements_importAll')]
    public function importAll(Request $request, EtablissementRepository $etablissementRepository, UserRepository $userRepository): JsonResponse
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

                $dataForm = $data['dataForm'];

                if ($dataForm['id'] === "importEtablissements") {
                    $etablissements = $etablissementRepository->FindByUser($user);

                    $vehicules = $dataForm['value'];
                    foreach ($vehicules as $champs) {
                        if (array_key_exists("Affectation", $champs)) {
                            $etablissement = null;
                            foreach ($etablissements as $etablissement_i) {
                                if ($etablissement_i->getNom() === $champs["Affectation"]) {
                                    $etablissement = $etablissement_i;
                                    break 1;
                                }
                            }
                            if (!$etablissement) {
                                $etablissement = new Etablissement();
                                $etablissement->setNom($champs["Affectation"]);
                                $user->addEtablissement($etablissement);
                                $etablissement->addUser($user);

                                // GENERATE EID

                                restart:
                                $name = bin2hex(random_bytes(8) . "_" . random_bytes(8));
                                $entities = $etablissementRepository->findAll();
                                $a = true;
                                foreach ($entities as $entity_i) {
                                    if ($entity_i->getEid() === $name) {
                                        $a = false;
                                        break 1;
                                    }
                                }
                                if ($a == false) {
                                    goto restart;
                                } else {
                                    $etablissement->setEid($name);
                                }

                                $etablissementRepository->save($etablissement, true);
                                $userRepository->save($user, true);
                                $etablissements[] = $etablissement;
                            }

                            $vehicule = null;
                            if (array_key_exists('Vid', $champs)) {
                                $vehicule = $this->vehiculeRepository->findByNom($champs['Vid']);
                            } else {
                                // if (array_key_exists('Numéro d\'immatriculation', $champs)) {
                                //     $vehicule = $this->vehiculeRepository->findOneByImmatriculation($champs['Numéro d\'immatriculation']);
                                // }
                            }
                            /** @var Vehicule $vehicule */
                            if ($vehicule) {
                                if (!$dataForm['remplace']) {
                                    continue;
                                }

                                $espace = $vehicule->getEtablissement();
                                $espaces = $user->getEtablissements();
                                $ok = false;
                                foreach ($espaces as $espace_i) {
                                    if ($espace === $espace_i) {
                                        $ok = true;
                                        break 1;
                                    }
                                }
                                if (!$ok) {
                                    return new JsonResponse([
                                        'error' => 'error',
                                        'message' => 'Ce vehicule existe déja, et ne fait pas partie de vos établissements autorisés. Immat :' . $vehicule->getImmatriculation() . ' Vid:' . $vehicule->getNom() . 'Etablisement:' . $vehicule->getEtablissement()?->getNom() . "Veuillez recommencer la démarche, sans ce vehicule, ou demandez le droit d'accès à cet établissement : " . $vehicule->getEtablissement()?->getNom(),
                                    ]);
                                }
                            }

                            if (!$vehicule) {
                                $vehicule = new Vehicule();
                                $this->idGenerator->generateNom($vehicule, $this->vehiculeRepository->findAll());
                                $vehicule->setEtablissement($etablissement);

                                if (array_key_exists("Modèle", $champs)) {
                                    $ent_ = $this->modeleRepository->findOneByNom($champs["Modèle"]);
                                    if ($ent_) {
                                        $vehicule->setModele($ent_);
                                    } else {
                                        $ent = new Modele();
                                        $ent->setNom($champs["Modèle"]);
                                        if (array_key_exists("Marque", $champs)) {
                                            $ent->setMarque($champs["Marque"]);
                                        }
                                        $this->modeleRepository->save($ent, true);
                                        $vehicule->setModele($ent);
                                    }
                                }

                                if (array_key_exists("Carburant", $champs)) {
                                    $vehicule->setCarburant($champs["Carburant"]);
                                }

                                if (array_key_exists("Commentaire", $champs)) {
                                    $vehicule->setDescFourn($champs["Commentaire"]);
                                }

                                if (array_key_exists("Commentaire Appartenance", $champs)) {
                                    $vehicule->setDescLeas($champs["Commentaire Appartenance"]);
                                }

                                if (array_key_exists("Commentaire affectation", $champs)) {
                                    $vehicule->setAssuranceDesc($champs["Commentaire affectation"]);
                                }

                                if (array_key_exists("Nombre de places", $champs)) {
                                    $vehicule->setNbDePlace($champs["Nombre de places"]);
                                }

                                if (array_key_exists("Numéro d'immatriculation", $champs)) {
                                    $vehicule->setImmatriculation($champs["Numéro d'immatriculation"]);
                                }

                                if (array_key_exists("Permis", $champs)) {
                                    $vehicule->setPermi($champs["Permis"]);
                                }

                                if (array_key_exists("Type", $champs)) {
                                    $vehicule->setType($champs["Type"]);
                                }

                                if (array_key_exists("Kilométrage (dernier saisi)", $champs)) {
                                    if ($this->validator->IntegerIsValid($champs["Kilométrage (dernier saisi)"])) {
                                        $vehicule->setLastKilometrage($champs["Kilométrage (dernier saisi)"]);
                                    }
                                }

                                if (array_key_exists("Appartenance", $champs)) {
                                    switch ($champs["Appartenance"]) {
                                        case "Achat établissement":
                                            $vehicule->setLeasing(false);
                                            break;
                                        case "Leasing":
                                            $vehicule->setLeasing(true);
                                            break;
                                        case "leasing":
                                            $vehicule->setLeasing(true);
                                            break;
                                        case "Achat":
                                            $vehicule->setLeasing(false);
                                            break;
                                        case "achat":
                                            $vehicule->setLeasing(false);
                                            break;
                                    }
                                }

                                if (array_key_exists("Date de mise en service", $champs)) {
                                    if ($this->validator->dateIsValid($champs["Date de mise en service"])) {
                                        $vehicule->setDateIncorporation(date_create_from_format('d/m/Y', $champs["Date de mise en service"]));
                                    }
                                }

                                if (array_key_exists("Date dernier contrôle technique", $champs)) {
                                    if ($this->validator->dateIsValid($champs["Date dernier contrôle technique"])) {
                                        $vehicule->setDateCTprev(date_create_from_format('d/m/Y', $champs["Date dernier contrôle technique"]));
                                    }
                                }

                                if (array_key_exists("Date dernière révision", $champs)) {
                                    if ($this->validator->dateIsValid($champs["Date dernière révision"])) {
                                        $vehicule->setDateCPprev(date_create_from_format('d/m/Y', $champs["Date dernière révision"]));
                                    }
                                }

                                if (array_key_exists("Date prochain contrôle technique", $champs)) {
                                    if ($this->validator->dateIsValid($champs["Date prochain contrôle technique"])) {
                                        $vehicule->setDateCT(date_create_from_format('d/m/Y', $champs["Date prochain contrôle technique"]));
                                    }
                                }

                                if (array_key_exists("Date prochaine révision", $champs)) {
                                    if ($this->validator->dateIsValid($champs["Date prochaine révision"])) {
                                        $vehicule->setDateCP(date_create_from_format('d/m/Y', $champs["Date prochaine révision"]));
                                    }
                                }

                                if (array_key_exists("Date renouvellement prévu", $champs)) {
                                    if ($this->validator->dateIsValid($champs["Date renouvellement prévu"])) {
                                        $vehicule->setDateLeasFin(date_create_from_format('d/m/Y', $champs["Date renouvellement prévu"]));
                                    }
                                }

                                $this->vehiculeRepository->save($vehicule, true);


                            }
                        }
                    }

                    return new JsonResponse([
                        'traited' => true,
                        'message' => "L'importation a été traité avec success.",
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


    #[Route('/server-be/etablissement', name: 'app_espace')]
    public function espaceInfo(Request $request, EtablissementRepository $etablissementRepository): JsonResponse
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

                if (array_key_exists("eid", $data['dataForm'])) {
                    $espace = $etablissementRepository->findOneBy(["eid" => $data['dataForm']['eid']]);
                } else {
                    $espace = $etablissementRepository->findOneBy(["eid" => $data['vehicule']]);
                }

                $etablissement = $this->fireWallTokenJWT->checkEspace($user, $espace->getEid());
                if ($etablissement instanceof JsonResponse) {
                    return $etablissement;
                }

                // TRAITEMENT

                switch ($data['cat']) {
                    case "set":
                        $informations = $this->espaceEditor($data['dataForm'], $espace, $user);
                        return new JsonResponse([
                            'traited' => true,
                            'data' => $informations,
                        ]);
                }

                return new JsonResponse([
                    'error' => 'error'
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


    private function espaceEditor(array $dataForm, Etablissement $espace, User $user): array
    {
        switch ($dataForm["tab"]) {
            case "LISTECARTE":
                switch ($dataForm["id"]) {
                    case 'imgData':
                        $file = $this->fileUploader->convert_base64_to_uploadedFile_file($dataForm['value']);

                        $path = "/user" . "/" . $user->getEmail() . "/" . $espace->getNom() . "/image";

                        $this->fileUploader->delete($espace->getImage());
                        $imageNameData = $this->fileUploader->upload($file, $path, "", "Photo");
                        $espace->setImage($imageNameData['fullFileName']);
                        $this->etablissementRepository->save($espace, true);
                        return ["traited" => true];

                }
        }

        return ["message" => "Champ invalide"];
    }
}
