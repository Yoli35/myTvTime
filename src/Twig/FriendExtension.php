<?php

namespace App\Twig;

use App\Config\FavoriteType;
use App\Entity\Friend;
use App\Repository\FavoriteRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FriendExtension extends AbstractExtension
{
    public function __construct()
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('isFriend', [$this, 'isFriend']),
        ];
    }

    public function isFriend(int $needle, array $strawBale): bool
    {
        $friend = false;

        /** @var Friend $straw */
        foreach ($strawBale as $straw) {
            if ($needle == $straw->getRecipient()->getId() || $needle == $straw->getOwner()->getId()) {
                $friend = true;
                break;
            }
        }
        return $friend;
    }
}
