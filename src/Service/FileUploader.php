<?php

namespace App\Service;

use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\Exception\InvalidArgumentException;

class FileUploader
{
    private array $targetDirectory;

    public function __construct($targetDirectory)
    {
        $this->targetDirectory = $targetDirectory;
    }

    public function upload(UploadedFile $file, $type): string
    {
        $fileName = Uuid::uuid4()->toString() . '.' . $file->guessExtension();
        $file->move($this->getTargetDirectory($type), $fileName);

        return $fileName;
    }

    public function removeFile($fileName, $type): bool
    {
        return unlink($this->getTargetDirectory($type) . '/' . $fileName);
    }

    public function getTargetDirectory($type): string
    {
        if ($type == 'avatar') {
            return $this->targetDirectory[0];
        } else
            return $this->targetDirectory[1];
    }
}

