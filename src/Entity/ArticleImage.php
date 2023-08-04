<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ArticleImageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ArticleImageRepository::class)]
#[ApiResource]
class ArticleImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['articleImage:item', 'articleImage:list'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['articleImage:item', 'articleImage:list'])]
    private ?string $path = null;

    #[ORM\ManyToOne(inversedBy: 'articleImages')]
    #[Groups(['articleImage:item', 'articleImage:list'])]
    private ?Article $article = null;

    #[Groups(['articleImage:item'])]
    private string $imagesPath = '/images/articles/images/';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): self
    {
        $this->article = $article;

        return $this;
    }

    public function __toString()
    {
        return $this->path;
    }

    public function getImagesPath(): string
    {
        return $this->imagesPath;
    }
}
