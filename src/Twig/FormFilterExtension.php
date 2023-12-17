<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FormFilterExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('getFormFilterLayout', [$this, 'getFormFilterLayout'], ['is_safe' => ['html']]),
        ];
    }

    function getFormFilterLayout(): string
    {
        $cookie = $_COOKIE['formFilter'];
        if ($cookie) {
            $cookie = json_decode($cookie, true);
        } else {
            $cookie = ['layout' => 'open'];
            setcookie('formFilter', json_encode($cookie), time() + 365 * 24 * 3600, null, null, false, true);
        }
        dump($cookie);

        return $cookie['layout'];
    }
}
