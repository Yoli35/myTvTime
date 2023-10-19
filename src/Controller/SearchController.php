<?php

namespace App\Controller;

//use App\Config\SearchHistoryType;
use App\Entity\SearchHistory;
use App\Repository\CastRepository;
use App\Repository\SearchHistoryRepository;
use App\Service\ImageConfiguration;
use App\Service\TMDBService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    public function __construct(private readonly TMDBService             $TMDBService,
                                private readonly CastRepository          $castRepository,
                                private readonly ImageConfiguration      $imageConfiguration,
                                private readonly SearchHistoryRepository $searchHistoryRepository,
    )
    {
    }

    #[Route('/search', name: 'app_search')]
    public function index(Request $request): Response
    {
        $query = $request->query->get('query');
        $multiPeople = strchr($query, '|');
        $page = $request->query->get('page', 1);
        $fromDB = $request->query->get('db', 0);

        if ($fromDB) {
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
            $all = count($this->castRepository->searchByNameCount($query));
            $results = [
                "page" => $page,
                "total_pages" => (int)ceil($all / 20),
                "total_results" => $all,
                "results" => $results
            ];
//            dump(["query" => $query, "movies" => $movies, "series" => $series, "casts" => $casts]);
//            dump(["query" => $query, "results" => $results]);
        } else {
            if ($multiPeople) {
                $queries = explode(',', $query);
                $results = [];
                foreach ($queries as $query) {
                    $standing = $this->TMDBService->getPerson($query, $request->getLocale());
                    $results = array_merge($results, strlen($standing) ? json_decode($standing, true) : []);
                }
            } else {
                $standing = $this->TMDBService->multiSearch($page, $query, $request->getLocale());
                $results = strlen($standing) ? json_decode($standing, true) : [];
            }
        }
//        dump(["query" => $query, "results" => $results]);

        return $this->render('search/index.html.twig', [
            'query' => $query,
            'db' => $fromDB,
            'results' => $results['results'] ?? [],
            'page' => $results['page'] ?? 1,
            'total_pages' => $results['total_pages'] ?? 1,
            'total_results' => $results['total_results'] ?? 0,
            'imageConfig' => $this->imageConfiguration->getConfig(),
            'from' => 'search',
        ]);
    }

    #[Route('/search-person/{name}', name: 'app_search_person')]
    public function searchPerson(Request $request, $name): Response
    {
        return $this->json($this->TMDBService->searchPerson($name, $request->getLocale()));
    }

    #[Route('/search-people', name: 'people')]
    public function searchPeople(Request $request): Response
    {
        $query = $request->query->get('query');
        $ids = explode(',', $query);
        // On supprime les éléments égaux à zéro
        $ids = array_filter($ids, function ($id) {
            return $id != 0;
        });
        $numberOfIds = count($ids);
        $people = array_map(function ($id) use ($request) {
            if ($id) {
                $person = json_decode($this->TMDBService->getPerson($id, $request->getLocale()), true);
                $credits = json_decode($this->TMDBService->getPersonCredits($id, $request->getLocale()), true);
//                dump(['credits' => $credits, 'locale' => $request->getLocale()]);
                if (!key_exists('cast', $credits))
                    $credits['cast'] = [];
                $person['cast'] = $credits['cast'];
                return $person;
            } else
                return [];
        }, $ids);
//        dump(['people' => $people]);
        // Trouver les films et séries en commun
        $common = [];
        foreach ($people as $person) {
            foreach ($person['cast'] as $cast) {
                $id = $cast['id'];
                if (!key_exists($id, $common))
                    $common[$id] = [[], $cast, 0];
                if (!in_array($person, $common[$id][0])) {
                    $common[$id][0][] = $person;
                    $common[$id][2]++;
                }
            }
        }
//        dump($common);
        if ($numberOfIds > 1) {
            $common = array_filter($common, function ($value) {
                return $value[2] > 1;
            });
//        dump($common);
        }
        $common = array_map(function ($value) {
            return ['people' => $value[0], 'media' => $value[1], 'count' => $value[2]];
        }, $common);
//        dump($common);
        // Tri par date (media_type = movie) ou première date de diffusion (media_type = tv)
        usort($common, function ($a, $b) {
            $aDate = $a['media']['media_type'] == 'movie' ? $a['media']['release_date'] : $a['media']['first_air_date'];
            $bDate = $b['media']['media_type'] == 'movie' ? $b['media']['release_date'] : $b['media']['first_air_date'];

            return $bDate <=> $aDate;
        });

        return $this->render('search/people.html.twig', [
            'people' => $people,
            'common' => $common,
            'imageConfig' => $this->imageConfiguration->getConfig(),
            'from' => 'people',
        ]);
    }

    const SEARCH_TYPES = [
        'movie' => 0, /*SearchHistoryType::MOVIE,*/
        'tv' => 1, /*SearchHistoryType::TV,*/
        'person' => 3, /*SearchHistoryType::PERSON,*/
    ];

    #[Route('/search-people/history', name: 'people_history')]
    public function searchPeopleHistory(Request $request): Response
    {
        $name = $request->query->get('name');
        $id = $request->query->get('id');
        $type = self::SEARCH_TYPES['person'];

        if (!$name || !$id)
            return $this->json(['result' => 'error', 'message' => 'Missing parameters']);

        $history = new SearchHistory($name, $type, $id);
        $this->searchHistoryRepository->save($history, true);

        return $this->json(['result' => 'success', 'message' => 'History saved']);
    }

    #[Route('/search-people/history/get', name: 'people_history_get')]
    public function getSearchPeopleHistory(Request $request): Response
    {
        $limit = $request->query->get('limit', 20);
        $type = self::SEARCH_TYPES['person'];

        $history = $this->searchHistoryRepository->findBy(['type' => $type], ['id' => 'DESC'], $limit);
        if (empty($history))
            return $this->json(['result' => 'warning', 'message' => 'History is empty']);

        return $this->json(['result' => 'success', 'message' => 'History found', 'history' => $history]);
    }
}
