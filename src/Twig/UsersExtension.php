<?php

namespace App\Twig;

use App\Config\FavoriteType;
use App\Entity\Friend;
use App\Entity\User;
use App\Repository\FavoriteRepository;
use App\Repository\UserRepository;
use DateInterval;
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
        $users = $this->userRepository->findAll();
//        dump($users);
        try {
            $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        } catch (\Exception $e) {
            $now = new \DateTimeImmutable();
        }
        return array_map(function (User $user) use ($now) {
            return [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'avatar' => $user->getAvatar(),
                'lastLogin' => $user->getLastLogin(),
                'lastLogout' => $user->getLastLogout(),
                'lastActivity' => $user->getLastActivityAt(),
                'isOnLine' => $user->isOnLine($now),
            ];
        }, $users);
    }
}
