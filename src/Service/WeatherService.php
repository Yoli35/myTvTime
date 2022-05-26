<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherService
{
    /*
     * API Key: a790df8a650b4ec8ab145453222405
     */
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getLocalWeather($location, $locale): ?string
    {
        $response = $this->client->request(
            'GET',
            'https://api.weatherapi.com/v1/current.json?key=a790df8a650b4ec8ab145453222405&q='.$location.'&aqi=no&lang='.$locale);
        return $response->getContent();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getLocalAstronomy($location, $date, $locale): ?string
    {
        $response = $this->client->request(
            'GET',
            'https://api.weatherapi.com/v1/astronomy.json?key=a790df8a650b4ec8ab145453222405&q='.$location.'&dt='.$date.'&lang='.$locale);
        return $response->getContent();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getLocalForecast($location, $days, $locale): ?string
    {
        dump('https://api.weatherapi.com/v1/forecast.json?key=a790df8a650b4ec8ab145453222405&q='.$location.'&days='.$days.'&lang='.$locale);
        $response = $this->client->request(
            'GET',
            'https://api.weatherapi.com/v1/forecast.json?key=a790df8a650b4ec8ab145453222405&q='.$location.'&days='.$days.'&lang='.$locale);
        return $response->getContent();
    }
}