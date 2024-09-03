<?php

namespace App\Service;

use Illuminate\Support\Str;

use App\Service\ImageOptimizer;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class Validator
{
    public function __construct(
    ) {

    }

    public function DateIsValid(string $input)
    {
        if (strlen($input) !== 10) {
            return false;
        }
        if (preg_match('/(?P<d>\d{2})(?P<sep>\D)(?P<m>\d{2})\2(?P<y>\d{4})/', $input)) {
            return true;
        }
        return false;
    }

    public function NumberIsValid(string $input)
    {
        if (is_numeric($input) && $input !== "") {
            return true;
        }
        return false;
    }

    public function IntegerIsValid(string $input)
    {
        if(str_contains($input, ".")){
            return false;
        }
        if (is_numeric($input) && $input !== "") {
            return true;
        }
        return false;
    }

    public function AnneeIsValid(string $input)
    {
        if (strlen($input) !== 4) {
            return false;
        }
        if (preg_match("/^\d+$/", $input)) {
            return true;
        }
        return false;
    }

}