<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserMovieRepository;
use App\Service\CallTmdbService;
use App\Service\ImageConfiguration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'homeWoLocale')]
    public function home(Request $request): RedirectResponse
    {
        $locale = $request->getLocale();
        return $this->redirectToRoute('app_home', ['_locale' => $locale]);
    }
    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/{_locale}', name: 'app_home', requirements: ['_locale' => 'fr|en|de|es', 'page'=>1, 'sort_by'=>'popularity.desc'])]
    public function index(Request $request, CallTmdbService $callTmdbService, UserMovieRepository $userMovieRepository, ImageConfiguration $imageConfiguration): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $userMovieIds = [];
        if ($user) {
            $userMovies = $userMovieRepository->findUserMovies($user->getId());
            foreach ($userMovies as $userMovie) {
                $userMovieIds[] = $userMovie['movie_db_id'];
            }
        }

        $options['fr'] = [
            'Popularité ↑ (du moins vers le plus)' => 'popularity.asc',
            'Popularité ↓ (du plus vers le moins)' => 'popularity.desc',
            'Date de sortie ↑' => 'release_date.asc',
            'Date de sortie ↓' => 'release_date.desc',
            'Recettes ↑' => 'revenue.asc',
            'Recettes ↓' => 'revenue.desc',
            'Première date de sortie ↑' => 'primary_release_date.asc',
            'Première date de sortie ↓' => 'primary_release_date.desc',
            'Titre original ↑' => 'original_title.asc',
            'Titre original ↓' => 'original_title.desc',
            'Moyenne des votes ↑' => 'vote_average.asc',
            'Moyenne des votes ↓' => 'vote_average.desc',
            'Nombre de votes ↑' => 'vote_count.asc',
            'Nombre de votes ↓' => 'vote_count.desc'
        ];
        $options['en'] = [
            'Ascending Popularity' => 'popularity.asc',
            'Descending Popularity' => 'popularity.desc',
            'Ascending Release Date' => 'release_date.asc',
            'Descending Release Date' => 'release_date.desc',
            'Ascending Revenue' => 'revenue.asc',
            'Descending Revenue' => 'revenue.desc',
            'Ascending Primary Release Date' => 'primary_release_date.asc',
            'Descending Primary Release Date' => 'primary_release_date.desc',
            'Ascending Original Title' => 'original_title.asc',
            'Descending Original Title' => 'original_title.desc',
            'Ascending Vote Average' => 'vote_average.asc',
            'Descending Vote Average' => 'vote_average.desc',
            'Ascending Vote Count' => 'vote_count.asc',
            'Descending Vote Count' => 'vote_count.desc'
        ];
        $options['de'] = [
            'Aufsteigende Popularität' => 'popularity.asc',
            'Absteigende Popularität' => 'popularity.desc',
            'Aufsteigendes Veröffentlichungsdatum' => 'release_date.asc',
            'Absteigendes Veröffentlichungsdatum' => 'release_date.desc',
            'Aufsteigend Einnahmen' => 'revenue.asc',
            'Absteigend Umsatz' => 'revenue.desc',
            'Aufsteigend Primäres Veröffentlichungsdatum' => 'primary_release_date.asc',
            'Absteigend Primäres Freigabedatum' => 'primary_release_date.desc',
            'Aufsteigend Originaltitel' => 'original_title.asc',
            'Absteigend Originaltitel' => 'original_title.desc',
            'Aufsteigend Vote Average' => 'vote_average.asc',
            'Absteigender Stimmendurchschnitt' => 'vote_average.desc',
            'Aufsteigende Stimmenzahl' => 'vote_count.asc',
            'Absteigende Stimmenzahl' => 'vote_count.desc'
        ];
        $options['es'] = [
            'Popularidad ascendente' => 'popularity.asc',
            'Popularidad descendente' => 'popularity.desc',
            'Fecha de lanzamiento ascendente' => 'release_date.asc',
            'Fecha de lanzamiento descendente' => 'release_date.desc',
            'Ingresos ascendentes' => 'revenue.asc',
            'Descendente Ingresos' => 'revenue.desc',
            'Fecha de lanzamiento principal ascendente' => 'primary_release_date.asc',
            'Descendente Fecha de publicación primaria' => 'primary_release_date.desc',
            'Ascendente Título original' => 'original_title.asc',
            'Descendente Título original' => 'original_title.desc',
            'Promedio de votos ascendente' => 'vote_average.asc',
            'Media de votos descendente' => 'vote_average.desc',
            'Recuento de votos ascendente' => 'vote_count.asc',
            'Recuento de votos descendente' => 'vote_count.desc'
        ];

        $locale = $request->getLocale();

        $sort_by = $request->query->get('sort', 'popularity.desc');
        $sorts = [
            'sort_by' => $sort_by,
            'options' => $options[$locale],
        ];

        $page = $request->query->getInt('page', 1);
        $standing = $callTmdbService->discoverMovies($page, $sort_by, $locale);
        $discovers = json_decode($standing, true);
        $imageConfig = $imageConfiguration->getConfig();

        $pages = [
            'page' => $discovers['page'],
            'total_pages' => $discovers['total_pages'],
            'total_results' => $discovers['total_results']
        ];

        // Certains films ne possèdent pas tous les champs …
        foreach ($discovers['results'] as &$discover) {
            if (!array_key_exists('release_date', $discover)) {
                $discover['release_date'] = "";
            }
        }

        return $this->render('home/index.html.twig', [
            'discovers' => $discovers,
            'userMovies' => $userMovieIds,
            'imageConfig' => $imageConfig,
            'pages' => $pages,
            'sorts' => $sorts,
            'dRoute' => 'app_movie'
        ]);
    }
}
