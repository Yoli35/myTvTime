<?php

namespace App\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('user_movie_search')]
class UserMovieSearchComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $query = '';
    #[LiveProp]
    public array $movies = [];
    #[LiveProp]
    public array $config = [];

    public function __construct()
    {
        $this->n = 0;
    }

    public function mount($movies, $config): void
    {
        $this->movies = $movies;
        $this->config = $config;
    }

    public function getUserMovies(): array
    {
        $results = [];
        if (strlen($this->query)) {
            foreach ($this->movies as $movie) {
                if (strlen(stristr($movie['title'], $this->query))) {
                    $results[] = $movie;
                }
            }
        }
        return array_reverse($results);
    }
}