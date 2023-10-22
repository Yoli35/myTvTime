<?php

namespace App\Controller;

use App\Service\TanService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OpenLayersController extends AbstractController
{
    public function __construct(private readonly TanService $tanService)
    {
    }

    #[Route('/open/layers', name: 'app_open_layers')]
    public function index(Request $request): Response
    {
        $standing = $this->tanService->getRoute('5');
        $route5 = json_decode($standing['content'], true);

        return $this->render('open_layers/index.html.twig', [
            'routes' => ['5' => $route5],
            'remaining' => $standing['remaining'],
            'limit' => $standing['limit'],
            'user' => $this->getUser(),
        ]);
    }
}
