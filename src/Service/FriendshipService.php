<?php

namespace App\Service;

use App\Entity\Friend;
use App\Entity\User;
use App\Repository\FriendRepository;

class FriendshipService
{
    public function __construct(private readonly FriendRepository $repository)
    {
    }

    public function getPendingFriendshipRequest(User $user): array
    {
        return $this->repository->findBy(['owner' => $user]);
    }
}