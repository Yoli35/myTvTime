<?php

namespace App\Entity;

use App\Repository\YoutubeVideoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: YoutubeVideoRepository::class)]
class YoutubeVideo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $link;

    #[ORM\Column(type: 'integer')]
    private $userId;

    #[ORM\Column(type: 'integer')]
    private $categoryId;

    #[ORM\Column(type: 'string', length: 8, nullable: true)]
    private $defaultAudioLanguage;

    #[ORM\Column(type: 'text', nullable: true)]
    private $description;

    #[ORM\Column(type: 'datetime_immutable')]
    private $publishedAt;

    #[ORM\Column(type: 'string', length: 255)]
    private $title;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $thumbnailDefaultPath;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $thumbnailMediumPath;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $thumbnailHighPath;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $thumbnailStandardPath;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $thumbnailMaxresPath;

    #[ORM\Column(type: 'text', nullable: true)]
    private $localizedDescription;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $localizedTitle;

    #[ORM\Column(type: 'string', length: 8)]
    private $contentDefinition;

    #[ORM\Column(type: 'string', length: 8)]
    private $contentDimension;

    #[ORM\Column(type: 'integer')]
    private $contentDuration;

    #[ORM\Column(type: 'string', length: 16)]
    private $contentProjection;

    #[ORM\ManyToOne(targetEntity: YoutubeChannel::class, inversedBy: 'youtubeVideos')]
    private $channel;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(string $link): self
    {
        $this->link = $link;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function setCategoryId(int $categoryId): self
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    public function getDefaultAudioLanguage(): ?string
    {
        return $this->defaultAudioLanguage;
    }

    public function setDefaultAudioLanguage(string $defaultAudioLanguage): self
    {
        $this->defaultAudioLanguage = $defaultAudioLanguage;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(\DateTimeImmutable $publishedAt): self
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getThumbnailDefaultPath(): ?string
    {
        return $this->thumbnailDefaultPath;
    }

    public function setThumbnailDefaultPath(string $thumbnailDefaultPath): self
    {
        $this->thumbnailDefaultPath = $thumbnailDefaultPath;

        return $this;
    }

    public function getThumbnailMediumPath(): ?string
    {
        return $this->thumbnailMediumPath;
    }

    public function setThumbnailMediumPath(string $thumbnailMediumPath): self
    {
        $this->thumbnailMediumPath = $thumbnailMediumPath;

        return $this;
    }

    public function getThumbnailHighPath(): ?string
    {
        return $this->thumbnailHighPath;
    }

    public function setThumbnailHighPath(string $thumbnailHighPath): self
    {
        $this->thumbnailHighPath = $thumbnailHighPath;

        return $this;
    }

    public function getThumbnailStandardPath(): ?string
    {
        return $this->thumbnailStandardPath;
    }

    public function setThumbnailStandardPath(string $thumbnailStandardPath): self
    {
        $this->thumbnailStandardPath = $thumbnailStandardPath;

        return $this;
    }

    public function getThumbnailMaxresPath(): ?string
    {
        return $this->thumbnailMaxresPath;
    }

    public function setThumbnailMaxresPath(string $thumbnailMaxresPath): self
    {
        $this->thumbnailMaxresPath = $thumbnailMaxresPath;

        return $this;
    }

    public function getLocalizedDescription(): ?string
    {
        return $this->localizedDescription;
    }

    public function setLocalizedDescription(?string $localizedDescription): self
    {
        $this->localizedDescription = $localizedDescription;

        return $this;
    }

    public function getLocalizedTitle(): ?string
    {
        return $this->localizedTitle;
    }

    public function setLocalizedTitle(?string $localizedTitle): self
    {
        $this->localizedTitle = $localizedTitle;

        return $this;
    }

    public function getContentDefinition(): ?string
    {
        return $this->contentDefinition;
    }

    public function setContentDefinition(string $contentDefinition): self
    {
        $this->contentDefinition = $contentDefinition;

        return $this;
    }

    public function getContentDimension(): ?string
    {
        return $this->contentDimension;
    }

    public function setContentDimension(string $contentDimension): self
    {
        $this->contentDimension = $contentDimension;

        return $this;
    }

    public function getContentDuration(): ?string
    {
        return $this->contentDuration;
    }

    public function setContentDuration(string $contentDuration): self
    {
        $this->contentDuration = $contentDuration;

        return $this;
    }

    public function getContentProjection(): ?string
    {
        return $this->contentProjection;
    }

    public function setContentProjection(string $contentProjection): self
    {
        $this->contentProjection = $contentProjection;

        return $this;
    }

    public function getChannel(): ?YoutubeChannel
    {
        return $this->channel;
    }

    public function setChannel(?YoutubeChannel $channel): self
    {
        $this->channel = $channel;

        return $this;
    }
}
