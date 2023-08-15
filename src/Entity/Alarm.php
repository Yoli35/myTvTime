<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Trait\CreateAtTrait;
use App\Entity\Trait\UserTrait;
use App\Repository\AlarmRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlarmRepository::class)]
#[ApiResource]
class Alarm
{
    use CreateAtTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'alarms')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner;

    #[ORM\Column(length: 255)]
    private ?string $name;

    #[ORM\Column]
    private ?int $recurrence;

    #[ORM\Column(nullable: true)]
    private ?bool $recurrenceByDays;

    #[ORM\Column(options: ['min' => 1, 'default' => 1])]
    private ?int $days;

    #[ORM\Column]
    private ?bool $active = true;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?DateTimeInterface $time = null;

    public function __construct(User $user, string $name, bool $recurrenceByDays, int $days = 1, string $description = null)
    {
        $this->owner = $user;
        $this->name = $name;
        $this->recurrenceByDays = $recurrenceByDays;
        $this->days = $days;
        $this->description = $description;
        $this->createdAt = new DateTimeImmutable('now', 'Europe/Paris');
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

    public function getRecurrence(): ?int
    {
        return $this->recurrence;
    }

    public function setRecurrence(int $recurrence): static
    {
        $this->recurrence = $recurrence;

        return $this;
    }

    public function getMonday(): bool
    {
        return $this->recurrence & 1;
    }

    public function getTuesday(): bool
    {
        return $this->recurrence & 2;
    }

    public function getWednesday(): bool
    {
        return $this->recurrence & 4;
    }

    public function getThursday(): bool
    {
        return $this->recurrence & 8;
    }

    public function getFriday(): bool
    {
        return $this->recurrence & 16;
    }

    public function getSaturday(): bool
    {
        return $this->recurrence & 32;
    }

    public function getSunday(): bool
    {
        return $this->recurrence & 64;
    }

    public function addMonday(): static
    {
        $this->recurrence = $this->recurrence | 1;

        return $this;
    }

    public function addTuesday(): static
    {
        $this->recurrence = $this->recurrence | 2;

        return $this;
    }

    public function addWednesday(): static
    {
        $this->recurrence = $this->recurrence | 4;

        return $this;
    }

    public function addThursday(): static
    {
        $this->recurrence = $this->recurrence | 8;

        return $this;
    }

    public function addFriday(): static
    {
        $this->recurrence = $this->recurrence | 16;

        return $this;
    }

    public function addSaturday(): static
    {
        $this->recurrence = $this->recurrence | 32;

        return $this;
    }

    public function addSunday(): static
    {
        $this->recurrence = $this->recurrence | 64;

        return $this;
    }

    public function removeMonday(): static
    {
        $this->recurrence = $this->recurrence & ~1;

        return $this;
    }

    public function removeTuesday(): static
    {
        $this->recurrence = $this->recurrence & ~2;

        return $this;
    }

    public function removeWednesday(): static
    {
        $this->recurrence = $this->recurrence & ~4;

        return $this;
    }

    public function removeThursday(): static
    {
        $this->recurrence = $this->recurrence & ~8;

        return $this;
    }

    public function removeFriday(): static
    {
        $this->recurrence = $this->recurrence & ~16;

        return $this;
    }

    public function removeSaturday(): static
    {
        $this->recurrence = $this->recurrence & ~32;

        return $this;
    }

    public function removeSunday(): static
    {
        $this->recurrence = $this->recurrence & ~64;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isRecurrenceByDays(): ?bool
    {
        return $this->recurrenceByDays;
    }

    public function setRecurrenceByDays(bool $recurrenceByDays): static
    {
        $this->recurrenceByDays = $recurrenceByDays;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): void
    {
        $this->active = $active;
    }

    public function getDays(): ?int
    {
        return $this->days;
    }

    public function setDays(?int $days): void
    {
        $this->days = $days;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getTime(): ?DateTimeInterface
    {
        return $this->time;
    }

    public function setTime(DateTimeInterface $time): static
    {
        $this->time = $time;

        return $this;
    }
}
