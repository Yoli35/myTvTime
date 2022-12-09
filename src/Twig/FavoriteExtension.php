<?php

namespace App\Twig;

use App\Config\FavoriteType;
use App\Repository\FavoriteRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FavoriteExtension extends AbstractExtension
{
    public function __construct(private FavoriteRepository $repository)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_favorite', [$this, 'isFavorite']),
        ];
    }

    public function isFavorite(int $userId, int $mediaId, string $type): bool
    {
        if ($type != FavoriteType::movie && $type != FavoriteType::serie && $type != FavoriteType::youtube) {
            return false;
        }

        $favorite = $this->repository->findOneBy(['type' => $type, 'userId' => $userId, 'mediaId' => $mediaId]);
        return ($favorite != null);
    }
}
