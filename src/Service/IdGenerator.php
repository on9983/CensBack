<?php
namespace App\Service;

class IdGenerator
{

    public function generateNom($entity, array $entities)
    {
        // Uid Generation

        //$entities = $userRepository->findAll();

        restart:
        $entity->setNom(bin2hex(random_bytes(8) . "_" . random_bytes(8)));
        $a = true;
        foreach ($entities as $entity_i) {
            if ($entity_i->getNom() === $entity->getNom()) {
                $a = false;
                break;
            }
        }
        if ($a == false) {
            goto restart;
        }
    }

}