<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserADcheckType;
use App\Repository\UserRepository;
use App\Service\AuthServConnector;
use App\Service\DevOnly;
use App\Service\FireWallTokenJWT;
use App\Service\LdapConnector;
use App\Service\RetardFunction;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWSProvider\JWSProviderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Serializer\SerializerInterface;

class LoginController extends AbstractController
{


    public function __construct(
        private SerializerInterface $serializerInterface,
        private JWTTokenManagerInterface $JWTManager,
        private FireWallTokenJWT $fireWallTokenJWT,
        private JWSProviderInterface $jwsProvider,
        private TokenStorageInterface $tokenStorageInterface,
        private DevOnly $devOnly,
        private UserRepository $userRepository,
        private RetardFunction $retardFunction,

    ) {
        $this->serializerInterface = $serializerInterface;
        $this->JWTManager = $JWTManager;
        $this->fireWallTokenJWT = $fireWallTokenJWT;
        $this->jwsProvider = $jwsProvider;
        $this->tokenStorageInterface = $tokenStorageInterface;
        $this->devOnly = $devOnly;
        $this->userRepository = $userRepository;
        $this->retardFunction = $retardFunction;
    }




    #[Route('/server-be/login', name: 'app_login', methods: ['POST'])]
    public function index(
        AuthenticationUtils $authenticationUtils,
        AuthServConnector $authServConnector,
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        UserRepository $userRepository,
        Security $security
    ): JsonResponse {


        sleep(2);

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();


        $data = json_decode($request->getContent(), true);

        $userEmail = $data["username"];
        $userPw = $data["password"];

        $authServtestCon = $authServConnector->connectionChecker($request, $userEmail, $userPw);

        if ($authServtestCon[0]) {
            $userUid = $authServtestCon[1];
            $findUserByEmail = $userRepository->findOneByEmail($userUid);
            if ($findUserByEmail === null) {
                $user = new User();
                $user->setEmail($userUid);
                $user->setRoles(["ROLE_USER"]);
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        uniqid()
                    )
                );
                $userRepository->save($user, true);

                $this->retardFunction->RunDeleteNonValideUser($user->getEmail());

                // $security->login($user);
                return new JsonResponse([
                    'traited' => true,
                    'message' => "Connection reussie.",
                    'uid' => $user->getUserIdentifier(),
                    'roles' => $user->getRoles(),
                    'accessToken' => $this->JWTManager->create($user)
                ]);

            } else {
                $user = $findUserByEmail;
                // $security->login($user);
                return new JsonResponse([
                    'traited' => true,
                    'message' => "Connection reussie.",
                    'uid' => $user->getUserIdentifier(),
                    'roles' => $user->getRoles(),
                    'accessToken' => $this->JWTManager->create($user)
                ]);
            }
        } else {
            return new JsonResponse(['message' => $authServtestCon[1]]);
        }


    }


    #[Route('/server-be/state', name: 'app_state', methods: ['POST'])]
    public function stateLogin(
        AuthenticationUtils $authenticationUtils,
        AuthServConnector $authServConnector,
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        UserRepository $userRepository,
        Security $security
    ): JsonResponse {


        $data = json_decode($request->getContent(), true);

        try {
            $accessToken = $data["accessToken"];
            $fireWallResult = $this->fireWallTokenJWT->checkJWT($accessToken);

            if ($fireWallResult[0]) {

                $payload = $this->JWTManager->parse($accessToken);

                $user = $userRepository->findOneByEmail($payload['username']);
                if ($user) {
                    return new JsonResponse([
                        'traited' => true,
                        'messageCL' => 'Token bon',
                        'user' => [
                            'uid' => $payload['username'],
                            'role' => $this->fireWallTokenJWT->checkBestRole($user->getRoles()),
                            'test' => 'test3',
                            'userDocument' => [
                                'onboardingIsCompleted' => $user->isOnboardingIsCompleted(),
                                'emailVerified' => $user->isEmailVerified(),
                            ],
                        ]
                    ]);
                }
                return new JsonResponse([
                    'error' => 'error',
                    'message' => "Votre compte n'existe plus.",
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

    #[Route('/server-be/logout', name: 'app_logout', methods: ['POST'])]
    public function stateLogout(
        AuthenticationUtils $authenticationUtils,
        AuthServConnector $authServConnector,
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        UserRepository $userRepository,
        Security $security
    ): JsonResponse {
        return new JsonResponse([
            'traited' => true,
            'message' => 'Déconnection réussie.'
        ]);
    }



    #[Route('/server-be/MDPOublier', name: 'app_mdp_oublier', methods: ['POST'])]
    public function MDPOublieur(Request $request, UserPasswordHasherInterface $userPasswordHasher): Response
    {
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
            $result = file_get_contents("http://" . $domaine . "/oubliMdp", false, $context);
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


    #[Route('/server-be/MDPChange', name: 'app_mdp_change', methods: ['POST'])]
    public function MDPChangeur(Request $request, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $data = json_decode($request->getContent(), true);

        try {
            $data = array(
                'username' => $data["username"],
                'password' => $data["password"],
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
            $result = file_get_contents("http://" . $domaine . "/mdpchange", false, $context);
            $response = json_decode($result);

            $responsed = json_decode($result, true);


            if (array_key_exists('traited', $responsed)) {
                $user = $this->userRepository->findOneByEmail($responsed['uid']);
                if ($user) {
                    if (!$user->isEmailVerified()) {
                        $user->setEmailVerified(true);
                        $this->userRepository->save($user, true);
                    }
                }
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

}
