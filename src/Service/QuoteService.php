<?php

namespace App\Service;

class QuoteService
{
    private array $quotes = [
        "You may have my number, you can take my name but you'll never have my heart",
        "We are searchlights, we can see in the dark",
        "We are rockets, pointed up at the stars",
        "We are billions of beautiful hearts",
        "And you sold us down the river too far",
        "What about us? What about all the times you said you had the answers?",
        "What about us? What about all the broken happy ever afters?",
        "What about us? What about all the plans that ended in disaster?",
        "What about love? What about trust? What about us?",
        "What about us? What about all the times you said you had the answers?",
        "Sticks and stones, they may break these bones. But then I'll be ready, are you ready?",
        "But I set fire to the rain",
        "Watched it pour as I touched your face",
        "Well, it burned while I cried, cause I heard it screaming out your name, your name",
        "Whatever happened to the funky race?",
        "A generation lost in pace",
        "Oh, it takes a fool to remain sane",
        "Or maybe they're afraid to feel ashamed to seem strange, to seem insane, to gain weight, to seem gay",
        "Never mind, I'll find someone like you, I wish nothing but the best for you",
        "Sometimes it lasts in love, but sometimes it hurts instead",
        "I have nothing, nothing, nothing, if I don't have you, you",
        "I know there is hope in these waters, But I can't bring myself to swim",
        "Didn't get the chance to feel the world around me",
        "How can one become so bounded by choices that somebody else makes?",
        "How come we've both become a version of a person we don't even like?",
        "But the world just wants to bring us down by putting ideas in our heads that corrupt our hearts somehow",
        "Bienvenida a mi casa, relájese, mami, mi casa es su casa",
        "Si tu quieres te sacas, todita la ropa y nos vamos a la playa",
        "¿Qué pasa?, ¿qué pasa?",
        "Hon vände direkt, trodde jag var som andra",
        "¿Qué pasa?, ¿qué pasa?",
        "Kroppen hon har fick mig att sluta andas",
    ];

    public function getAGayQuote(): string
    {
        return $this->quotes[rand(0, count($this->quotes) - 1)];
    }

    public function getAStraightQuote(): ?string
    {
        return "#@&!, was a straight one ...";
    }

    public function getARandomQuote(): ?string
    {
        $r = rand(0, 999);
        return ($r % 2 == 0) ? $this->getAGayQuote() : $this->getAStraightQuote();
    }
}