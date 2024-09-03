<?php
namespace App\Service\ressources;

use App\Entity\Etablissement;
use App\Repository\EtablissementRepository;
use App\Service\FileUploader;

class EtablissementCrud
{
    public function __construct(
        private FileUploader $fileUploader,
        private VehiculeCrud $vehiculeCrud,
        private EtablissementRepository $etablissementRepository,
    ) {
        $this->fileUploader = $fileUploader;
        $this->vehiculeCrud = $vehiculeCrud;
        $this->etablissementRepository = $etablissementRepository;
    }

    public function delete(Etablissement $etablissement)
    {
        $vehicules = $etablissement->getVehicules()->toArray();
        foreach ($vehicules as $vehicule) {
            $this->vehiculeCrud->delete($vehicule);
        }

        if ($etablissement->getImage()) {
            $this->fileUploader->delete($etablissement->getImage());
        }

        $this->etablissementRepository->remove($etablissement, true);
    }

}