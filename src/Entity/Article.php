<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ArticleRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['article:item']]),
        new GetCollection(normalizationContext: ['groups' => ['article:list']])
    ],
    order: ['createdAt' => 'DESC'],
    paginationItemsPerPage: 4,
)]
#[ORM\Entity(repositoryClass: ArticleRepository::class)]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['article:item', 'article:list'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['article:item', 'article:list'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['article:item'])]
    private ?string $content = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['article:item'])]
    private ?string $abstract = null;

    #[ORM\ManyToOne(inversedBy: 'articles')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['article:item', 'article:list'])]
    private ?User $user = null;

    #[Groups(['article:item', 'article:list'])]
    private string $userEmail = '';

    #[Groups(['article:item', 'article:list'])]
    private string $userUsername = '';

    #[Groups(['article:item', 'article:list'])]
    private string $userAvatar = '';

    #[Groups(['article:item', 'article:list'])]
    private string $userAvatarPath = '/images/users/avatars/';

    #[ORM\Column]
    #[Groups(['article:item', 'article:list'])]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['article:item', 'article:list'])]
    private ?DateTime $updatedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['article:item', 'article:list'])]
    private ?DateTimeImmutable $publishedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['article:item', 'article:list'])]
    private ?string $thumbnail = null;

    #[Groups(['article:item', 'article:list'])]
    private string $thumbnailPath = '/images/articles/thumbnails/';

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['article:item', 'article:list'])]
    private ?string $banner = null;

    #[Groups(['article:item', 'article:list'])]
    private string $bannerPath = '/images/articles/banners/';

    #[Groups(['article:item'])]
    private array $images = [];

    #[ORM\OneToMany(mappedBy: 'article', targetEntity: ArticleImage::class)]
    private Collection $articleImages;

    #[Groups(['article:item', 'article:list'])]
    private string $imagesPath = '/images/articles/images/';

    #[ORM\Column]
    private ?bool $isPublished;

    #[ORM\OneToMany(mappedBy: 'article', targetEntity: Comment::class, orphanRemoval: true)]
    private Collection $comments;

    public function __construct()
    {
        $this->setCreatedAt(new DateTimeImmutable());
        $this->setUpdatedAt(new DateTime());
        $this->articleImages = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->isPublished = false;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getAbstract(): ?string
    {
        return $this->abstract;
    }

    public function setAbstract(?string $abstract): self
    {
        $this->abstract = $abstract;

        return $this;
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

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

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

    public function getBanner(): ?string
    {
        return $this->banner;
    }

    public function setBanner(?string $banner): self
    {
        $this->banner = $banner;

        return $this;
    }

    /**
     * @return Collection<int, ArticleImage>
     */
    public function getArticleImages(): Collection
    {
        return $this->articleImages;
    }

    public function addArticleImage(ArticleImage $articleImage): self
    {
        if (!$this->articleImages->contains($articleImage)) {
            $this->articleImages->add($articleImage);
            $articleImage->setArticle($this);
        }

        return $this;
    }

    public function removeArticleImage(ArticleImage $articleImage): self
    {
        if ($this->articleImages->removeElement($articleImage)) {
            // set the owning side to null (unless already changed)
            if ($articleImage->getArticle() === $this) {
                $articleImage->setArticle(null);
            }
        }

        return $this;
    }

    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    public function setThumbnail(?string $thumbnail): self
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    public function isIsPublished(): ?bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): self
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function __toString()
    {
        return $this->title;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setArticle($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getArticle() === $this) {
                $comment->setArticle(null);
            }
        }

        return $this;
    }

    public function getThumbnailPath(): string
    {
        return $this->thumbnailPath;
    }

    public function getBannerPath(): string
    {
        return $this->bannerPath;
    }

    public function getImagesPath(): string
    {
        return $this->imagesPath;
    }

    public function getUserEmail(): string
    {
        return $this->user?->getEmail();
    }

    public function getUserUsername(): string
    {
        return $this->user?->getUsername();
    }

    public function getUserAvatar(): ?string
    {
        return $this->user?->getAvatar();
    }

    public function getUserAvatarPath(): string
    {
        return $this->userAvatarPath;
    }

    public function getImages(): array
    {
        $images = [];
        foreach ($this->articleImages as $articleImage) {
            $images[] = $articleImage->getPath();
        }
        return $images;
    }
}
