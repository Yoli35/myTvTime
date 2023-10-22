<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class TanService
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function getRoute($route_short_name): array|string
    {
        try {
            $response = $this->client->request(
                'GET',
                'https://data.nantesmetropole.fr/api/explore/v2.1/catalog/datasets/244400404_tan-circuits/records?where=route_short_name='.$route_short_name.'&limit=20'
            );
            try {
                $header = $response->getHeaders();

                return [
                    'content' => $response->getContent(),
                    'remaining' => $header['x-ratelimit-remaining'][0],
                    'limit' => $header['x-ratelimit-limit'][0]
                ];
            } catch (Throwable $e) {
                dump(['response' => $e]);
                return "";
            }
        } catch (Throwable $e) {
            dump(['request' => $e]);
            return "";
        }
    }
}
