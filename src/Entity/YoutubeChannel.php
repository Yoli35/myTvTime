<?php

namespace App\Entity;

use App\Repository\YoutubeChannelRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: YoutubeChannelRepository::class)]
class YoutubeChannel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $youtubeId;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $customUrl;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $publishedAt;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $thumbnailDefaultUrl;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $thumbnailMediumUrl;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $thumbnailHighUrl;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $localizedTitle;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $localizedDescription;

    #[ORM\Column(type: 'string', length: 16, nullable: true)]
    private ?string $country;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastUpdateAt = null;

    public function __toString(): string
    {
        return $this->title;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getYoutubeId(): ?string
    {
        return $this->youtubeId;
    }

    public function setYoutubeId(string $youtubeId): self
    {
        $this->youtubeId = $youtubeId;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCustomUrl(): ?string
    {
        return $this->customUrl;
    }

    public function setCustomUrl(?string $customUrl): self
    {
        $this->customUrl = $customUrl;

        return $this;
    }

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?DateTimeImmutable $publishedAt): self
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getThumbnailDefaultUrl(): ?string
    {
        return $this->thumbnailDefaultUrl;
    }

    public function setThumbnailDefaultUrl(?string $thumbnailDefaultUrl): self
    {
        $this->thumbnailDefaultUrl = $thumbnailDefaultUrl;

        return $this;
    }

    public function getThumbnailMediumUrl(): ?string
    {
        return $this->thumbnailMediumUrl;
    }

    public function setThumbnailMediumUrl(?string $thumbnailMediumUrl): self
    {
        $this->thumbnailMediumUrl = $thumbnailMediumUrl;

        return $this;
    }

    public function getThumbnailHighUrl(): ?string
    {
        return $this->thumbnailHighUrl;
    }

    public function setThumbnailHighUrl(?string $thumbnailHighUrl): self
    {
        $this->thumbnailHighUrl = $thumbnailHighUrl;

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

    public function getLocalizedDescription(): ?string
    {
        return $this->localizedDescription;
    }

    public function setLocalizedDescription(?string $localizedDescription): self
    {
        $this->localizedDescription = $localizedDescription;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

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
