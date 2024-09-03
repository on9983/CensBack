<?php

namespace App\Controller;

use App\Entity\Etablissement;
use App\Entity\Event;
use App\Entity\Kilometrage;
use App\Entity\Modele;
use App\Entity\User;
use App\Entity\Vehicule;
use App\Form\RegistrationFormType;
use App\Repository\EtablissementRepository;
use App\Repository\EventRepository;
use App\Repository\KilometrageRepository;
use App\Repository\ModeleRepository;
use App\Repository\UserRepository;
use App\Repository\VehiculeRepository;
use App\Security\EmailVerifier;
use App\Service\DevOnly;
use App\Service\DockerEnv;
use App\Service\FireWallTokenJWT;
use App\Service\IdGenerator;
use App\Service\SendDataToUrl;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{

    public function __construct(
        private DevOnly $devOnly,
        private FireWallTokenJWT $fireWallTokenJWT,
        private JWTTokenManagerInterface $JWTManager,
        private SendDataToUrl $sendDataToUrl,
        private IdGenerator $idGenerator,
    ) {
        $this->devOnly = $devOnly;
        $this->fireWallTokenJWT = $fireWallTokenJWT;
        $this->JWTManager = $JWTManager;
        $this->sendDataToUrl = $sendDataToUrl;
        $this->idGenerator = $idGenerator;
    }

    #[Route('/server-be/register', name: 'app_register', methods: ['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        sleep(2);
        $data = json_decode($request->getContent(), true);

        try {
            $userEmail = $data["username"];
            $userPw = $data["password"];

            $data = array(
                'username' => $data["username"],
                'password' => $data["password"]
            );
            $options = array(
                'http' => array(
                    'method' => 'POST',
                    'content' => json_encode($data),
                    'header' => "Content-Type: application/json\r\n" .
                        "Accept: application/json\r\n"
                )
            );

            $domaine = "localhost:8004";
            if (getenv('DOCKER_ENV')) {
                $domaine = "gvrAuthServ";
            }

            $context = stream_context_create($options);
            $result = file_get_contents("http://" . $domaine . "/register", false, $context);
            $response = json_decode($result);


            return new JsonResponse([
                'response' => $response
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'critique' => $this->devOnly->displayError($e->getMessage())
            ]);
        }
    }

    #[Route('/server-be/register/sendverif', name: 'app_send_verif', methods: ['POST'])]
    public function sendVerif(Request $request, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        sleep(2);
        $data = json_decode($request->getContent(), true);

        try {
            $userEmail = $data["username"];

            $data = array(
                'username' => $data["username"],
            );
            $options = array(
                'http' => array(
                    'method' => 'POST',
                    'content' => json_encode($data),
                    'header' => "Content-Type: application/json\r\n" .
                        "Accept: application/json\r\n"
                )
            );

            $domaine = "localhost:8004";
            if (getenv('DOCKER_ENV')) {
                $domaine = "gvrAuthServ";
            }

            $context = stream_context_create($options);
            $result = file_get_contents("http://" . $domaine . "/register/sendverif", false, $context);
            $response = json_decode($result);

            return new JsonResponse([
                'response' => $response
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'critique' => $this->devOnly->displayError($e->getMessage())
            ]);
        }
    }

    #[Route('/server-be/register/verif', name: 'app_verifemail', methods: ['POST'])]
    public function verifemail(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        UserRepository $userRepository,
    ): Response {
        sleep(2);
        $data = json_decode($request->getContent(), true);

        try {

            $data = array(
                'username' => $data["username"],
                'jeton' => $data["jeton"]
            );
            $options = array(
                'http' => array(
                    'method' => 'POST',
                    'content' => json_encode($data),
                    'header' => "Content-Type: application/json\r\n" .
                        "Accept: application/json\r\n"
                )
            );

            $domaine = "localhost:8004";
            if (getenv('DOCKER_ENV')) {
                $domaine = "gvrAuthServ";
            }

            $context = stream_context_create($options);
            $result = file_get_contents("http://" . $domaine . "/register/verif", false, $context);
            $response = json_decode($result, true);
            if (array_key_exists('traited', $response)) {
                $user = $userRepository->findOneByEmail($response['uid']);
                $user->setEmailVerified(true);
                $userRepository->save($user, true);
            }

            return new JsonResponse([
                'response' => $response
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'critique' => $this->devOnly->displayError($e->getMessage())
            ]);
        }
    }

    #[Route('/server-be/source', name: 'app_source', methods: ['POST'])]
    public function getUserSource(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        UserRepository $userRepository,
        EtablissementRepository $etablissementRepository,
        VehiculeRepository $vehiculeRepository,
        ModeleRepository $modeleRepository
    ): JsonResponse {


        $data = json_decode($request->getContent(), true);


        try {
            $accessToken = $data["accessToken"];
            $fireWallResult = $this->fireWallTokenJWT->checkJWT($accessToken);

            if ($fireWallResult[0]) {

                $payload = $this->JWTManager->parse($accessToken);
                $user = $userRepository->findOneByEmail($payload['username']);

                if ($user->isEmailVerified()) {

                    $response = $this->sendDataToUrl->send("/get-user-source", [
                        'username' => $payload['username'],
                    ]);
                    if (array_key_exists('traited', $response)) {
                        return new JsonResponse([
                            'traited' => true,
                            'messageCL' => 'Token bon, email bon',
                            'source' => [
                                'domaine' => $response['domaine'],
                                'nom' => $response['nom'],
                                'information' => $response['information'],
                            ]
                        ]);
                    }
                    return new JsonResponse([
                        'error' => 'error',
                    ]);
                }

                return new JsonResponse([
                    'error' => 'error',
                ]);
            } else {
                return new JsonResponse([
                    'error' => 'error',
                    'code' => $fireWallResult[3],
                    'message' => $fireWallResult[1],
                ]);
            }
            ;

        } catch (\Exception $e) {
            return new JsonResponse([
                'critique' => $this->devOnly->displayError($e->getMessage())
            ]);
        }
    }

    #[Route('/server-be/set-source', name: 'app_set-source', methods: ['POST'])]
    public function setUserSource(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        UserRepository $userRepository
    ): JsonResponse {


        $data = json_decode($request->getContent(), true);
        sleep(2);

        try {
            $accessToken = $data["accessToken"];
            $fireWallResult = $this->fireWallTokenJWT->checkJWT($accessToken);

            if ($fireWallResult[0]) {

                $payload = $this->JWTManager->parse($accessToken);
                $user = $userRepository->findOneByEmail($payload['username']);

                if ($user->isEmailVerified()) {
                    if ($data["source"]["nom"] === "pbchampagne.org") {
                        $response = $this->sendDataToUrl->send("/set-user-source", [
                            'username' => $payload['username'],
                            'source' => $data["source"],
                        ]);
                        if (array_key_exists('traited', $response)) {
                            return new JsonResponse([
                                'traited' => true,
                                'messageCL' => 'Token bon, email bon',
                                'message' => "Cette provenance est acceptée.",
                                'source' => [
                                    'domaine' => "pbchampagne.org",
                                    'nom' => "Association Papillons Blanc Champagne",
                                    'information' => "Vous avez été reconnue comme fessant partie de l'APBC, vous êtes donc autorisé à utiliser l'application.",
                                ],
                            ]);
                        }
                        return new JsonResponse([
                            'error' => 'error',
                            'message' => "Cette provenance est acceptée mais il y a une erreur.",
                        ]);
                    }
                    if ($data["source"]["nom"] === "UIMMCharlevilleCDA") {
                        $response = $this->sendDataToUrl->send("/set-user-source", [
                            'username' => $payload['username'],
                            'source' => $data["source"],
                        ]);
                        if (array_key_exists('traited', $response)) {
                            return new JsonResponse([
                                'traited' => true,
                                'messageCL' => 'Token bon, email bon',
                                'message' => "Cette provenance est acceptée.",
                                'source' => [
                                    'domaine' => "UIMM",
                                    'nom' => "UIMM",
                                    'information' => "Vous avez été reconnue comme fessant partie de l'UIMM, vous êtes donc autorisé à utiliser l'application.",
                                ],
                            ]);
                        }
                        return new JsonResponse([
                            'error' => 'error',
                            'message' => "Cette provenance est acceptée mais il y a une erreur.",
                        ]);
                    }

                    return new JsonResponse([
                        'error' => 'error',
                        'message' => "Cette provenance n'est pas acceptée.",
                    ]);
                }

                return new JsonResponse([
                    'error' => 'error',
                ]);
            } else {
                return new JsonResponse([
                    'error' => 'error',
                    'code' => $fireWallResult[3],
                    'message' => $fireWallResult[1],
                ]);
            }
            ;

        } catch (\Exception $e) {
            return new JsonResponse([
                'critique' => $this->devOnly->displayError($e->getMessage())
            ]);
        }
    }

    #[Route('/server-be/check-source', name: 'app_check-source', methods: ['POST'])]
    public function checkUserSource(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        UserRepository $userRepository,
        EtablissementRepository $etablissementRepository,
        VehiculeRepository $vehiculeRepository,
        ModeleRepository $modeleRepository,
        EventRepository $eventRepository,
        KilometrageRepository $kilometrageRepository,
    ): JsonResponse {


        $data = json_decode($request->getContent(), true);

        try {
            $accessToken = $data["accessToken"];
            $fireWallResult = $this->fireWallTokenJWT->checkJWT($accessToken);

            if ($fireWallResult[0]) {

                $payload = $this->JWTManager->parse($accessToken);
                $user = $userRepository->findOneByEmail($payload['username']);

                if ($user->isEmailVerified()) {
                    $response = $this->sendDataToUrl->send("/check-user-source", [
                        'username' => $payload['username'],
                    ]);
                    if (array_key_exists('traited', $response)) {
                        $user->setOnboardingIsCompleted(true);
                        if (!$user->isInitialised()) {

                            // CREATION ETABLISSEMENT

                            $etablissement = new Etablissement();
                            $etablissement->setNom("Exemple");
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

                            $etablissementRepository->save($etablissement, true);


                            // CREATION DE VEHICULES

                            $vehicule = new Vehicule();
                            $vehicule->setEtablissement($etablissement);
                            $vehicule->setImmatriculation("AC-123-AC");
                            $vehicule->setDateCT(date_create_from_format('d/m/Y', "01/01/2025"));
                            $vehicule->setDateCP(date_create_from_format('d/m/Y', "01/01/2025"));
                            $kilometrage = new Kilometrage();
                            $kilometrage->setAnnee(2024);
                            $kilometrage->setAout(22563);
                            $kilometrage->setJuillet(21523);
                            $kilometrage->setJuin(20143);
                            $kilometrage->setVehicule($vehicule);
                            $vehicule->setLastKilometrage(22563);
                            $vehicule->setKmInit(20143);
                            $this->idGenerator->generateNom($vehicule, $vehiculeRepository->findAll());
                            // SET Model

                            $ent_ = $modeleRepository->findOneByNom("Exemple de modèle");
                            if ($ent_) {
                                $vehicule->setModele($ent_);
                            } else {
                                $ent_ = new Modele();
                                $ent_->setNom("Exemple de modèle");
                                $ent_->setMarque("Marque1");
                                $modeleRepository->save($ent_, true);
                                $vehicule->setModele($ent_);
                            }
                            $vehiculeRepository->save($vehicule, true);
                            $kilometrageRepository->save($kilometrage,true);
                            

                            //1
                            $vehicule1 = new Vehicule();
                            $vehicule1->setEtablissement($etablissement);
                            $vehicule1->setModele($ent_);
                            $vehicule1->setImmatriculation("AB-123-AB");
                            $vehicule1->setDateCT(date_create_from_format('d/m/Y', "01/11/2026"));
                            $vehicule1->setDateCP(date_create_from_format('d/m/Y', "01/11/2026"));
                            $kilometrage = new Kilometrage();
                            $kilometrage->setAnnee(2024);
                            $kilometrage->setAout(22563);
                            $kilometrage->setJuillet(21523);
                            $kilometrage->setJuin(20143);
                            $kilometrage->setVehicule($vehicule1);
                            $vehicule1->setLastKilometrage(22563);
                            $vehicule1->setKmInit(20143);
                            $this->idGenerator->generateNom($vehicule1, $vehiculeRepository->findAll());
                            $vehiculeRepository->save($vehicule1, true);
                            $kilometrageRepository->save($kilometrage,true);

                            //2
                            $vehicule2 = new Vehicule();
                            $vehicule2->setModele($ent_);
                            $vehicule2->setImmatriculation("AA-123-AA");
                            $vehicule2->setDateCT(date_create_from_format('d/m/Y', "01/10/2026"));
                            $vehicule2->setDateCP(date_create_from_format('d/m/Y', "01/10/2026"));
                            $kilometrage = new Kilometrage();
                            $kilometrage->setAnnee(2024);
                            $kilometrage->setAout(22563);
                            $kilometrage->setJuillet(21523);
                            $kilometrage->setJuin(20143);
                            $kilometrage->setVehicule($vehicule2);
                            $vehicule2->setLastKilometrage(22563);
                            $vehicule2->setKmInit(20143);
                            $vehicule2->setStatus("En réparation...");
                            $vehicule2->setEtablissement($etablissement);
                            $event = new Event();
                            $event->setDate(date_create_from_format('d/m/Y', "01/10/2024"));
                            $event->setNom("Lavage");
                            $event->setVehicule($vehicule2);
                            $this->idGenerator->generateNom($vehicule2, $vehiculeRepository->findAll());
                            $vehiculeRepository->save($vehicule2, true);
                            $kilometrageRepository->save($kilometrage,true);
                            $eventRepository->save($event);

                            //3
                            $vehicule3 = new Vehicule();
                            $vehicule3->setModele($ent_);
                            $vehicule3->setImmatriculation("AD-123-AD");
                            $vehicule3->setDateCT(date_create_from_format('d/m/Y', "01/10/2026"));
                            $vehicule3->setDateCP(date_create_from_format('d/m/Y', "01/10/2026"));
                            $vehicule3->setEtablissement($etablissement);
                            $event = new Event();
                            $event->setDate(date_create_from_format('d/m/Y', "01/10/2024"));
                            $event->setNom("Lavage");
                            $event->setVehicule($vehicule3);
                            $this->idGenerator->generateNom($vehicule3, $vehiculeRepository->findAll());
                            $vehiculeRepository->save($vehicule3, true);
                            $eventRepository->save($event);

                            //4
                            $vehicule4 = new Vehicule();
                            $vehicule4->setModele($ent_);
                            $vehicule4->setImmatriculation("AY-123-AY");
                            $vehicule4->setDateCT(date_create_from_format('d/m/Y', "01/10/2026"));
                            $vehicule4->setEtablissement($etablissement);
                            $this->idGenerator->generateNom($vehicule4, $vehiculeRepository->findAll());
                            $vehiculeRepository->save($vehicule4, true);

                            //5
                            $vehicule5 = new Vehicule();
                            $vehicule5->setModele($ent_);
                            $vehicule5->setImmatriculation("AE-123-AE");
                            $vehicule5->setDateCT(date_create_from_format('d/m/Y', "01/10/2022"));
                            $vehicule5->setDateCP(date_create_from_format('d/m/Y', "01/10/2022"));
                            $vehicule5->setEtablissement($etablissement);
                            $this->idGenerator->generateNom($vehicule5, $vehiculeRepository->findAll());
                            $vehiculeRepository->save($vehicule5, true);

                            //6
                            $vehicule6 = new Vehicule();
                            $vehicule6->setModele($ent_);
                            $vehicule6->setImmatriculation("AF-123-AF");
                            $vehicule6->setDateCT(date_create_from_format('d/m/Y', "30/09/2026"));
                            $vehicule6->setDateCP(date_create_from_format('d/m/Y', "30/09/2026"));
                            $vehicule6->setEtablissement($etablissement);
                            $this->idGenerator->generateNom($vehicule6, $vehiculeRepository->findAll());
                            $vehiculeRepository->save($vehicule6, true);

                            //7
                            $vehicule7 = new Vehicule();
                            $vehicule7->setModele($ent_);
                            $vehicule7->setImmatriculation("AG-123-AG");
                            $vehicule7->setDateCT(date_create_from_format('d/m/Y', "30/10/2024"));
                            $vehicule7->setDateCP(date_create_from_format('d/m/Y', "30/10/2024"));
                            $vehicule7->setStatus("RETIRED");
                            $vehicule7->setEtablissement($etablissement);
                            $this->idGenerator->generateNom($vehicule7, $vehiculeRepository->findAll());
                            $vehiculeRepository->save($vehicule7, true);

                            //8
                            $vehicule8 = new Vehicule();
                            $vehicule8->setModele($ent_);
                            $vehicule8->setImmatriculation("AH-123-AH");
                            $vehicule8->setDateCT(date_create_from_format('d/m/Y', "01/10/2024"));
                            $vehicule8->setDateCP(date_create_from_format('d/m/Y', "01/10/2024"));
                            $vehicule8->setStatus("RETIRED");
                            $vehicule8->setEtablissement($etablissement);
                            $this->idGenerator->generateNom($vehicule8, $vehiculeRepository->findAll());
                            $vehiculeRepository->save($vehicule8, true);

                            $user->setInitialised(true);
                            $userRepository->save($user, true);
                        }
                        $userRepository->save($user, true);

                        return new JsonResponse([
                            'traited' => true,
                            'messageCL' => 'Token bon, email bon',
                            'message' => "Votre compte est autorisé à utiliser l'application.",
                        ]);
                    }

                    return new JsonResponse([
                        'error' => 'error',
                        'message' => "Cette provenance n'est pas acceptée.",
                    ]);
                }

                return new JsonResponse([
                    'error' => 'error',
                ]);
            } else {
                return new JsonResponse([
                    'error' => 'error',
                    'code' => $fireWallResult[3],
                    'message' => $fireWallResult[1],
                ]);
            }
            ;

        } catch (\Exception $e) {
            return new JsonResponse([
                'critique' => $this->devOnly->displayError($e->getMessage())
            ]);
        }
    }

}
