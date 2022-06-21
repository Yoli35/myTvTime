<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\YoutubeChannelThumbnailDimension;
use App\Entity\YoutubeVideo;
use App\Entity\YoutubeVideoThumbnailDimension;
use App\Repository\YoutubeChannelThumbnailDimensionRepository;
use App\Repository\YoutubeVideoThumbnailDimensionRepository;
use App\Repository\YoutubeVideoRepository;
use Google\Exception;
use Google_Client;
use Google_Service_YouTube;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class YoutubeController extends AbstractController
{
    //
    // Clé API : AIzaSyDIBSBnQs6LAxrCO4Bj8uNbbqcJXt78W_M
    //

    #[Route('/{_locale}/youtube', name: 'app_youtube', requirements: ['_locale' => 'fr|en|de|es'])]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        /** TODO Must be logged */
        if (!$user) {
            return $this->redirectToRoute('app_home');
        }
        $previews = ['FhNiY_n0rmc', 'UoRyxgdFJ5Y', 'NCHMT-nQ-8c', 'tBTZ96Iit2g', 'T94JsAgK1X8', 'W9b8ifsDons', 'qOVT9rYda2o', 'qOVT9rYda2o', 'esNfg_XbXMY', 'lqttiQMLTbI', '9sLiQ7DKJ2g', 'q5D55G7Ejs8', 'R4bkKkooa-A', 'ieDIpgso4no', 'n0GSZtPEQs0', 'sbriUP3Pp5s', 'kDsC-fHC0vE', '2k-I_8lhS0w', 'iHTntTTa2io', 'uhMKEd18m_s', 'pVoRFDjq8-g', 'P5UZgiENdx0', 'at9h35V8rtQ', 'Mf1TwEySpno', '2kqvfoUUhA4', 'MUxcCgx4VlI', '6qiK5oQ_Vwk', '85gW-XY3fSE', '1Z5SRVURcIA', 'u044iM9xsWU', 'dWtG6DFFb1E', 'gmKINSHqryc', 'l8e8-8K1G0Y', 'xD_5BsMDBHY'];
        $preview_index = array_rand($previews, 1);

        return $this->render('youtube/index.html.twig', [
            'locale' => $request->getLocale(),
            'preview' => $previews[$preview_index],
        ]);
    }

    #[Route('/{_locale}/youtube/video/{id}', name: 'app_youtube_video', requirements: ['_locale' => 'fr|en|de|es'])]
    public function video($id, YoutubeVideoRepository $repository): Response
    {
        $videoId = $id;
        $video = $repository->find($videoId);

        return $this->render('youtube/video.html.twig', [
                'video' => $video,
            ]
        );
    }
}
