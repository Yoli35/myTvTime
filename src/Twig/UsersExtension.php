<?php

namespace App\Twig;

use App\Entity\ChatDiscussion;
use App\Entity\User;
use App\Repository\ChatDiscussionRepository;
use App\Repository\MovieCollectionRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\Common\Collections\Collection;
use Exception;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class UsersExtension extends AbstractExtension
{
    public function __construct(
        private readonly ChatDiscussionRepository  $chatDiscussionRepository,
        private readonly MovieCollectionRepository $movieCollectionRepository,
        private readonly TranslatorInterface       $translator,
        private readonly UserRepository            $userRepository,
    )
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('lastActivityAgo', [$this, 'lastActivityAgo'], ['is_safe' => ['html']]),
            new TwigFunction('userAlarms', [$this, 'userAlarms'], ['is_safe' => ['html']]),
            new TwigFunction('userDiscussions', [$this, 'userDiscussions'], ['is_safe' => ['html']]),
            new TwigFunction('userList', [$this, 'userList'], ['is_safe' => ['html']]),
            new TwigFunction('userMovieCollections', [$this, 'userMovieCollections'], ['is_safe' => ['html']]),
            new TwigFunction('whoIsTyping', [$this, 'whoIsTyping'], ['is_safe' => ['html']]),
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

        usort($users, function (User $a, User $b) {
            $lastA = $a->getLastLogout() ?: $a->getLastActivityAt();
            $lastB = $b->getLastLogout() ?: $b->getLastActivityAt();

            return $lastB->getTimestamp() <=> $lastA->getTimestamp();
        });

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

    public function userDiscussions(User $user): array
    {
        $discussion1 = $this->chatDiscussionRepository->findBy(['user' => $user]);
        $discussion2 = $this->chatDiscussionRepository->findBy(['recipient' => $user]);
        $discussions = array_merge($discussion1, $discussion2);
        usort($discussions, function (ChatDiscussion $a, ChatDiscussion $b) {
            $lastA = $a->getLastMessageAt();
            $lastB = $b->getLastMessageAt();

            return $lastB->getTimestamp() <=> $lastA->getTimestamp();
        });
        return $discussions;
    }

    public function whoIsTyping(User $user): array
    {
        $discussion1 = $this->chatDiscussionRepository->findBy(['user' => $user]);
        $discussion2 = $this->chatDiscussionRepository->findBy(['recipient' => $user]);
        $discussions = array_merge($discussion1, $discussion2);
        $chatterBoxes = [];

        foreach ($discussions as $discussion) {
            if ($discussion->getUser()->getId() == $user->getId()) {
                if ($discussion->isTypingRecipient()) {
                    $chatterBoxes[] = $discussion->getRecipient()->getId();
                }
            } else {
                if ($discussion->isTypingUser()) {
                    $chatterBoxes[] = $discussion->getUser()->getId();
                }
            }
        }
        return $chatterBoxes;
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
            'i' => 'minute'
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

    public function userMovieCollections(User $user): array
    {
        return $this->movieCollectionRepository->getSummary($user->getId());
    }

    public function userAlarms(User $user): Collection
    {
        return $user->getAlarms();
    }
}
