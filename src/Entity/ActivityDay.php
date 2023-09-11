<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ActivityDayRepository;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;

#[ORM\Entity(repositoryClass: ActivityDayRepository::class)]
#[ApiResource]
class ActivityDay
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(cascade: ['persist', 'remove'], inversedBy: 'activityDays')]
    private ?Activity $activity = null;

    #[ORM\Column]
    private ?bool $standUpRingCompleted = false;

    #[ORM\Column]
    private ?bool $moveRingCompleted = false;

    #[ORM\Column]
    private ?bool $exerciseRingCompleted = false;

    #[ORM\Column(type: Types::JSON)]
    private array $standUp;

    #[ORM\Column]
    private ?int $standUpResult = 0;

    #[ORM\Column]
    private ?int $moveResult = 0;

    #[ORM\Column]
    private ?int $exerciseResult = 0;

    #[ORM\Column]
    private ?int $steps = 0;

    #[ORM\Column]
    private ?float $distance = 0;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $day;

    #[ORM\Column(nullable: true)]
    private ?int $week = null;

    public function __construct($activity)
    {
        try {
            $today = new DateTimeImmutable('now', new DateTimeZone($activity->getUser()->getTimezone() ?? 'Europe/Paris'));
//            $today = new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));
        } catch (Exception) {
            $today = new DateTimeImmutable();
        }
        $today = $today->setTime(0, 0);

        $this->activity = $activity;
        $this->standUp = array_fill(0, 24, 0);
        $this->day = $today;
        $this->week = $today->format('W');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStandUp(): array
    {
        return $this->standUp;
    }

    public function setStandUp(array $standUp): self
    {
        $this->standUp = $standUp;

        return $this;
    }

    public function isMoveRingCompleted(): ?bool
    {
        return $this->moveRingCompleted;
    }

    public function setMoveRingCompleted(bool $moveRingCompleted): self
    {
        $this->moveRingCompleted = $moveRingCompleted;

        return $this;
    }

    public function isExerciseRingCompleted(): ?bool
    {
        return $this->exerciseRingCompleted;
    }

    public function setExerciseRingCompleted(bool $exerciseRingCompleted): self
    {
        $this->exerciseRingCompleted = $exerciseRingCompleted;

        return $this;
    }

    public function isStandUpRingCompleted(): ?bool
    {
        return $this->standUpRingCompleted;
    }

    public function setStandUpRingCompleted(bool $standUpRingCompleted): self
    {
        $this->standUpRingCompleted = $standUpRingCompleted;

        return $this;
    }

    public function getMoveResult(): ?int
    {
        return $this->moveResult;
    }

    public function setMoveResult(int $moveResult): self
    {
        $this->moveResult = $moveResult;

        return $this;
    }

    public function getExerciseResult(): ?int
    {
        return $this->exerciseResult;
    }

    public function setExerciseResult(int $exerciseResult): self
    {
        $this->exerciseResult = $exerciseResult;

        return $this;
    }

    public function getStandUpResult(): ?int
    {
        return $this->standUpResult;
    }

    public function setStandUpResult(int $standUpResult): self
    {
        $this->standUpResult = $standUpResult;

        return $this;
    }

    public function getSteps(): ?int
    {
        return $this->steps;
    }

    public function setSteps(int $steps): self
    {
        $this->steps = $steps;

        return $this;
    }

    public function getDistance(): ?float
    {
        return $this->distance;
    }

    public function setDistance(float $distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    public function getDay(): ?DateTimeInterface
    {
        try {
            return $this->day->setTimezone(new DateTimeZone($this->activity->getUser()->getTimezone() ?? 'Europe/Paris'));
        } catch (Exception) {
            return $this->day;
        }
//        return $this->day->setTimezone(new DateTimeZone('Europe/Paris'));
    }

    public function setDay(DateTimeInterface $day): self
    {
        $this->day = $day;

        return $this;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function setActivity(?Activity $activity): self
    {
        $this->activity = $activity;

        return $this;
    }

    public function getWeek(): ?int
    {
        return $this->week;
    }

    public function setWeek(?int $week): self
    {
        $this->week = $week;

        return $this;
    }
}
