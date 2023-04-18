<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\UserRepository;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['user:item']]),
        new GetCollection(normalizationContext: ['groups' => ['user:list']])
            ],
            order: ['username' => 'ASC'],
    paginationItemsPerPage: 10,
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['user:item', 'user:list'])]
    private ?int $id = null;

    #[Groups(['user:item', 'user:list'])]
    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string')]
    private string $password;

    #[Groups(['user:item', 'user:list'])]
    #[ORM\Column(type: 'string', length: 255)]
    private ?string $username;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[Groups(['user:item'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $avatar;

    #[Groups(['user:item'])]
    private string $avatarPath = '/images/users/avatars/';

    #[Groups(['user:item'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $banner;

    #[Groups(['user:item'])]
    private string $bannerPath = '/images/users/banners/';

    #[Groups(['user:item'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $city;

    #[Groups(['user:item'])]
    #[ORM\Column(type: 'string', length: 16, nullable: true)]
    private ?string $zipCode;

    #[Groups(['user:item'])]
    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    private ?string $country;

    #[Groups(['user:item'])]
    #[ORM\Column(length: 8, nullable: true)]
    private ?string $preferredLanguage = null;

    #[ORM\ManyToMany(targetEntity: Movie::class, inversedBy: 'users')]
    private Collection $movies;

    #[ORM\ManyToMany(targetEntity: TikTokVideo::class, inversedBy: 'users')]
    private Collection $tiktoks;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Article::class)]
    private Collection $articles;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: MovieCollection::class, orphanRemoval: true)]
    private Collection $movieCollections;

    #[ORM\ManyToMany(targetEntity: Serie::class, mappedBy: 'users')]
    private Collection $series;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Event::class, orphanRemoval: true)]
    private Collection $events;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: SerieViewing::class, cascade: ['persist', 'remove'])]
    private Collection $serieViewings;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Friend::class)]
    private Collection $friends;

    #[ORM\ManyToMany(targetEntity: YoutubeVideo::class, inversedBy: 'users')]
    private Collection $youtubeVideos;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Activity $activity = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ChatDiscussion::class, orphanRemoval: true)]
    private Collection $chatDiscussions;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $lastLogin = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $lastLogout = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $lastActivityAt = null;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
        $this->chatDiscussions = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->friends = new ArrayCollection();
        $this->movieCollections = new ArrayCollection();
        $this->movies = new ArrayCollection();
        $this->serieViewings = new ArrayCollection();
        $this->series = new ArrayCollection();
        $this->tiktoks = new ArrayCollection();
        $this->youtubeVideos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;

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

    public function __toString(): string
    {
        if ($this->username) return $this->username;
        return 'No Username ('.$this->email.')';
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): self
    {
        $this->zipCode = $zipCode;

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

    /**
     * @return Collection<int, Movie>
     */
    public function getMovies(): Collection
    {
        return $this->movies;
    }

    public function addMovie(Movie $movie): self
    {
        if (!$this->movies->contains($movie)) {
            $this->movies[] = $movie;
        }

        return $this;
    }

    public function removeMovie(Movie $movie): self
    {
        $this->movies->removeElement($movie);

        return $this;
    }

    /**
     * @return Collection<int, TikTokVideo>
     */
    public function getTiktoks(): Collection
    {
        return $this->tiktoks;
    }

    public function addTiktok(TikTokVideo $tiktok): self
    {
        if (!$this->tiktoks->contains($tiktok)) {
            $this->tiktoks[] = $tiktok;
        }

        return $this;
    }

    public function removeTiktok(TikTokVideo $tiktok): self
    {
        $this->tiktoks->removeElement($tiktok);

        return $this;
    }

    public function getPreferredLanguage(): ?string
    {
        return $this->preferredLanguage;
    }

    public function setPreferredLanguage(string $preferredLanguage): self
    {
        $this->preferredLanguage = $preferredLanguage;

        return $this;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(Article $article): self
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->setUser($this);
        }

        return $this;
    }

    public function removeArticle(Article $article): self
    {
        if ($this->articles->removeElement($article)) {
            // set the owning side to null (unless already changed)
            if ($article->getUser() === $this) {
                $article->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MovieCollection>
     */
    public function getMovieCollections(): Collection
    {
        return $this->movieCollections;
    }

    public function addMovieCollection(MovieCollection $movieCollection): self
    {
        if (!$this->movieCollections->contains($movieCollection)) {
            $this->movieCollections->add($movieCollection);
            $movieCollection->setUser($this);
        }

        return $this;
    }

    public function removeMovieCollection(MovieCollection $movieCollection): self
    {
        if ($this->movieCollections->removeElement($movieCollection)) {
            // set the owning side to null (unless already changed)
            if ($movieCollection->getUser() === $this) {
                $movieCollection->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Serie>
     */
    public function getSeries(): Collection
    {
        return $this->series;
    }

    public function addSeries(Serie $series): self
    {
        if (!$this->series->contains($series)) {
            $this->series->add($series);
            $series->addUser($this);
        }

        return $this;
    }

    public function removeSeries(Serie $series): self
    {
        if ($this->series->removeElement($series)) {
            $series->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): self
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setUser($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): self
    {
        if ($this->events->removeElement($event)) {
            // set the owning side to null (unless already changed)
            if ($event->getUser() === $this) {
                $event->setUser(null);
            }
        }

        return $this;
    }

    public function getSerieViewings(): Collection
    {
        return $this->serieViewings;
    }

    public function addSerieViewing(SerieViewing $serieViewing): self
    {
        if (!$this->serieViewings->contains($serieViewing)) {
            $this->serieViewings->add($serieViewing);
            $serieViewing->setUser($this);
        }

        return $this;
    }

    public function removeSerieViewing(SerieViewing $serieViewing): self
    {
        if ($this->serieViewings->removeElement($serieViewing)) {
            // set the owning side to null (unless already changed)
            if ($serieViewing->getUser() === $this) {
                $serieViewing->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Friend>
     */
    public function getFriends(): Collection
    {
        return $this->friends;
    }

    public function addFriend(Friend $friend): self
    {
        if (!$this->friends->contains($friend)) {
            $this->friends->add($friend);
            $friend->setOwner($this);
        }

        return $this;
    }

    public function removeFriend(Friend $friend): self
    {
        if ($this->friends->removeElement($friend)) {
            // set the owning side to null (unless already changed)
            if ($friend->getOwner() === $this) {
                $friend->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, YoutubeVideo>
     */
    public function getYoutubeVideos(): Collection
    {
        return $this->youtubeVideos;
    }

    public function addYoutubeVideo(YoutubeVideo $youtubeVideo): self
    {
        if (!$this->youtubeVideos->contains($youtubeVideo)) {
            $this->youtubeVideos->add($youtubeVideo);
        }

        return $this;
    }

    public function removeYoutubeVideo(YoutubeVideo $youtubeVideo): self
    {
        $this->youtubeVideos->removeElement($youtubeVideo);

        return $this;
    }

    public function getAvatarPath(): string
    {
        return $this->avatarPath;
    }

    public function getBannerPath(): string
    {
        return $this->bannerPath;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function setActivity(?Activity $activity): self
    {
        // unset the owning side of the relation if necessary
        if ($activity === null && $this->activity !== null) {
            $this->activity->setUser(null);
        }

        // set the owning side of the relation if necessary
        if ($activity !== null && $activity->getUser() !== $this) {
            $activity->setUser($this);
        }

        $this->activity = $activity;

        return $this;
    }

    /**
     * @return Collection<int, ChatDiscussion>
     */
    public function getChatDiscussions(): Collection
    {
        return $this->chatDiscussions;
    }

    public function addChatDiscussion(ChatDiscussion $chatDiscussion): self
    {
        if (!$this->chatDiscussions->contains($chatDiscussion)) {
            $this->chatDiscussions->add($chatDiscussion);
            $chatDiscussion->setUser($this);
        }

        return $this;
    }

    public function removeChatDiscussion(ChatDiscussion $chatDiscussion): self
    {
        if ($this->chatDiscussions->removeElement($chatDiscussion)) {
            // set the owning side to null (unless already changed)
            if ($chatDiscussion->getUser() === $this) {
                $chatDiscussion->setUser(null);
            }
        }

        return $this;
    }

    public function getLastLogin(): ?DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?DateTimeInterface $lastLogin): self
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getLastLogout(): ?DateTimeInterface
    {
        return $this->lastLogout;
    }

    public function setLastLogout(?DateTimeInterface $lastLogout): self
    {
        $this->lastLogout = $lastLogout;

        return $this;
    }

    public function getLastActivityAt(): ?DateTimeInterface
    {
        return $this->lastActivityAt;
    }

    public function setLastActivityAt(?DateTimeInterface $lastActivityAt): self
    {
        $this->lastActivityAt = $lastActivityAt;

        return $this;
    }

    public function isOnLine($date): bool
    {
        if ($this->lastLogout) {
            return false;
        } else {
            try {
                $lastActivityAt = new DateTimeImmutable($this->lastActivityAt->format('Y-m-d H:i:s'), new DateTimeZone('Europe/Paris'));
            } catch (Exception) {
                $lastActivityAt = $this->lastActivityAt;
            }
            return $lastActivityAt >= $date->sub(new DateInterval('PT5M'));
        }
    }
}
