<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ActivityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
#[ApiResource]
class Activity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'activity', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    #[ORM\Column]
    private ?bool $standUpRingCompleted = null;

    #[ORM\Column]
    private ?bool $moveRingCompleted = null;

    #[ORM\Column]
    private ?bool $exerciceRingCompleted = null;

    #[ORM\Column(type: Types::JSON)]
    private array $standUp = [];

    #[ORM\Column]
    private ?int $standUpGoal = null;

    #[ORM\Column]
    private ?int $standUpResult = null;

    #[ORM\Column]
    private ?int $moveGoal = null;

    #[ORM\Column]
    private ?int $moveResult = null;

    #[ORM\Column]
    private ?int $exerciceGoal = null;

    #[ORM\Column]
    private ?int $exerciceResult = null;

    #[ORM\Column]
    private ?int $steps = null;

    #[ORM\Column]
    private ?float $distance = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
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

    public function getStandUpGoal(): ?int
    {
        return $this->standUpGoal;
    }

    public function setStandUpGoal(int $standUpGoal): self
    {
        $this->standUpGoal = $standUpGoal;

        return $this;
    }

    public function getMoveGoal(): ?int
    {
        return $this->moveGoal;
    }

    public function setMoveGoal(int $moveGoal): self
    {
        $this->moveGoal = $moveGoal;

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

    public function getExerciceGoal(): ?int
    {
        return $this->exerciceGoal;
    }

    public function setExerciceGoal(int $exerciceGoal): self
    {
        $this->exerciceGoal = $exerciceGoal;

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
}
