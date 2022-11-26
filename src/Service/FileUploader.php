<?php

namespace App\Service;

use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\Exception\InvalidArgumentException;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    private array $targetDirectory;
    private SluggerInterface $slugger;

    public function __construct($targetDirectory, SluggerInterface $slugger)
    {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
    }

    public function upload(UploadedFile $file, $type): string
    {
        if ($type == 'avatar' || $type == 'banner' ||
            $type == 'event_thumbnail' || $type == 'event_banner') {
            $fileName = Uuid::uuid4()->toString() . '.' . $file->guessExtension();
        } else {
            $originalFilename = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $fileName = $this->slugger->slug($originalFilename) . '.' . $file->guessExtension();
        }
        $file->move($this->getTargetDirectory($type), $fileName);

        return $fileName;
    }

    public function removeFile($fileName, $type): bool
    {
        if (file_exists($this->getTargetDirectory($type) . '/' . $fileName)) {
            return unlink($this->getTargetDirectory($type) . '/' . $fileName);
        }
        return false;
    }

    public function getTargetDirectory($type): string
    {
        return match ($type) {
            'avatar' => $this->targetDirectory[0],
            'banner' => $this->targetDirectory[1],
            'article_images' => $this->targetDirectory[2],
            'event_thumbnail' => $this->targetDirectory[3],
            'event_banner' => $this->targetDirectory[4],
            default => '',
        };
    }
}

