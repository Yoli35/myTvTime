<?php

namespace App\DTO;

class MovieDirectLink
{
    private string $name;
    private string $linkPath;
    private MovieProvider $provider;

    public function __construct(string $name, string $linkPath, MovieProvider $provider)
    {
        $this->name = $name;
        $this->linkPath = $linkPath;
        $this->provider = $provider;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getLinkPath(): string
    {
        return $this->linkPath;
    }

    public function setLinkPath(string $linkPath): void
    {
        $this->linkPath = $linkPath;
    }

    public function getProvider(): MovieProvider
    {
        return $this->provider;
    }

    public function setProvider(MovieProvider $provider): void
    {
        $this->provider = $provider;
    }
}