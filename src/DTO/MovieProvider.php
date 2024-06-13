<?php

namespace App\DTO;

class MovieProvider
{
    private array $displayPriorities = [];
    private int $displayPriority = 0;
    private string $logoPath = '';
    private string $providerName = '';
    private int $providerId = 0;

    public function __construct(array $displayPriorities, int $displayPriority, string $logoPath, string $providerName, int $providerId)
    {
        $this->displayPriorities = $displayPriorities;
        $this->displayPriority = $displayPriority;
        $this->logoPath = $logoPath;
        $this->providerName = $providerName;
        $this->providerId = $providerId;
    }

    public function getDisplayPriorities(): array
    {
        return $this->displayPriorities;
    }

    public function setDisplayPriorities(array $displayPriorities): void
    {
        $this->displayPriorities = $displayPriorities;
    }

    public function getDisplayPriority(): int
    {
        return $this->displayPriority;
    }

    public function setDisplayPriority(int $displayPriority): void
    {
        $this->displayPriority = $displayPriority;
    }

    public function getLogoPath(): string
    {
        return $this->logoPath;
    }

    public function setLogoPath(string $logoPath): void
    {
        $this->logoPath = $logoPath;
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }

    public function setProviderName(string $providerName): void
    {
        $this->providerName = $providerName;
    }

    public function getProviderId(): int
    {
        return $this->providerId;
    }

    public function setProviderId(int $providerId): void
    {
        $this->providerId = $providerId;
    }

}