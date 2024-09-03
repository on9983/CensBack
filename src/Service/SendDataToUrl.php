<?php
namespace App\Service;

class SendDataToUrl
{

    public function send(string $url, array $data) {
        $options = array(
            'http' => array(
                'method' => 'POST',
                'content' => json_encode($data),
                'header' => "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n"
            )
        );

        $domaine = "localhost:8004";
        if (getenv('DOCKER_ENV')){
            $domaine = "gvrAuthServ";
        }

        $context = stream_context_create($options);
        $result = file_get_contents("http://".$domaine.$url, false, $context);
        $response = json_decode($result, true);

        return $response;
    }

}