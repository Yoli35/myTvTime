<?php

namespace App\Controller;

use App\Repository\YoutubeVideoRepository;
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
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $previews = ['FhNiY_n0rmc', 'UoRyxgdFJ5Y', 'NCHMT-nQ-8c', 'tBTZ96Iit2g', 'T94JsAgK1X8', 'W9b8ifsDons', 'qOVT9rYda2o', 'qOVT9rYda2o', 'esNfg_XbXMY', 'lqttiQMLTbI', '9sLiQ7DKJ2g', 'q5D55G7Ejs8', 'R4bkKkooa-A', 'ieDIpgso4no', 'n0GSZtPEQs0', 'sbriUP3Pp5s', 'kDsC-fHC0vE', '2k-I_8lhS0w', 'iHTntTTa2io', 'uhMKEd18m_s', 'pVoRFDjq8-g', 'P5UZgiENdx0', 'at9h35V8rtQ', 'Mf1TwEySpno', '2kqvfoUUhA4', 'MUxcCgx4VlI', '6qiK5oQ_Vwk', '85gW-XY3fSE', '1Z5SRVURcIA', 'u044iM9xsWU', 'dWtG6DFFb1E', 'gmKINSHqryc', 'l8e8-8K1G0Y', 'xD_5BsMDBHY'];
        $preview_index = array_rand($previews, 1);

        return $this->render('youtube/index.html.twig', [
            'locale' => $request->getLocale(),
            'preview' => $previews[$preview_index],
        ]);
    }

    #[Route('/youtube/more', name: 'app_youtube_more')]
    public function userMoviesMore(Request $request, YoutubeVideoRepository $youtubeVideoRepository): Response
    {
        return $this->json([
            'results' => $youtubeVideoRepository->findAllByDate($request->query->get('id'), $request->query->get('offset')),
        ]);
    }

    #[Route('/{_locale}/youtube/video/{id}', name: 'app_youtube_video', requirements: ['_locale' => 'fr|en|de|es'])]
    public function video($id, YoutubeVideoRepository $repository): Response
    {
        $videoId = $id;
        $video = $repository->find($videoId);
        $description = nl2br($video->getDescription());
        $description = preg_replace('@([^>"])(https?://[a-z0-9\./+,%\@\?=#_-]+)@i', '$1<a href="$2" target="_blank">$2</a>', $description);
        $description = preg_replace('#([A-Za-z_-][A-Za-z0-9\._-]*@[a-z0-9_-]+(\.[a-z0-9_-]+)+)#','<a href="mailto:$1">$1</a>', $description);


        return $this->render('youtube/video.html.twig', [
                'video' => $video,
                'description' => $description,
            ]
        );
    }

    private function urlsToLinks($text): array|string|null
    {
        return preg_replace(
            array(
            '/(?(?=<a[^>]*>.+<\/a>) (?:<a[^>]*>.+<\/a>) | ([^="\']?)((?:https?|ftp|bf2|):\/\/[^<> \n\r]+) )/iex',
            '/<a([^>]*)target="?[^"\']+"?/i',
            '/<a([^>]+)>/i',
            '/(^|\s)(www.[^<> \n\r]+)/iex',
            '/(([_A-Za-z0-9-]+)(\\.[_A-Za-z0-9-]+)*@([A-Za-z0-9-]+) (\\.[A-Za-z0-9-]+)*)/iex'
            ),
            array(
                "stripslashes((strlen('\\2')>0?'\\1<a href=\"\\2\">\\2</a>\\3':'\\0'))",
                '<a\\1',
                '<a\\1 target="_blank">',
                "stripslashes((strlen('\\2')>0?'\\1<a href=\"http://\\2\">\\2</a>\\3':'\\0'))",
                "stripslashes((strlen('\\2')>0?'<a href=\"mailto:\\0\">\\0</a>':'\\0'))"
            ),
            $text);
    }

}
