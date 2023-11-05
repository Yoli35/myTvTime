<?php

namespace App\Entity;

use App\Repository\UserTvPreferenceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserTvPreferenceRepository::class)]
class UserTvPreference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'userTvPreferences')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TvGenre $tvGenre = null;

    #[ORM\Column]
    private ?int $vitality = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getTvGenre(): ?TvGenre
    {
        return $this->tvGenre;
    }

    public function setTvGenre(?TvGenre $tvGenre): static
    {
        $this->tvGenre = $tvGenre;

        return $this;
    }

    public function getVitality(): ?int
    {
        return $this->vitality;
    }

    public function setVitality(int $vitality): static
    {
        $this->vitality = $vitality;

        return $this;
    }
}
