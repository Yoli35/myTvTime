<?php

namespace App\Entity;

use App\Repository\YoutubePlaylistRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: YoutubePlaylistRepository::class)]
class YoutubePlaylist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $playlistId = null;

    #[ORM\ManyToOne(inversedBy: 'youtubePlaylists')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(nullable: true)]
    private ?int $numberOfVideos = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlaylistId(): ?string
    {
        return $this->playlistId;
    }

    public function setPlaylistId(string $playlistId): static
    {
        $this->playlistId = $playlistId;

        return $this;
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

    public function getNumberOfVideos(): ?int
    {
        return $this->numberOfVideos;
    }

    public function setNumberOfVideos(?int $numberOfVideos): static
    {
        $this->numberOfVideos = $numberOfVideos;

        return $this;
    }
}
