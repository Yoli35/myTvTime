<?php

namespace App\Twig;

use App\Config\PathType;
use App\Service\ImageConfiguration;
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

    public function __construct(private readonly ImageConfiguration $imageConfiguration)
    {
        $imageConfig = $this->imageConfiguration->getConfig();
        $this->targetDirectories[PathType::tmdb_base_url] = $imageConfig['url'];
        $this->targetDirectories[PathType::profile_sizes] = $imageConfig['profile_sizes']; // 0 => "w45"; 1 => "w185"; 2 => "h632"; 3 => "original";
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getPath', [$this, 'getPath'], ['is_safe' => ['html']]),
        ];
    }

    public function getPath(string $type, $sizes = null): string
    {
        if ($type === PathType::profile_sizes) {
            return $this->targetDirectories['tmdb_base_url'] . $this->targetDirectories[$type][$sizes];
        }
        return $this->targetDirectories[$type];
    }
}
