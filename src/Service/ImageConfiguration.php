<?php

namespace App\Service;

use App\Repository\ImageConfigRepository;
use JetBrains\PhpStorm\ArrayShape;

class ImageConfiguration
{
    private array $imageConfiguration;

    public function __construct(ImageConfigRepository $imageConfigRepository)
    {
        $config = $imageConfigRepository->findAll();
        $c = $config[0];
        $backdropSizes = $c->getBackdropSizes();
        $logoSizes = $c->getLogoSizes();
        $posterSizes = $c->getPosterSizes();
        $profileSizes = $c->getProfileSizes();
        $stillSizes = $c->getStillSizes();

        $this->imageConfiguration = [
            'url' => $c->getSecureBaseUrl(),
            'backdrop_sizes' => $backdropSizes,
            'logo_sizes' => $logoSizes,
            'poster_sizes' => $posterSizes,
            'profile_sizes' => $profileSizes,
            'still_sizes' => $stillSizes
        ];
    }

    #[ArrayShape(['url' => "string", 'backdrop_sizes' => "array", 'logo_sizes' => "array", 'poster_sizes' => "array", 'profile_sizes' => "array", 'still_sizes' => "array"])]
    public function getConfig(): array
    {
        return $this->imageConfiguration;
    }

    public function getCompleteUrl(string $path, $type, $size): string
    {
        return $this->imageConfiguration['url'] . $this->imageConfiguration[$type][$size] . $path;
    }
}