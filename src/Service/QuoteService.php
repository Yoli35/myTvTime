<?php

namespace App\Service;

class QuoteService
{
    private array $quotes = [
        "“But real evil has to be dealt with and you don’t do that by letting it live to take good people down.” -Joe Goldberg"
    ];

    public function getASerieQuote(): string
    {
        return $this->quotes[rand(0, count($this->quotes) - 1)];
    }

    public function getARandomQuote(): ?string
    {
        return $this->getASerieQuote();
    }
}