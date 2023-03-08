<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ActivityDayRepository;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityDayRepository::class)]
#[ApiResource]
class ActivityDay
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(cascade: ['persist', 'remove'], inversedBy: 'activityDays')]
    private ?Activity $activity;

    #[ORM\Column]
    private ?bool $standUpRingCompleted = false;

    #[ORM\Column]
    private ?bool $moveRingCompleted = false;

    #[ORM\Column]
    private ?bool $exerciceRingCompleted = false;

    #[ORM\Column(type: Types::JSON)]
    private array $standUp;

    #[ORM\Column]
    private ?int $standUpResult = 0;

    #[ORM\Column]
    private ?int $moveResult = 0;

    #[ORM\Column]
    private ?int $exerciceResult = 0;

    #[ORM\Column]
    private ?int $steps = 0;

    #[ORM\Column]
    private ?float $distance = 0;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $day;

    public function __construct($activity)
    {
        $this->activity = $activity;
        $this->standUp = array_fill(0, 24, 0);
        $this->day = new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));
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

    public function isExerciceRingCompleted(): ?bool
    {
        return $this->exerciceRingCompleted;
    }

    public function setExerciceRingCompleted(bool $exerciceRingCompleted): self
    {
        $this->exerciceRingCompleted = $exerciceRingCompleted;

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

    public function getExerciceResult(): ?int
    {
        return $this->exerciceResult;
    }

    public function setExerciceResult(int $exerciceResult): self
    {
        $this->exerciceResult = $exerciceResult;

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
        return $this->day;
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
}
