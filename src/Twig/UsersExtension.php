<?php

namespace App\Twig;

use App\Config\FavoriteType;
use App\Entity\Friend;
use App\Repository\FavoriteRepository;
use App\Repository\UserRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UsersExtension extends AbstractExtension
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('userList', [$this, 'userList']),
        ];
    }

    public function userList(): array
    {
        return $this->userRepository->findAll();
    }
}
