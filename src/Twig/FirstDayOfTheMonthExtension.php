<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class FirstDayOfTheMonthExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('fixIfFirstDayOfTheMonth', [$this, 'fixIfFirstDayOfTheMonth'], ['is_safe' => ['html']]),
        ];
    }

    public function getFilters(): array
    {
        return array(
            new TwigFilter('fixIfFirstDayOfTheMonth', [$this, 'fixIfFirstDayOfTheMonth'], ['is_safe' => ['html']]),
        );
    }

    public function fixIfFirstDayOfTheMonth($date): string
    {
        return preg_replace(
            '/ 1 /',
            ' 1<sup>er</sup> ',
            $date);
    }
}
