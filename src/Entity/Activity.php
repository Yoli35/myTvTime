<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ActivityRepository;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    private ?User $user;

    #[ORM\Column]
    private ?int $standUpGoal;

    #[ORM\Column]
    private ?int $moveGoal;

    #[ORM\Column]
    private ?int $exerciseGoal;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'activity', targetEntity: ActivityMoveGoal::class)]
    private Collection $moveGoals;

    #[ORM\OneToMany(mappedBy: 'activity', targetEntity: ActivityExerciseGoal::class)]
    private Collection $exerciseGoals;

    #[ORM\OneToMany(mappedBy: 'activity', targetEntity: ActivityStandUpGoal::class)]
    private Collection $standUpGoals;

    #[ORM\OneToMany(mappedBy: 'activity', targetEntity: ActivityDay::class)]
    private Collection $activityDays;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->createdAt = new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));
        $this->activityDays = new ArrayCollection();
        $this->standUpGoal = 12;
        $this->moveGoal = 500;
        $this->exerciseGoal = 30;

        $this->moveGoals = new ArrayCollection();
        $this->exerciseGoals = new ArrayCollection();
        $this->standUpGoals = new ArrayCollection();
    }

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

    public function getExerciseGoal(): ?int
    {
        return $this->exerciseGoal;
    }

    public function setExerciseGoal(int $exerciseGoal): self
    {
        $this->exerciseGoal = $exerciseGoal;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, ActivityDay>
     */
    public function getActivityDays(): Collection
    {
        return $this->activityDays;
    }

    public function addActivityDay(ActivityDay $activityDay): self
    {
        if (!$this->activityDays->contains($activityDay)) {
            $this->activityDays->add($activityDay);
            $activityDay->setActivity($this);
        }

        return $this;
    }

    public function removeActivityDay(ActivityDay $activityDay): self
    {
        if ($this->activityDays->removeElement($activityDay)) {
            if ($activityDay->getActivity() === $this) {
                $activityDay->setActivity(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ActivityMoveGoal>
     */
    public function getMoveGoals(): Collection
    {
        return $this->moveGoals;
    }

    public function addMoveGoal(ActivityMoveGoal $moveGoal): self
    {
        if (!$this->moveGoals->contains($moveGoal)) {
            $this->moveGoals->add($moveGoal);
            $moveGoal->setActivity($this);
        }

        return $this;
    }

    public function removeGoal(ActivityMoveGoal $moveGoal): self
    {
        if ($this->moveGoals->removeElement($moveGoal)) {
            if ($moveGoal->getActivity() === $this) {
                $moveGoal->setActivity(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ActivityMoveGoal>
     */
    public function getExerciseGoals(): Collection
    {
        return $this->exerciseGoals;
    }

    public function addExerciseGoal(ActivityExerciseGoal $exerciseGoal): self
    {
        if (!$this->exerciseGoals->contains($exerciseGoal)) {
            $this->exerciseGoals->add($exerciseGoal);
            $exerciseGoal->setActivity($this);
        }

        return $this;
    }

    public function removeExerciseGoal(ActivityExerciseGoal $exerciseGoal): self
    {
        if ($this->exerciseGoals->removeElement($exerciseGoal)) {
            // set the owning side to null (unless already changed)
            if ($exerciseGoal->getActivity() === $this) {
                $exerciseGoal->setActivity(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ActivityMoveGoal>
     */
    public function getStandUpGoals(): Collection
    {
        return $this->standUpGoals;
    }

    public function addStandUpGoal(ActivityStandUpGoal $standUpGoal): self
    {
        if (!$this->standUpGoals->contains($standUpGoal)) {
            $this->standUpGoals->add($standUpGoal);
            $standUpGoal->setActivity($this);
        }

        return $this;
    }

    public function removeStandUpGoal(ActivityStandUpGoal $standUpGoal): self
    {
        if ($this->standUpGoals->removeElement($standUpGoal)) {
            if ($standUpGoal->getActivity() === $this) {
                $standUpGoal->setActivity(null);
            }
        }

        return $this;
    }
}
