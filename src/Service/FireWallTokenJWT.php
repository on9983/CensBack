<?php

namespace App\Service;

use App\Entity\Etablissement;
use App\Entity\User;
use App\Repository\EtablissementRepository;
use App\Repository\UserRepository;
use App\Service\FireWallEspaces;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWSProvider\JWSProviderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;




class FireWallTokenJWT
{
    public function __construct(
        private JWSProviderInterface $jwsProvider,
        private UserRepository $userRepository,
        private JWTTokenManagerInterface $JWTManager,
        private EtablissementRepository $etablissementRepository,
    ) {
        $this->jwsProvider = $jwsProvider;
        $this->userRepository = $userRepository;
        $this->JWTManager = $JWTManager;
        $this->etablissementRepository = $etablissementRepository;
    }

    public function checkJWT(string|null $accessToken): array
    {

        if ($accessToken) {
            try {
                $jwt = $this->jwsProvider->load($accessToken);
                $payload = $jwt->getPayload();

                if ($jwt->isExpired()) {
                    return [
                        false,
                        'Votre connection a expiré.',
                        [],
                        403
                    ];
                }

                if ($jwt->isInvalid()) {
                    return [
                        false,
                        'Vous n\'êtes plus connecté.',
                        [],
                        401
                    ];
                }

                return [
                    true,
                    'Token bon',
                    $payload,
                    0
                ];
            } catch (\Exception $e) {
                return [
                    false,
                    'Error',
                    [],
                    401
                ];
            }
        } else {
            return [
                false,
                'Vous n\'êtes pas connecté.',
                [],
                401
            ];
        }

    }

    public function checkBestRole(array $roles): string
    {
        foreach ($roles as $role) {
            if ($role === "ROLE_ADMIN") {
                return $role;
            }
        }
        foreach ($roles as $role) {
            if ($role === "ROLE_ESP_ADMIN") {
                return $role;
            }
        }
        return "ROLE_USER";
    }

    public function checkAccessToken(Request $request, string|null $accessToken): JsonResponse|array
    {

        if ($accessToken) {
            try {
                $jwt = $this->jwsProvider->load($accessToken);
                $payload = $jwt->getPayload();

                if ($jwt->isExpired()) {
                    return new JsonResponse([
                        'error' => 'error',
                        'code' => 403,
                        'message' => 'Votre connection a expiré.'
                    ]);
                }

                if ($jwt->isInvalid()) {
                    return new JsonResponse([
                        'error' => 'error',
                        'code' => 401,
                        'message' => 'Vous n\'êtes plus connecté.'
                    ]);
                }


                // TOKEN VALID

                $payload = $this->JWTManager->parse($accessToken);
                $user = $this->userRepository->findOneByEmail($payload["username"]);

                return ['JWT valide' => true, 'user' => $user];

            } catch (\Exception $e) {
                return new JsonResponse([
                    'error' => 'error',
                    'code' => 401,
                ]);
            }
        } else {
            return new JsonResponse([
                'error' => 'error',
                'code' => 401,
                'message' => 'Vous n\'êtes pas connecté.'
            ]);

        }

    }

    public function checkEspace(User $user, string $eid): Etablissement|JsonResponse
    {
        $espaces = $user->getEtablissements();
        foreach ($espaces as $espace_i) {
            if ($eid === $espace_i->getEid()) {
                return $espace_i;
            }
        }
        return new JsonResponse([
            'error' => 'error3',
        ]);
    }


    public function FindUserEspaceByName(User $user, string $name): Etablissement|null
    {
        $espaces = $user->getEtablissements();
        foreach ($espaces as $espace_i) {
            if ($name === $espace_i->getNom()) {
                return $espace_i;
            }
        }
        return null;
    }


}