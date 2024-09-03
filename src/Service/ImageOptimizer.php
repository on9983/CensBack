<?php

namespace App\Service;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;

class ImageOptimizer
{
    private const MAX_WIDTH = 200;
    private const MAX_HEIGHT = 125;

    private $imagine;

    public function __construct()
    {
        $this->imagine = new Imagine();
    }

    public function resize(string $fileNameAndPath, $maxWidth = 200, $maxHeight = 125): void
    {
        list($iwidth, $iheight) = getimagesize($fileNameAndPath);
        $ratio = $iwidth / $iheight;

        if($iwidth > $maxWidth || $iheight > $maxHeight) {
            if ($maxWidth / $maxHeight > $ratio) {
                $width = $maxHeight * $ratio;
                $height = $maxHeight;
            } else {
                $height = $maxWidth / $ratio;
                $width = $maxWidth;
            }
        }else{
            $width = $iwidth;
            $height = $iheight;
        }
        $photo = $this->imagine->open($fileNameAndPath);
        $photo->resize(new Box($width, $height))->save($fileNameAndPath);
    }
}