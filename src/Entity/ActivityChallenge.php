<?php

namespace App\Entity;

use App\Repository\ActivityChallengeRepository;
use App\Entity\Trait\CreateAtTrait;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityChallengeRepository::class)]
class ActivityChallenge
{
    use CreateAtTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private ?bool $completed = null;

    #[ORM\ManyToOne(inversedBy: 'activityChallenges')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Activity $activity = null;

    #[ORM\Column(length: 16)]
    private ?string $challenge = null;

    #[ORM\Column]
    private ?int $goal = null;

    #[ORM\Column]
    private ?float $value = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?DateTimeImmutable $startAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?DateTimeImmutable $endAt = null;

    #[ORM\Column]
    private ?int $progress;

    public function __construct(Activity $activity)
    {
        $this->activity = $activity;
        $this->createdAt = new DateTimeImmutable();
        $this->completed = false;
        $this->progress = 0;
    }

    public function __toString(): string
    {
        return 'Challenge for ' . $this->activity->getUser()->getUsername();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function isCompleted(): ?bool
    {
        return $this->completed;
    }

    public function setCompleted(bool $completed): static
    {
        $this->completed = $completed;

        return $this;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function setActivity(?Activity $activity): static
    {
        $this->activity = $activity;

        return $this;
    }

    public function getChallenge(): ?string
    {
        return $this->challenge;
    }

    public function setChallenge(string $challenge): static
    {
        $this->challenge = $challenge;

        return $this;
    }

    public function getGoal(): ?int
    {
        return $this->goal;
    }

    public function setGoal(int $goal): static
    {
        $this->goal = $goal;

        return $this;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(float $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getStartAt(): ?DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(DateTimeImmutable $startAt): static
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getEndAt(): ?DateTimeImmutable
    {
        return $this->endAt;
    }

    public function setEndAt(DateTimeImmutable $endAt): static
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function inProgress(): bool
    {
        $now = new DateTimeImmutable();
        return $this->endAt > $now && $this->startAt < $now;
    }

    public function getProgress(): ?int
    {
        return $this->progress;
    }

    public function setProgress(?int $progress): void
    {
        $this->progress = $progress;
    }
}
