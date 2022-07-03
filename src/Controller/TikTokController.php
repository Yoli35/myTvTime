<?php

namespace App\Controller;

use App\Repository\TikTokVideoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TikTokController extends AbstractController
{
    #[Route('/{_locale}/tik/tok', name: 'app_tik_tok', requirements: ['_locale' => 'fr|en|de|es'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->render('tik_tok/index.html.twig', [
            'locale' => $request->getLocale(),
        ]);
    }

    #[Route('/{_locale}/tiktok/video/{id}', name: 'app_tik_tok_video', requirements: ['_locale' => 'fr|en|de|es'])]
    public function video($id, TikTokVideoRepository $repository): Response
    {
        $videoId = $id;
        $video = $repository->find($videoId);

        return $this->render('tik_tok/video.html.twig', [
                'video' => $video,
            ]
        );
    }
}
