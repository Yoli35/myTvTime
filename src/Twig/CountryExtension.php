<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CountryExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('getEmojiFlag', [$this, 'getEmojiFlag'], ['is_safe' => ['html']]),
        ];
    }

    // From Smaine Milianni : https://smaine-milianni.medium.com/emoji-flag-in-the-symfony-countrytype-f794f39e6ac9
    function getEmojiFlag(string $countryCode): string
    {
        if ($countryCode === 'all') {
            return '🌍'; // ← Réponse donnée pas Copilot
        }
        $regionalOffset = 0x1F1A5;

        return mb_chr($regionalOffset + mb_ord($countryCode[0], 'UTF-8'), 'UTF-8')
            . mb_chr($regionalOffset + mb_ord($countryCode[1], 'UTF-8'), 'UTF-8');
    }
}
