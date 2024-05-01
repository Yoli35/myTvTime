<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
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
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource]
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

    #[Assert\Timezone]
    #[ORM\Column(length: 255, nullable: true, options: ['default' => 'Europe/Paris'])]
    private ?string $timezone = null;

    #[ORM\ManyToMany(targetEntity: Movie::class, inversedBy: 'users')]
    private Collection $movies;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Article::class)]
    private Collection $articles;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: MovieList::class, orphanRemoval: true)]
    private Collection $movieLists;

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

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Alert::class, orphanRemoval: true)]
    private Collection $alerts;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: YoutubeVideoComment::class, orphanRemoval: true)]
    private Collection $youtubeVideoComments;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Alarm::class, orphanRemoval: true)]
    private Collection $alarms;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Contribution::class)]
    private Collection $contributions;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserTvPreference::class, orphanRemoval: true)]
    private Collection $userTvPreferences;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserYVideo::class)]
    private Collection $userYVideos;

    /**
     * @var Collection<int, YoutubePlaylist>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: YoutubePlaylist::class, orphanRemoval: true)]
    private Collection $youtubePlaylists;

    /**
     * @var Collection<int, MovieVideo>
     */
    #[ORM\ManyToMany(targetEntity: MovieVideo::class, mappedBy: 'users')]
    private Collection $movieVideos;

    public function __construct()
    {
        $this->alarms = new ArrayCollection();
        $this->alerts = new ArrayCollection();
        $this->articles = new ArrayCollection();
        $this->chatDiscussions = new ArrayCollection();
        $this->contributions = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->friends = new ArrayCollection();
        $this->movieLists = new ArrayCollection();
        $this->movies = new ArrayCollection();
        $this->serieViewings = new ArrayCollection();
        $this->series = new ArrayCollection();
        $this->userTvPreferences = new ArrayCollection();
        $this->userYVideos = new ArrayCollection();
        $this->youtubeVideoComments = new ArrayCollection();
        $this->youtubeVideos = new ArrayCollection();
        $this->youtubePlaylists = new ArrayCollection();
        $this->movieVideos = new ArrayCollection();
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
    public function eraseCredentials(): void
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
            $movieList->setUser($this);
        }

        return $this;
    }

    public function removeMovieList(MovieList $movieList): self
    {
        if ($this->movieLists->removeElement($movieList)) {
            // set the owning side to null (unless already changed)
            if ($movieList->getUser() === $this) {
                $movieList->setUser(null);
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
            return $lastActivityAt >= $date->sub(new DateInterval('PT10M'));
        }
    }

    /**
     * @return Collection<int, Alert>
     */
    public function getAlerts(): Collection
    {
        return $this->alerts;
    }

    /**
     * @return Collection<int, YoutubeVideoComment>
     */
    public function getYoutubeVideoComments(): Collection
    {
        return $this->youtubeVideoComments;
    }

    public function addYoutubeVideoComment(YoutubeVideoComment $youtubeVideoComment): static
    {
        if (!$this->youtubeVideoComments->contains($youtubeVideoComment)) {
            $this->youtubeVideoComments->add($youtubeVideoComment);
            $youtubeVideoComment->setUser($this);
        }

        return $this;
    }

    public function removeYoutubeVideoComment(YoutubeVideoComment $youtubeVideoComment): static
    {
        if ($this->youtubeVideoComments->removeElement($youtubeVideoComment)) {
            // set the owning side to null (unless already changed)
            if ($youtubeVideoComment->getUser() === $this) {
                $youtubeVideoComment->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Alarm>
     */
    public function getAlarms(): Collection
    {
        return $this->alarms;
    }

    public function addAlarm(Alarm $alarm): static
    {
        if (!$this->alarms->contains($alarm)) {
            $this->alarms->add($alarm);
            $alarm->setOwner($this);
        }

        return $this;
    }

    public function removeAlarm(Alarm $alarm): static
    {
        if ($this->alarms->removeElement($alarm)) {
            // set the owning side to null (unless already changed)
            if ($alarm->getOwner() === $this) {
                $alarm->setOwner(null);
            }
        }

        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * @return Collection<int, Contribution>
     */
    public function getContributions(): Collection
    {
        return $this->contributions;
    }

    public function addContribution(Contribution $contribution): static
    {
        if (!$this->contributions->contains($contribution)) {
            $this->contributions->add($contribution);
            $contribution->setUser($this);
        }

        return $this;
    }

    public function removeContribution(Contribution $contribution): static
    {
        if ($this->contributions->removeElement($contribution)) {
            // set the owning side to null (unless already changed)
            if ($contribution->getUser() === $this) {
                $contribution->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserTvPreference>
     */
    public function getUserTvPreferences(): Collection
    {
        return $this->userTvPreferences;
    }

    public function addUserTvPreference(UserTvPreference $userTvPreference): static
    {
        if (!$this->userTvPreferences->contains($userTvPreference)) {
            $this->userTvPreferences->add($userTvPreference);
            $userTvPreference->setUser($this);
        }

        return $this;
    }

    public function removeUserTvPreference(UserTvPreference $userTvPreference): static
    {
        if ($this->userTvPreferences->removeElement($userTvPreference)) {
            // set the owning side to null (unless already changed)
            if ($userTvPreference->getUser() === $this) {
                $userTvPreference->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserYVideo>
     */
    public function getUserYVideos(): Collection
    {
        return $this->userYVideos;
    }

    public function addUserYVideo(UserYVideo $userYVideo): static
    {
        if (!$this->userYVideos->contains($userYVideo)) {
            $this->userYVideos->add($userYVideo);
            $userYVideo->setUser($this);
        }

        return $this;
    }

    public function removeUserYVideo(UserYVideo $userYVideo): static
    {
        if ($this->userYVideos->removeElement($userYVideo)) {
            // set the owning side to null (unless already changed)
            if ($userYVideo->getUser() === $this) {
                $userYVideo->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, YoutubePlaylist>
     */
    public function getYoutubePlaylists(): Collection
    {
        return $this->youtubePlaylists;
    }

    public function addYoutubePlaylist(YoutubePlaylist $youtubePlaylist): static
    {
        if (!$this->youtubePlaylists->contains($youtubePlaylist)) {
            $this->youtubePlaylists->add($youtubePlaylist);
            $youtubePlaylist->setUser($this);
        }

        return $this;
    }

    public function removeYoutubePlaylist(YoutubePlaylist $youtubePlaylist): static
    {
        if ($this->youtubePlaylists->removeElement($youtubePlaylist)) {
            // set the owning side to null (unless already changed)
            if ($youtubePlaylist->getUser() === $this) {
                $youtubePlaylist->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MovieVideo>
     */
    public function getMovieVideos(): Collection
    {
        return $this->movieVideos;
    }

    public function addMovieVideo(MovieVideo $movieVideo): static
    {
        if (!$this->movieVideos->contains($movieVideo)) {
            $this->movieVideos->add($movieVideo);
            $movieVideo->addUser($this);
        }

        return $this;
    }

    public function removeMovieVideo(MovieVideo $movieVideo): static
    {
        if ($this->movieVideos->removeElement($movieVideo)) {
            $movieVideo->removeUser($this);
        }

        return $this;
    }
}
