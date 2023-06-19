<?php

namespace App\Controller;

use App\Repository\CastRepository;
use App\Repository\MovieRepository;
use App\Repository\SerieRepository;
use App\Service\ImageConfiguration;
use App\Service\TMDBService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    public function __construct(private readonly TMDBService        $TMDBService,
                                private readonly MovieRepository    $movieRepository,
                                private readonly SerieRepository    $serieRepository,
                                private readonly CastRepository     $castRepository,
                                private readonly ImageConfiguration $imageConfiguration,
    )
    {
    }

    #[Route('/search', name: 'app_search')]
    public function index(Request $request): Response
    {
        $query = $request->query->get('query');
        $page = $request->query->get('page', 1);
        $fromDB = $request->query->get('db', 0);

        if ($fromDB) {
//            $movies = $this->movieRepository->moviesByTitle($query, 20, ($page-1) * 20);
//            $series = $this->serieRepository->seriesByTitle($query, 20, ($page-1) * 20);
//            $casts = $this->castRepository->castByName($query, 20, ($page-1) * 20);
            $results = $this->castRepository->searchByName($query, 20, ($page - 1) * 20);
            $results = array_map(function ($result) {
                switch ($result['media_type']) {
                    case 'movie':
                        $result['poster_path'] = $result['path'];
                        $result['title'] = $result['name'];
                        $result['original_title'] = $result['original_name'];
                        $result['release_date'] = $result['date'];
                        break;
                    case 'tv':
                        $result['poster_path'] = $result['path'];
                        $result['first_date_air'] = $result['date'];
                        break;
                    case 'person':
                        $result['profile_path'] = $result['path'];
                        break;
                }
                return $result;
            }, $results);
            $all = $this->castRepository->searchByNameCount($query);
            dump($all);
            $results = [
                "page" => $page,
                "total_pages" => 1,
                "total_results" => count($all),
                "results" => $results
            ];
//            dump(["query" => $query, "movies" => $movies, "series" => $series, "casts" => $casts]);
            dump(["query" => $query, "results" => $results]);
        } else {
            $standing = $this->TMDBService->multiSearch($page, $query, $request->getLocale());
            $results = strlen($standing) ? json_decode($standing, true) : [];
        }
//        dump(["query" => $query, "results" => $results]);

        return $this->render('search/index.html.twig', [
            'query' => $query,
            'results' => $results['results'] ?? [],
            'page' => $results['page'] ?? 1,
            'total_pages' => $results['total_pages'] ?? 1,
            'total_results' => $results['total_results'] ?? 0,
            'imageConfig' => $this->imageConfiguration->getConfig(),
            'from' => 'search',
        ]);
    }
}
