<?php

namespace App\Entity;

use App\Repository\VisitorLogRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VisitorLogRepository::class)]
class VisitorLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $username;

    #[ORM\Column(length: 255)]
    private ?string $url;

    #[ORM\Column(length: 255)]
    private ?string $ip;

    #[ORM\Column(length: 255)]
    private ?string $browser;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $platform;

    #[ORM\Column(type: Types::JSON)]
    private array $languages;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deviceName;

    public function __construct($username, $url, $ip, $browser, $platform, $languages, $deviceName)
    {
        $this->createdAt = new DateTimeImmutable();
        $this->username = $username;
        $this->url = $url;
        $this->ip = $ip;
        $this->browser = $browser;
        $this->platform = $platform;
        $this->languages = $languages;
        $this->deviceName = $deviceName;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getBrowser(): ?string
    {
        return $this->browser;
    }

    public function setBrowser(string $browser): self
    {
        $this->browser = $browser;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

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

    public function getLanguages(): array
    {
        return $this->languages;
    }

    public function setLanguages(array $languages): self
    {
        $this->languages = $languages;

        return $this;
    }

    public function getDeviceName(): ?string
    {
        return $this->deviceName;
    }

    public function setDeviceName(?string $deviceName): self
    {
        $this->deviceName = $deviceName;

        return $this;
    }

    public function getPlatform(): ?string
    {
        return $this->platform;
    }

    public function setPlatform(?string $platform): self
    {
        $this->platform = $platform;

        return $this;
    }
}
