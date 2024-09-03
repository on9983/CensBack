<?php

namespace App\Service;

use DateTime;
use Illuminate\Support\Str;

use App\Service\ImageOptimizer;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class DateVerifieur
{


    public function __construct(

    ) {

    }

    public function Verifie(DateTime|null $datetime): int
    {
        if ($datetime) {
            if (date_diff(new DateTime("now"), $datetime)->format('%r%a') < 60) {
                if (date_diff(new DateTime("now"), $datetime)->format('%r%a') <= 0) {
                    return 4;
                } else {
                    return 3;
                }
            } else {
                if (date_diff(new DateTime("now"), $datetime)->format('%r%a') < 120) {
                    return 2;
                } else {
                    return 1;
                }
            }
        }
        return 5;
    }

    public function ConvertToString(int $urgenceLevel): string
    {
        switch ($urgenceLevel) {
            case 1:
                return "OK";
            case 2:
                return "PRESQUE";
            case 3:
                return "URGENT";
            case 4:
                return "PERIMED";
            case 5:
                return "INCONNU";
        }
        return "INCONNU";
    }

    public function TacheAFaireGen(string $tacheLabel, DateTime|null $datetime, array $tacheAFaire, int $urgenceMaxLevel): array
    {
        $urgenceLevel = $this->Verifie($datetime);
        if ($urgenceLevel !== 1) { //!OK
            if ($datetime) {
                $tacheAFaire[] = $tacheLabel . ", le " . $datetime?->format('d/m/Y') . ".";
            } else {
                $tacheAFaire[] = $tacheLabel . " non définie.";
            }
        }
        if ($urgenceLevel > $urgenceMaxLevel) {
            $urgenceMaxLevel = $urgenceLevel;
        }

        return ["tacheAFaire" => $tacheAFaire, "urgenceMaxLevel" => $urgenceMaxLevel];
    }

}