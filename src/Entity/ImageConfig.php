<?php

namespace App\Entity;

use App\Repository\ImageConfigRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImageConfigRepository::class)]
class ImageConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $base_url;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $secure_base_url;

    #[ORM\Column(type: 'array')]
    private array $backdrop_sizes = [];

    #[ORM\Column(type: 'array')]
    private array $logo_sizes = [];

    #[ORM\Column(type: 'array')]
    private array $poster_sizes = [];

    #[ORM\Column(type: 'array')]
    private array $profile_sizes = [];

    #[ORM\Column(type: 'array')]
    private array $still_sizes = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBaseUrl(): ?string
    {
        return $this->base_url;
    }

    public function setBaseUrl(string $base_url): self
    {
        $this->base_url = $base_url;

        return $this;
    }

    public function getSecureBaseUrl(): ?string
    {
        return $this->secure_base_url;
    }

    public function setSecureBaseUrl(string $secure_base_url): self
    {
        $this->secure_base_url = $secure_base_url;

        return $this;
    }

    public function getBackdropSizes(): ?array
    {
        return $this->backdrop_sizes;
    }

    public function setBackdropSizes(array $backdrop_sizes): self
    {
        $this->backdrop_sizes = $backdrop_sizes;

        return $this;
    }

    public function getLogoSizes(): ?array
    {
        return $this->logo_sizes;
    }

    public function setLogoSizes(array $logo_sizes): self
    {
        $this->logo_sizes = $logo_sizes;

        return $this;
    }

    public function getPosterSizes(): ?array
    {
        return $this->poster_sizes;
    }

    public function setPosterSizes(array $poster_sizes): self
    {
        $this->poster_sizes = $poster_sizes;

        return $this;
    }

    public function getProfileSizes(): ?array
    {
        return $this->profile_sizes;
    }

    public function setProfileSizes(array $profile_sizes): self
    {
        $this->profile_sizes = $profile_sizes;

        return $this;
    }

    public function getStillSizes(): ?array
    {
        return $this->still_sizes;
    }

    public function setStillSizes(array $still_sizes): self
    {
        $this->still_sizes = $still_sizes;

        return $this;
    }
}
