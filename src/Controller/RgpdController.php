<?php

namespace App\Controller;

use App\Service\LogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RgpdController extends AbstractController
{
    public function __construct(private readonly LogService $logService)
    {
    }

    #[Route('/rgpd', name: 'app_rgpd')]
    public function index(Request $request): Response
    {
//        $this->logService->log($request, $this->getUser());
        return $this->render('rgpd/index.html.twig', [
        ]);
    }
}
