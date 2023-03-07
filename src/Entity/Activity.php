<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ActivityDayRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityDayRepository::class)]
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
    private ?int $standUpGoal = null;

    #[ORM\Column]
    private ?int $moveGoal = null;

    #[ORM\Column]
    private ?int $exerciceGoal = null;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'activity', targetEntity: ActivityDay::class)]
    private Collection $activityDays;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->createdAt = new DateTimeImmutable();
        $this->activityDays = new ArrayCollection();
        $this->standUpGoal = 12;
        $this->moveGoal = 500;
        $this->exerciceGoal = 30;
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

    public function getExerciceGoal(): ?int
    {
        return $this->exerciceGoal;
    }

    public function setExerciceGoal(int $exerciceGoal): self
    {
        $this->exerciceGoal = $exerciceGoal;

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
            // set the owning side to null (unless already changed)
            if ($activityDay->getActivity() === $this) {
                $activityDay->setActivity(null);
            }
        }

        return $this;
    }
}
