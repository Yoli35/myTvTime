<?php

namespace App\Controller;

use DateTimeImmutable;

class SeasonView
{
    private DateTimeImmutable $air_date;
    private array $episodes;
    private int $episode_count;
    private int $season_number;
    private ?bool $season_completed;

    public function __construct()
    {
        $this->air_date = new DateTimeImmutable();
    }

    /**
     * @return DateTimeImmutable
     */
    public function getAirDate(): DateTimeImmutable
    {
        return $this->air_date;
    }

    /**
     * @param DateTimeImmutable $air_date
     */
    public function setAirDate(DateTimeImmutable $air_date): void
    {
        $this->air_date = $air_date;
    }

    /**
     * @return array
     */
    public function getEpisodes(): array
    {
        return $this->episodes;
    }

    /**
     * @param array $episodes
     */
    public function setEpisodes(array $episodes): void
    {
        $this->episodes = $episodes;
    }

    /**
     * @return int
     */
    public function getEpisodeCount(): int
    {
        return $this->episode_count;
    }

    /**
     * @param int $episode_count
     */
    public function setEpisodeCount(int $episode_count): void
    {
        $this->episode_count = $episode_count;
    }

    /**
     * @return int
     */
    public function getSeasonNumber(): int
    {
        return $this->season_number;
    }

    /**
     * @param int $season_number
     */
    public function setSeasonNumber(int $season_number): void
    {
        $this->season_number = $season_number;
    }

    /**
     * @return bool|null
     */
    public function getSeasonCompleted(): ?bool
    {
        return $this->season_completed;
    }

    /**
     * @param bool|null $season_completed
     */
    public function setSeasonCompleted(?bool $season_completed): void
    {
        $this->season_completed = $season_completed;
    }
}