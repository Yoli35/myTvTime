<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\YoutubeChannelThumbnailDimension;
use App\Entity\YoutubeVideo;
use App\Entity\YoutubeVideoThumbnailDimension;
use App\Repository\YoutubeChannelThumbnailDimensionRepository;
use App\Repository\YoutubeVideoThumbnailDimensionRepository;
use App\Repository\YoutubeVideoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class YoutubeController extends AbstractController
{
    //
    // Clé API : AIzaSyDIBSBnQs6LAxrCO4Bj8uNbbqcJXt78W_M
    //

    #[Route('/{_locale}/youtube', name: 'app_youtube', requirements: ['_locale' => 'fr|en|de|es'])]
    public function index(YoutubeVideoRepository $repository, YoutubeVideoThumbnailDimensionRepository $youtubeVideoThumbnailDimensionRepository, YoutubeChannelThumbnailDimensionRepository $youtubeChannelThumbnailDimensionRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        /** TODO Must be logged */

        /** @var YoutubeVideo $videos */
        $videos = $repository->findAllByDate($user->getId());

        /** @var YoutubeVideoThumbnailDimension $ytVideoThumbnailDims */
        $ytVideoThumbnailDims = $youtubeVideoThumbnailDimensionRepository->findAll();

        /** @var YoutubeChannelThumbnailDimension $ytChannelThumbnailDims */
        $ytChannelThumbnailDims = $youtubeChannelThumbnailDimensionRepository->findAll();

        return $this->render('youtube/index.html.twig', [
            'videos' => $videos,
            'ytVDims' => $ytVideoThumbnailDims[0],    // Default sizes
            'ytCDims' => $ytChannelThumbnailDims[0],    // Default sizes
        ]);
    }
}
