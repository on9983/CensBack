<?php

namespace App\Service;

use phpDocumentor\Reflection\Types\Boolean;
use App\Service\DevOnly;
use Symfony\Component\HttpFoundation\Request;

class AuthServConnector
{
    public function __construct(
        private DevOnly $devOnly,
    )
    {
        $this->devOnly = $devOnly;
    }

    public function connectionChecker(Request $request,string $user_email, string $user_pw): array
    {

        $data = [
            'username' => $user_email,
            'password' => $user_pw
        ];

        try {
            $options = [
                'http' => [
                    'method' => 'POST',
                    'content' => json_encode($data),
                    'header' => "Content-Type: application/json\r\n" .
                        "Accept: application/json\r\n"
                ]
            ];

            $domaine = "localhost:8004";
            if (getenv('DOCKER_ENV')){
                $domaine = "gvrAuthServ";
            }

            $context = stream_context_create($options);

            $result = file_get_contents("http://".$domaine."/jsonLogin", false, $context);
            
            $response = json_decode($result, true);


            if ($user_pw !== "" && array_key_exists('traited',$response)) {
                return [true,$response['uid']];
            } else {
                if (array_key_exists('message',$response)){
                    return [false,$response['message']];
                } else {
                    return [false,'Vos informations sont incorrectes, ou alors le compte n\'existe plus.'];
                }
            }


        } catch (\Exception $e) {
            //return [false,$e->getMessage()];
            return [false,'Vos informations sont incorrectes, ou alors le compte n\'existe plus.'];
        }

    }



    // public function getTargetDirectory()
    // {
    //     return $this->targetDirectory;
    // }
}