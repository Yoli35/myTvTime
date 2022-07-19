<?php

namespace App\Entity;

use App\Repository\TikTokVideoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TikTokVideoRepository::class)]
class TikTokVideo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $version;

    #[ORM\Column(type: 'string', length: 255)]
    private $type;

    #[ORM\Column(type: 'string', length: 32)]
    private $videoId;

    #[ORM\Column(type: 'text', nullable: true)]
    private $title;

    #[ORM\Column(type: 'string', length: 255)]
    private $authorUrl;

    #[ORM\Column(type: 'string', length: 255)]
    private $authorName;

    #[ORM\Column(type: 'string', length: 8)]
    private $width;

    #[ORM\Column(type: 'string', length: 8)]
    private $height;

    #[ORM\Column(type: 'text')]
    private $html;

    #[ORM\Column(type: 'integer')]
    private $thumbnailWidth;

    #[ORM\Column(type: 'integer')]
    private $thumbnailHeight;

    #[ORM\Column(type: 'string', length: 255)]
    private $thumbnailUrl;

    #[ORM\Column]
    private ?bool $thumbnailHasExpired = null;

    #[ORM\Column(type: 'string', length: 255)]
    private $providerUrl;

    #[ORM\Column(type: 'string', length: 255)]
    private $providerName;

    #[ORM\Column(type: 'datetime')]
    private $addedAt;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'tiktoks')]
    private $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getAuthorUrl(): ?string
    {
        return $this->authorUrl;
    }

    public function setAuthorUrl(string $authorUrl): self
    {
        $this->authorUrl = $authorUrl;

        return $this;
    }

    public function getAuthorName(): ?string
    {
        return $this->authorName;
    }

    public function setAuthorName(string $authorName): self
    {
        $this->authorName = $authorName;

        return $this;
    }

    public function getWidth(): ?string
    {
        return $this->width;
    }

    public function setWidth(string $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?string
    {
        return $this->height;
    }

    public function setHeight(string $height): self
    {
        $this->height = $height;

        return $this;
    }

    public function getHtml(): ?string
    {
        return $this->html;
    }

    public function setHtml(string $html): self
    {
        $this->html = $html;

        return $this;
    }

    public function getThumbnailWidth(): ?int
    {
        return $this->thumbnailWidth;
    }

    public function setThumbnailWidth(int $thumbnailWidth): self
    {
        $this->thumbnailWidth = $thumbnailWidth;

        return $this;
    }

    public function getThumbnailHeight(): ?int
    {
        return $this->thumbnailHeight;
    }

    public function setThumbnailHeight(int $thumbnailHeight): self
    {
        $this->thumbnailHeight = $thumbnailHeight;

        return $this;
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnailUrl;
    }

    public function setThumbnailUrl(string $thumbnailUrl): self
    {
        $this->thumbnailUrl = $thumbnailUrl;

        return $this;
    }

    public function getProviderUrl(): ?string
    {
        return $this->providerUrl;
    }

    public function setProviderUrl(string $providerUrl): self
    {
        $this->providerUrl = $providerUrl;

        return $this;
    }

    public function getProviderName(): ?string
    {
        return $this->providerName;
    }

    public function setProviderName(string $providerName): self
    {
        $this->providerName = $providerName;

        return $this;
    }

    public function getVideoId(): ?string
    {
        return $this->videoId;
    }

    public function setVideoId(string $videoId): self
    {
        $this->videoId = $videoId;

        return $this;
    }

    public function getAddedAt(): ?\DateTimeInterface
    {
        return $this->addedAt;
    }

    public function setAddedAt(\DateTimeInterface $addedAt): self
    {
        $this->addedAt = $addedAt;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->addTiktok($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            $user->removeTiktok($this);
        }

        return $this;
    }

    public function isThumbnailHasExpired(): ?bool
    {
        return $this->thumbnailHasExpired;
    }

    public function setThumbnailHasExpired(bool $thumbnailHasExpired): self
    {
        $this->thumbnailHasExpired = $thumbnailHasExpired;

        return $this;
    }
}
