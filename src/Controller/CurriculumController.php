<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CurriculumController extends AbstractController
{
    #[Route('/{locale}/cv', name: 'app_curriculum', requirements: ['locale' => 'en|fr|de|es'])]
    public function index(): Response
    {
        return $this->render('curriculum/index.html.twig', [

        ]);
    }
}
