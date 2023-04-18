<?php

namespace App\Twig;

use App\Entity\User;
use App\Repository\UserRepository;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class UsersExtension extends AbstractExtension
{
    public function __construct(
        private readonly UserRepository      $userRepository,
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('userList', [$this, 'userList'], ['is_safe' => ['html']]),
            new TwigFunction('lastActivityAgo', [$this, 'lastActivityAgo'], ['is_safe' => ['html']]),
        ];
    }

    public function getFilters(): array
    {
        return array(
            new TwigFilter('lastActivityAgo', [$this, 'lastActivityAgo'], ['is_safe' => ['html']]),
        );
    }

    public function userList(): array
    {
        $users = $this->userRepository->findAll();
//        dump($users);
        try {
            $date = new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));
        } catch (Exception) {
            $date = new DateTimeImmutable();
        }
        return array_map(function (User $user) use ($date) {
            return [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'avatar' => $user->getAvatar(),
                'lastLogin' => $user->getLastLogin(),
                'lastLogout' => $user->getLastLogout(),
                'lastActivity' => $user->getLastActivityAt(),
                'isOnLine' => $user->isOnLine($date),
            ];
        }, $users);
    }

    public function lastActivityAgo($lastActivityAt, $locale = null): string
    {
        $timeZone = 'Europe/Paris';
        try {
            $date = new DateTimeImmutable('now', new DateTimeZone($timeZone));
            $lastActivityAt = new DateTimeImmutable($lastActivityAt->format('Y-m-d H:i:s'), new DateTimeZone($timeZone));
        } catch (Exception) {
            $date = new DateTimeImmutable();
        }

        static $units = array(
            'y' => 'year',
            'm' => 'month',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second'
        );

        $diff = date_diff($date, $lastActivityAt);

        foreach ($units as $attribute => $unit) {
            $count = $diff->$attribute;
            if (0 !== $count) {
                return $this->doGetDiffMessage($count, $diff->invert, $unit, $locale);
            }
        }

        return $this->getEmptyDiffMessage($locale);
    }

    protected function doGetDiffMessage(int $count, bool $invert, string $unit, string $locale = null): string
    {
        $id = sprintf('diff.%s.%s', $invert ? 'ago' : 'in', $unit);

        return $this->translator->trans($id, array('%count%' => $count), 'time', $locale);
    }

    public function getEmptyDiffMessage(string $locale = null): string
    {
        return $this->translator->trans('diff.empty', array(), 'time', $locale);
    }
}
