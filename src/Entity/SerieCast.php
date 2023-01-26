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
    #[ORM\JoinColumn(nullable: false)]
    private ?SerieViewing $serieViewing;

    #[ORM\Column(nullable: false)]
    private int $castId;

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

    public function __construct(SerieViewing $serieViewing, int $castId)
    {
        $this->serieViewing = $serieViewing;
        $this->castId = $castId;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSerieViewing(): ?SerieViewing
    {
        return $this->serieViewing;
    }

    public function setSerieViewing(?SerieViewing $serieViewing): self
    {
        $this->serieViewing = $serieViewing;

        return $this;
    }

    public function getCastId(): int
    {
        return $this->castId;
    }

    public function setCastId(int $castId): self
    {
        $this->castId = $castId;

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
            $episodes[] = sprintf('S%02dE%02d', $episode['seasonNumber'], $episode['episodeNumber']);
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
        $this->episodes[] = [
            'seasonNumber' => $seasonNumber,
            'episodeNumber' => $episodeNumber,
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
