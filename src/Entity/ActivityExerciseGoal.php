<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ActivityExerciseGoalRepository;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityExerciseGoalRepository::class)]
#[ApiResource]
class ActivityExerciseGoal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(cascade: ['persist', 'remove'], inversedBy: 'exerciseGoals')]
    private ?Activity $activity;

    #[ORM\Column]
    private ?int $amount;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $start;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $end;

    public function __construct(Activity $activity, int $amount, DateTimeInterface $start)
    {
        $this->activity = $activity;
        $this->amount = $amount;
        $this->start = $start;
        $this->end = null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function setActivity(?Activity $activity): void
    {
        $this->activity = $activity;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getStart(): ?DateTimeInterface
    {
        return $this->start;
    }

    public function setStart(DateTimeInterface $start): self
    {
        $this->start = $start;

        return $this;
    }

    public function getEnd(): ?DateTimeInterface
    {
        return $this->end;
    }

    public function setEnd(DateTimeInterface $end): self
    {
        $this->end = $end;

        return $this;
    }
}
