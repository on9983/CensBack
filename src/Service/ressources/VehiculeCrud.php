<?php
namespace App\Service\ressources;

use App\Entity\Vehicule;
use App\Repository\VehiculeRepository;
use App\Service\FileUploader;

class VehiculeCrud
{
    public function __construct(
        private FileUploader $fileUploader,
        private VehiculeRepository $vehiculeRepository,
    ) {
        $this->fileUploader = $fileUploader;
        $this->vehiculeRepository = $vehiculeRepository;
    }

    public function delete(Vehicule $vehicule)
    {
        if ($vehicule->getImage()) {
            $this->fileUploader->delete($vehicule->getImage());
        }
        $this->vehiculeRepository->remove($vehicule, true);
    }

}