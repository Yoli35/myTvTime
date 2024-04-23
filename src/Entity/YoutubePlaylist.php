<?php

namespace App\Entity;

use App\Repository\YoutubePlaylistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $thumbnailUrl = null;

    #[ORM\Column(nullable: true)]
    private ?int $numberOfVideos = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastUpdateAt = null;

    /**
     * @var Collection<int, YoutubePlaylistVideo>
     */
    #[ORM\OneToMany(mappedBy: 'playlist', targetEntity: YoutubePlaylistVideo::class, orphanRemoval: true)]
    private Collection $youtubePlaylistVideos;

    public function __construct()
    {
        $this->youtubePlaylistVideos = new ArrayCollection();
    }

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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

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

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnailUrl;
    }

    public function setThumbnailUrl(?string $thumbnailUrl): static
    {
        $this->thumbnailUrl = $thumbnailUrl;

        return $this;
    }

    public function getLastUpdateAt(): ?\DateTimeImmutable
    {
        return $this->lastUpdateAt;
    }

    public function setLastUpdateAt(?\DateTimeImmutable $lastUpdateAt): static
    {
        $this->lastUpdateAt = $lastUpdateAt;

        return $this;
    }

    /**
     * @return Collection<int, YoutubePlaylistVideo>
     */
    public function getYoutubePlaylistVideos(): Collection
    {
        return $this->youtubePlaylistVideos;
    }

    public function addYoutubePlaylistVideo(YoutubePlaylistVideo $youtubePlaylistVideo): static
    {
        if (!$this->youtubePlaylistVideos->contains($youtubePlaylistVideo)) {
            $this->youtubePlaylistVideos->add($youtubePlaylistVideo);
            $youtubePlaylistVideo->setPlaylist($this);
        }

        return $this;
    }

    public function removeYoutubePlaylistVideo(YoutubePlaylistVideo $youtubePlaylistVideo): static
    {
        if ($this->youtubePlaylistVideos->removeElement($youtubePlaylistVideo)) {
            // set the owning side to null (unless already changed)
            if ($youtubePlaylistVideo->getPlaylist() === $this) {
                $youtubePlaylistVideo->setPlaylist(null);
            }
        }

        return $this;
    }
}
