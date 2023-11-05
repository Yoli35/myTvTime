<?php

namespace App\Twig;

use App\Entity\Alarm;
use App\Entity\ChatDiscussion;
use App\Entity\Settings;
use App\Entity\User;
use App\Repository\ChatDiscussionRepository;
use App\Repository\MovieListRepository;
use App\Repository\MovieRepository;
use App\Repository\SettingsRepository;
use App\Repository\UserRepository;
use App\Service\DateService;
use DateTimeImmutable;
use DateTimeInterface;
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
        private readonly ChatDiscussionRepository $chatDiscussionRepository,
        private readonly DateService              $dateService,
        private readonly MovieListRepository      $movieListRepository,
        private readonly MovieRepository          $movieRepository,
        private readonly SettingsRepository       $settingsRepository,
        private readonly TranslatorInterface      $translator,
        private readonly UserRepository           $userRepository,
    )
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getSettings', [$this, 'getSettings'], ['is_safe' => ['html']]),
            new TwigFunction('getTime', [$this, 'getTime'], ['is_safe' => ['html']]),
            new TwigFunction('lastActivityAgo', [$this, 'lastActivityAgo'], ['is_safe' => ['html']]),
            new TwigFunction('newAlarm', [$this, 'newAlarm'], ['is_safe' => ['html']]),
            new TwigFunction('userAlarms', [$this, 'userAlarms'], ['is_safe' => ['html']]),
            new TwigFunction('userDiscussions', [$this, 'userDiscussions'], ['is_safe' => ['html']]),
            new TwigFunction('userList', [$this, 'userList'], ['is_safe' => ['html']]),
            new TwigFunction('userMovieLists', [$this, 'userMovieLists'], ['is_safe' => ['html']]),
            new TwigFunction('whoIsTyping', [$this, 'whoIsTyping'], ['is_safe' => ['html']]),
        ];
    }

    public function getFilters(): array
    {
        return array(
            new TwigFilter('lastActivityAgo', [$this, 'lastActivityAgo'], ['is_safe' => ['html']]),
            new TwigFilter('viewedMovie', [$this, 'viewedMovie'], ['is_safe' => ['html']]),
        );
    }

    public function getSettings(User $user): array
    {
        $settings = $this->settingsRepository->findOneBy(['user' => $user, 'name' => 'settings']);

        if ($settings === null) {
            $settings = new Settings();
            $settings->setUser($user)->setName('settings')->setData(['saturation' => 18]);
            $this->settingsRepository->save($settings, true);
        }

        return $settings->getData();
    }

    public function userList(): array
    {
        $users = $this->userRepository->findAll();

        usort($users, function (User $a, User $b) {
            $lastA = $a->getLastLogout() ?: $a->getLastActivityAt();
            $lastB = $b->getLastLogout() ?: $b->getLastActivityAt();

            return $lastB->getTimestamp() <=> $lastA->getTimestamp();
        });

        $date = $this->dateService->newDateImmutable('now', 'Europe/Paris');
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

    public function viewedMovie($user, $movieId): bool
    {
        if ($user == null) {
            return false;
        }
        $result = $this->movieRepository->viewedMovie($user->getId(), $movieId);
//        dump(['result' => $result, 'movieId' => $movieId, 'userId' => $user->getId()]);

        return (bool)count($result);
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

    public function userMovieLists(User $user): array
    {
        return $this->movieListRepository->getSummary($user->getId());
    }

    public function userAlarms(User $user): Collection
    {
        $alarms = $user->getAlarms();
        $alarms->add($this->newAlarm($user));
        return $alarms;
    }

    public function newAlarm(User $user): Alarm
    {
        $alarm = new Alarm($user, $this->translator->trans('New alarm'), 0, null, 1, null, $this->dateService->newDateImmutable('now', 'Europe/Paris'));
        $alarm->setTime($this->getTime($user))->setId(0);
        return $alarm;
    }

    public function getTime(User $user): DateTimeInterface
    {
        $time = $this->dateService->getNow($user->getTimeZone());
        $time = $time->setTime(intval($time->format('H')), intval(intval($time->format('i')) / 5) * 5 + 5);
        return $time->setDate(1970, 1, 1);
    }
}
