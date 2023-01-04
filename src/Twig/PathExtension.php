<?php

namespace App\Twig;

use App\Config\PathType;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PathExtension extends AbstractExtension
{
    private array $targetDirectories = [
        PathType::userAvatar => '/images/users/avatars/',
        PathType::userBanner => '/images/users/banners/',
        PathType::collectionThumbnail => '/images/collections/thumbnails/',
        PathType::collectionBanner => '/images/collections/banners/',
        PathType::eventThumbnail => '/images/events/thumbnails/',
        PathType::eventBanner => '/images/events/banners/',
        PathType::eventImages => '/images/events/images/',
        PathType::contactImages => '/images/contact/',
        PathType::articleThumbnail => '/images/articles/thumbnails/',
        PathType::articleBanner => '/images/articles/banners/',
        PathType::articleImages => '/images/articles/images/'
    ];

    public function __construct()
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getPath', [$this, 'getPath']),
        ];
    }

    public function getPath(string $type): string
    {
        return $this->targetDirectories[$type];
    }
}
