<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserADcheckType;
use App\Repository\UserRepository;
use App\Service\AuthServConnector;
use App\Service\DevOnly;
use App\Service\FireWallTokenJWT;
use App\Service\ressources\EtablissementCrud;
use App\Service\LdapConnector;
use App\Service\SendDataToUrl;
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

class CompteController extends AbstractController
{


    public function __construct(
        private SerializerInterface $serializerInterface,
        private JWTTokenManagerInterface $JWTManager,
        private FireWallTokenJWT $fireWallTokenJWT,
        private JWSProviderInterface $jwsProvider,
        private TokenStorageInterface $tokenStorageInterface,
        private DevOnly $devOnly,
        private UserRepository $userRepository,
        private SendDataToUrl $sendDataToUrl,
        private EtablissementCrud $etablissementCrud,

    ) {
        $this->serializerInterface = $serializerInterface;
        $this->JWTManager = $JWTManager;
        $this->fireWallTokenJWT = $fireWallTokenJWT;
        $this->jwsProvider = $jwsProvider;
        $this->tokenStorageInterface = $tokenStorageInterface;
        $this->devOnly = $devOnly;
        $this->userRepository = $userRepository;
        $this->sendDataToUrl = $sendDataToUrl;
        $this->etablissementCrud = $etablissementCrud;
    }




    #[Route('/server-be/mon-compte/suppr', name: 'app_compte_suppr', methods: ['POST'])]
    public function nousContacter(
        Request $request
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

                $etablissements = $user->getEtablissements()->toArray();
                foreach ($etablissements as $etablissement) {
                    $this->etablissementCrud->delete($etablissement);
                }




                // DELETE user from auth server

                $response = $this->sendDataToUrl->send("/compte/suppr", [
                    'uid' => $user->getEmail(),
                ]);

                if (array_key_exists('traited', $response)) {
                    $this->userRepository->remove($user, true);
                    return new JsonResponse($response);
                }
                return new JsonResponse([
                    'critique' => 'error',
                    'error' => 'Suppression du compte impossible',
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


}
