<?php

namespace App\Entity;

use App\Repository\SerieCastRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SerieCastRepository::class)]
class SerieCast
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'serieCasts')]
    private ?Serie $serie = null;

    #[ORM\ManyToOne]
    private ?Cast $cast = null;

    #[ORM\Column]
    private ?bool $recurringCharacter = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $characterName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $knownForDepartment = null;

    #[ORM\Column(type: Types::JSON)]
    private array $episodes = [];

    #[ORM\Column]
    private ?bool $guestStar = null;

    public function __construct(Serie $serie, Cast $cast)
    {
        $this->serie = $serie;
        $this->cast = $cast;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSerie(): ?Serie
    {
        return $this->serie;
    }

    public function setSerie(?Serie $serie): static
    {
        $this->serie = $serie;

        return $this;
    }

    public function getCast(): ?Cast
    {
        return $this->cast;
    }

    public function setCast(?Cast $cast): static
    {
        $this->cast = $cast;

        return $this;
    }

    public function isRecurringCharacter(): ?bool
    {
        return $this->recurringCharacter;
    }

    public function setRecurringCharacter(bool $recurringCharacter): self
    {
        $this->recurringCharacter = $recurringCharacter;

        return $this;
    }

    public function getEpisodes(): array
    {
        return $this->episodes;
    }

    public function getEpisodesString(): string
    {
        $episodes = [];
        foreach ($this->episodes as $episode) {
            $episodes[] = sprintf('S%02dE%02d', $episode[0], $episode[1]);
        }

        return implode(', ', $episodes);
    }

    public function getEpisodesCount(): int
    {
        return count($this->episodes);
    }

    public function setEpisodes(array $episodes): self
    {
        $this->episodes = $episodes;

        return $this;
    }

    public function addEpisode(int $seasonNumber, int $episodeNumber): self
    {
        $castEpisode = array_filter($this->episodes, function ($episode) use ($seasonNumber, $episodeNumber) {
            return $episode[0] === $seasonNumber && $episode[1] === $episodeNumber;
        });
        if (count($castEpisode) > 0) {
            return $this;
        }
        $this->episodes[] = [
            $seasonNumber,
            $episodeNumber
        ];

        return $this;
    }

    public function getKnownForDepartment(): ?string
    {
        return $this->knownForDepartment;
    }

    public function setKnownForDepartment(?string $knownForDepartment): self
    {
        $this->knownForDepartment = $knownForDepartment;

        return $this;
    }

    public function getCharacterName(): ?string
    {
        return $this->characterName;
    }

    public function setCharacterName(?string $characterName): self
    {
        $this->characterName = $characterName;

        return $this;
    }

    public function isGuestStar(): ?bool
    {
        return $this->guestStar;
    }

    public function setGuestStar(bool $guestStar): self
    {
        $this->guestStar = $guestStar;

        return $this;
    }
}
