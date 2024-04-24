<?php

namespace App\Entity;

use App\Repository\YoutubePlaylistVideoRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: YoutubePlaylistVideoRepository::class)]
class YoutubePlaylistVideo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'youtubePlaylistVideos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?YoutubePlaylist $playlist = null;

    #[ORM\Column(nullable: true)]
    private ?int $youtubeVideoId = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $youtubeVideoViewedAt = null;

    #[ORM\Column(length: 255)]
    private ?string $link = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $thumbnailUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $duration = null;

    #[ORM\Column]
    private ?DateTimeImmutable $publishedAt = null;

    #[ORM\Column(nullable: true)]
    private ?string $channelId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $channelThumbnail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $channelTitle = null;

    #[ORM\Column(nullable: true)]
    private ?int $viewCount = null;

    #[ORM\Column(nullable: true)]
    private ?int $likeCount = null;

    #[ORM\Column(nullable: true)]
    private ?int $favoriteCount = null;

    #[ORM\Column(nullable: true)]
    private ?int $commentCount = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastUpdateAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlaylist(): ?YoutubePlaylist
    {
        return $this->playlist;
    }

    public function setPlaylist(?YoutubePlaylist $playlist): static
    {
        $this->playlist = $playlist;

        return $this;
    }

    public function getYoutubeVideoId(): ?int
    {
        return $this->youtubeVideoId;
    }

    public function setYoutubeVideoId(?int $youtubeVideoId): static
    {
        $this->youtubeVideoId = $youtubeVideoId;

        return $this;
    }

    public function getYoutubeVideoViewedAt(): ?DateTimeImmutable
    {
        return $this->youtubeVideoViewedAt;
    }

    public function setYoutubeVideoViewedAt(?DateTimeImmutable $youtubeVideoViewedAt): static
    {
        $this->youtubeVideoViewedAt = $youtubeVideoViewedAt;

        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(string $link): static
    {
        $this->link = $link;

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

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(?string $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getChannelId(): ?string
    {
        return $this->channelId;
    }

    public function setChannelId(string $channelId): static
    {
        $this->channelId = $channelId;

        return $this;
    }

    public function getChannelThumbnail(): ?string
    {
        return $this->channelThumbnail;
    }

    public function setChannelThumbnail(?string $channelThumbnail): static
    {
        $this->channelThumbnail = $channelThumbnail;

        return $this;
    }

    public function getChannelTitle(): ?string
    {
        return $this->channelTitle;
    }

    public function setChannelTitle(?string $channelTitle): static
    {
        $this->channelTitle = $channelTitle;

        return $this;
    }

    public function getViewCount(): ?int
    {
        return $this->viewCount;
    }

    public function setViewCount(?int $viewCount): static
    {
        $this->viewCount = $viewCount;

        return $this;
    }

    public function getLikeCount(): ?int
    {
        return $this->likeCount;
    }

    public function setLikeCount(?int $likeCount): static
    {
        $this->likeCount = $likeCount;

        return $this;
    }

    public function getFavoriteCount(): ?int
    {
        return $this->favoriteCount;
    }

    public function setFavoriteCount(?int $favoriteCount): static
    {
        $this->favoriteCount = $favoriteCount;

        return $this;
    }

    public function getCommentCount(): ?int
    {
        return $this->commentCount;
    }

    public function setCommentCount(?int $commentCount): static
    {
        $this->commentCount = $commentCount;

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
}
