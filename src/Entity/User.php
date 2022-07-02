<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private $email;

    #[ORM\Column(type: 'json')]
    private $roles = [];

    #[ORM\Column(type: 'string')]
    private string $password;

/*    private string $plainPassword;
    private string $confirmPlainPassword;*/

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $username;

    #[ORM\Column(type: 'boolean')]
    private $isVerified = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $avatar;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $banner;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $city;

    #[ORM\Column(type: 'string', length: 16, nullable: true)]
    private $zipCode;

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    private $country;

    #[ORM\ManyToMany(targetEntity: UserMovie::class, inversedBy: 'users')]
    private $movies;

    #[ORM\ManyToMany(targetEntity: TikTokVideo::class, inversedBy: 'users')]
    private $tiktoks;

    public function __construct()
    {
        $this->movies = new ArrayCollection();
        $this->tiktoks = new ArrayCollection();
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
        return (string) $this->email;
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
/*
    public function getPlainPassword(): string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $password): self
    {
        $this->plainPassword = $password;

        return $this;
    }

    public function getConfirmPlainPassword(): string
    {
        return $this->confirmPlainPassword;
    }

    public function setConfirmPlainPassword(string $password): self
    {
        $this->confirmPlainPassword = $password;

        return $this;
    }*/

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
        return 'No Usermame ('.$this->email.')';
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
     * @return Collection<int, UserMovie>
     */
    public function getMovies(): Collection
    {
        return $this->movies;
    }

    public function addMovie(UserMovie $movie): self
    {
        if (!$this->movies->contains($movie)) {
            $this->movies[] = $movie;
        }

        return $this;
    }

    public function removeMovie(UserMovie $movie): self
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
}
