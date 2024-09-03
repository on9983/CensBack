<?php

namespace App\Service;

use Illuminate\Support\Str;

use App\Service\ImageOptimizer;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{


    public function __construct(
        // private $uploadsdirectory,
        private SluggerInterface $slugger,
        private ImageOptimizer $imageOptimizer,
        private KernelInterface $appKernel
    ) {
        $this->imageOptimizer = $imageOptimizer;
        $this->appKernel = $appKernel;
    }

    public function delete($twigFullFileName)
    {
        $FileAndPath = $this->appKernel->getProjectDir() . "/public/uploads" . $twigFullFileName;
        if (is_file($FileAndPath)) {
            unlink($FileAndPath);
        }
    }

    public function upload(UploadedFile $file, $ressource_name = "/autre", $espace_name = "/racine", $type = "standard")
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $extention = $file->guessExtension();
        $fileName = $safeFilename . '-' . uniqid() . '.' . $extention;

        $path = $this->appKernel->getProjectDir() . "/public/uploads" . $espace_name . $ressource_name;

        if ($extention === "png" || $extention === "jpg" || $extention === "jpeg" || $extention === "gif") {
            $path = $path . "/images";
            $file->move($path, $fileName);

            $twigFullFileName = $espace_name . $ressource_name . "/images" . "/" . $fileName;

            if ($type = "Photo") {
                $this->imageOptimizer->resize($path . "/" . $fileName, 1920, 1080);
            } else {
                $this->imageOptimizer->resize($path . "/" . $fileName);
            }

        } elseif ($extention === "pdf") {
            $path = $path . "/documents_pdf";
            $file->move($path, $fileName);

            $twigFullFileName = $espace_name . $ressource_name . "/documents_pdf" . "/" . $fileName;
        } else {
            return null;
        }

        return [
            'fileName' => $fileName,
            'fullFileName' => $twigFullFileName
        ];
    }


    public function convert_base64_to_uploadedFile_file(string $base64File)
    {
        // CONVERTER BASE64BIT-> UPLOADEDFILE()
        $base64File = str_replace(' ', '', $base64File);
        // decode the base64 file
        $fileData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64File));

        // save it to temporary dir first.
        $tmpFilePath = sys_get_temp_dir() . '/' . Str::uuid()->toString();
        file_put_contents($tmpFilePath, $fileData);

        // this just to help us get file info.
        $tmpFile = new File($tmpFilePath);

        $file = new UploadedFile(
            $tmpFile->getPathname(),
            $tmpFile->getFilename(),
            $tmpFile->getMimeType(),
            0,
            true // Mark it as test, since the file isn't from real HTTP POST.
        );

        return $file;
    }

    public function convert_image_to_base64(string $imagePath): string
    {
        $fullpath = $this->appKernel->getProjectDir() . "/public/uploads" . $imagePath;
        $imagedata = file_get_contents($fullpath);
        $base64 = base64_encode($imagedata);
        $imageB64Data = 'data: ' . mime_content_type($fullpath) . ';base64,' . $base64;
        return $imageB64Data;
    }

    // public function getTargetDirectory()
    // {
    //     return $this->uploadsdirectory;
    // }
}