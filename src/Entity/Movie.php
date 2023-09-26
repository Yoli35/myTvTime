<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\MovieRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MovieRepository::class)]
#[ApiResource]
class Movie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $title;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $originalTitle;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $posterPath;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $releaseDate;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $movieDbId;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'movies')]
    private $users;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $runtime;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\ManyToMany(targetEntity: MovieList::class, mappedBy: 'movies')]
    private Collection $movieLists;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $overview_fr = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $overview_en = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $overview_de = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $overview_es = null;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
        $this->movieLists = new ArrayCollection();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'originalTitle' => $this->getOriginalTitle(),
            'posterPath' => $this->getPosterPath(),
            'releaseDate' => $this->getReleaseDate(),
            'movieDbId' => $this->getMovieDbId(),
            'runtime' => $this->getRuntime(),
            'createdAt' => $this->getCreatedAt(),
            'overview_fr' => $this->getOverviewFr(),
            'overview_en' => $this->getOverviewEn(),
            'overview_de' => $this->getOverviewDe(),
            'overview_es' => $this->getOverviewEs(),
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getOriginalTitle(): ?string
    {
        return $this->originalTitle;
    }

    public function setOriginalTitle(?string $originalTitle): self
    {
        $this->originalTitle = $originalTitle;

        return $this;
    }

    public function getPosterPath(): ?string
    {
        return $this->posterPath;
    }

    public function setPosterPath(?string $posterPath): self
    {
        $this->posterPath = $posterPath;

        return $this;
    }

    public function getReleaseDate(): ?string
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(?string $releaseDate): self
    {
        $this->releaseDate = $releaseDate;

        return $this;
    }

    public function getMovieDbId(): ?int
    {
        return $this->movieDbId;
    }

    public function setMovieDbId(?int $movieDbId): self
    {
        $this->movieDbId = $movieDbId;

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
            $user->addMovie($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            $user->removeMovie($this);
        }

        return $this;
    }

    public function getRuntime(): ?int
    {
        return $this->runtime;
    }

    public function setRuntime(?int $runtime): self
    {
        $this->runtime = $runtime;

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

    /**
     * @return Collection<int, MovieList>
     */
    public function getMovieLists(): Collection
    {
        return $this->movieLists;
    }

    public function addMovieList(MovieList $movieList): self
    {
        if (!$this->movieLists->contains($movieList)) {
            $this->movieLists->add($movieList);
            $movieList->addMovie($this);
        }

        return $this;
    }

    public function removeMovieList(MovieList $movieList): self
    {
        if ($this->movieLists->removeElement($movieList)) {
            $movieList->removeMovie($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function getOverviewFr(): ?string
    {
        return $this->overview_fr;
    }

    public function setOverviewFr(?string $overview_fr): self
    {
        $this->overview_fr = $overview_fr;

        return $this;
    }

    public function getOverviewEn(): ?string
    {
        return $this->overview_en;
    }

    public function setOverviewEn(?string $overview_en): self
    {
        $this->overview_en = $overview_en;

        return $this;
    }

    public function getOverviewDe(): ?string
    {
        return $this->overview_de;
    }

    public function setOverviewDe(?string $overview_de): self
    {
        $this->overview_de = $overview_de;

        return $this;
    }

    public function getOverviewEs(): ?string
    {
        return $this->overview_es;
    }

    public function setOverviewEs(?string $overview_es): self
    {
        $this->overview_es = $overview_es;

        return $this;
    }
}
