<?php

namespace App\Entity;

use App\Repository\YoutubeVideoRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: YoutubeVideoRepository::class)]
class YoutubeVideo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $link;

    #[ORM\Column(type: 'integer')]
    private ?int $userId;

    #[ORM\Column(type: 'integer')]
    private ?int $categoryId;

    #[ORM\Column(type: 'string', length: 8, nullable: true)]
    private ?string $defaultAudioLanguage;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?DateTimeImmutable $publishedAt;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $title;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $thumbnailDefaultPath;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $thumbnailMediumPath;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $thumbnailHighPath;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $thumbnailStandardPath;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $thumbnailMaxresPath;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $localizedDescription;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $localizedTitle;

    #[ORM\Column(type: 'string', length: 8)]
    private ?string $contentDefinition;

    #[ORM\Column(type: 'string', length: 8)]
    private ?string $contentDimension;

    #[ORM\Column(type: 'integer')]
    private ?string $contentDuration;

    #[ORM\Column(type: 'string', length: 16)]
    private ?string $contentProjection;

    #[ORM\ManyToOne(targetEntity: YoutubeChannel::class)]
    private ?YoutubeChannel $channel;

    #[ORM\Column]
    private ?DateTimeImmutable $addedAt = null;

    #[ORM\ManyToMany(targetEntity: YoutubeVideoTag::class, mappedBy: 'ytVideos')]
    private Collection $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

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

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(DateTimeImmutable $publishedAt): self
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

    public function getAddedAt(): ?DateTimeImmutable
    {
        return $this->addedAt;
    }

    public function setAddedAt(DateTimeImmutable $addedAt): self
    {
        $this->addedAt = $addedAt;

        return $this;
    }

    /**
     * @return Collection<int, YoutubeVideoTag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(YoutubeVideoTag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
            $tag->addYtVideo($this);
        }

        return $this;
    }

    public function removeTag(YoutubeVideoTag $tag): self
    {
        if ($this->tags->removeElement($tag)) {
            $tag->removeYtVideo($this);
        }

        return $this;
    }
}
