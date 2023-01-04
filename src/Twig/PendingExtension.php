<?php

namespace App\Twig;

use App\Config\FavoriteType;
use App\Entity\Friend;
use App\Entity\User;
use App\Repository\FavoriteRepository;
use App\Repository\FriendRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PendingExtension extends AbstractExtension
{
    public function __construct(private readonly FriendRepository $repository)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('isPending', [$this, 'isPending']),
        ];
    }

    public function isPending(User $user, User $some): int
    {
        $pending = 1;
        $declined = 2;

        $friend= $this->repository->findOneBy(['owner' => $user, 'recipient' => $some]);

        if ($friend) {
            if ($friend->getAcceptedAt() == null) return $pending;
            if ($friend->getAcceptedAt() && !$friend->isApproved()) return $declined;
        }

        return 0;
    }
}
