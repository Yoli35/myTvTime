<?php

namespace App\Entity;

use App\Repository\UserYVideoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserYVideoRepository::class)]
class UserYVideo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'userYVideos')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'userYVideos')]
    private ?YoutubeVideo $video;

    #[ORM\Column]
    private ?bool $hidden = null;

    public function __construct()
    {
    }

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

    public function getVideo(): YoutubeVideo
    {
        return $this->video;
    }

    public function setVideo(?YoutubeVideo $video): static
    {
        $this->video = $video;

        return $this;
    }

    public function isHidden(): ?bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): static
    {
        $this->hidden = $hidden;

        return $this;
    }
}
