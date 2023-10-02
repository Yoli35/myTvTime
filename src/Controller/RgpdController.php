<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RgpdController extends AbstractController
{
    public function __construct()
    {
    }

    #[Route('/{_locale}/rgpd', name: 'app_rgpd', requirements: ['_locale' => 'fr|en|de|es'])]
    public function index(): Response
    {
        return $this->render('rgpd/index.html.twig');
    }
}
