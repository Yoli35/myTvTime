<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BetaSeriesService
{
    /*
     * Clé API : 971d5ae1390a
     * Clé secrète (pour OAuth 2.0) : bced1d045fccc6f550b1b81297814407
     */
    private HttpClientInterface $client;
    private int $limit = 20;

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
    public function showsList($page): ?string
    {
        /*
            PARAMÈTRES
                order : Spécifie l'ordre de retour : alphabetical, popularity, followers (facultatif)
                since : N'afficher que les séries modifiées à partir de cette date (timestamp UNIX — facultatif)
                recent : Séries des deux dernières années uniquement (Défaut false)
                starting : Affiche les séries commençant par les caractères spécifiés (facultatif)
                start : Nombre de démarrage pour la liste des séries (facultatif, défaut 0)
                limit : Limite du nombre de séries à afficher (facultatif, défaut 100)
                filter : Filtre d'affichage (facultatif, new=seulement les séries pas dans le compte)
                platforms : Ids des plateformes SVOD/VOD sur lesquelles les séries doivent être disponibles
                country : Pays pour les plateformes SVOD/VOD
                summary : Retourne uniquement les infos essentielles de la série (Défaut false)
         */
        $start = ($page - 1) * $this->limit;
        $response = $this->client->request('GET', 'https://api.betaseries.com/shows/list?key=971d5ae1390a&order=popularity&start=' . $start . '&limit=' . $this->limit . '&summary=false',);
        return $response->getContent();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function discoverSeries($page): ?string
    {
        $offset = ($page - 1) * $this->limit;
        $response = $this->client->request('GET', 'https://api.betaseries.com/shows/discover?key=971d5ae1390a&offset' . $offset . '&limit=' . $this->limit . '&summary=false',);
        return $response->getContent();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function showsDisplay($ids): ?string
    {
        $response = $this->client->request('GET', 'https://api.betaseries.com/shows/display?key=971d5ae1390a&id=' . $ids,);
        return $response->getContent();
    }
}
