<?php

namespace App\Components;

use App\Entity\User;
use App\Service\WeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('weather')]
class WeatherComponent extends AbstractController
{
    public array $weather;

    private WeatherService $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getForecast($locale = 'fr')
    {
        /** @var User $user */
        $user = $this->getUser();
        $forecast = [];
        if ($user->getCity()) {
            $standing = $this->weatherService->getLocalForecast($user->getCity(), 3, $locale);
            $forecast = json_decode($standing, true, 512, 0);
        }
        return $forecast;
    }
}
