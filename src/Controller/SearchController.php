<?php

namespace App\Controller;

use App\Service\ImageConfiguration;
use App\Service\TMDBService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    public function __construct(private readonly TMDBService $TMDBService,
                                private readonly ImageConfiguration $imageConfiguration,
                                )
    {
    }

    #[Route('/search', name: 'app_search')]
    public function index(Request $request): Response
    {
        $query = $request->query->get('query');
        $page = $request->query->get('page', 1);

        $standing = $this->TMDBService->multiSearch($page, $query, $request->getLocale());
        $results = strlen($standing) ? json_decode($standing, true) : [];
        dump(["query" => $query, "results" => $results]);

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
