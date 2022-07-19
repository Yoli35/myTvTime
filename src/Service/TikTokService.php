<?php

namespace App\Service;

use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\RedirectionException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TikTokService
{
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
    public function getVideo($link): ?string
    {
        $response = null;
        try {
            $response = $this->client->request(
                'GET',
                'https://www.tiktok.com/oembed?url=' . $link,
            );
        } catch (TransportException $transportException) {
            dump($transportException->getMessage());
        } catch (ServerException $serverException) {
            dump($serverException->getMessage());
        } catch (RedirectionException $redirectionException) {
            dump($redirectionException->getMessage());
        } catch (ClientException $clientException) {
            dump($clientException->getMessage());
        }
        return $response?->getContent();
    }

}