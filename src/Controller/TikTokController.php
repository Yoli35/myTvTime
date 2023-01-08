<?php

namespace App\Controller;

use App\Entity\TikTokVideo;
use App\Repository\TikTokVideoRepository;
use App\Repository\UserRepository;
use App\Service\TikTokService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class TikTokController extends AbstractController
{
    #[Route('/{_locale}/tiktok', name: 'app_tik_tok', requirements: ['_locale' => 'fr|en|de|es'])]
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


    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/tiktok/more', name: 'app_tik_tok_more')]
    public function getMore(Request $request, TikTokService $tikTokService, TikTokVideoRepository $tikTokVideoRepository, EntityManagerInterface $entityManager): Response
    {
        $id = $request->query->get('id');
        $offset = $request->query->get('offset');

        $videos = $tikTokVideoRepository->findUserTikToksByDate($id, $offset);
        $update = false;
        $inc = 0;

        foreach ($videos as $video) {

            $videoId = $video['video_id'];

            $thumbnail = $video['thumbnail_url'];
            $x_expires = intval(substr($thumbnail, strpos($thumbnail, "x-expires") + 10, 10));

            $date = new DateTime();
            $now = $date->getTimestamp();

            /** @var TikTokVideo $video */
            $video = $tikTokVideoRepository->findOneBy(['videoId' => $videoId]);

            if ($now >= $x_expires) {
                $link = $video->getAuthorUrl() . '/video/' . $video->getVideoId();
                $standing = $tikTokService->getVideo($link);
                if ($standing['code'] == 200) {
                    $tiktok = json_decode($standing['content'], true);
                    $video->setThumbnailUrl($tiktok['thumbnail_url']);
                    $video->setThumbnailHasExpired(true);

                    $videos[$inc]['thumbnail_url'] = $tiktok['thumbnail_url'];
                    $videos[$inc]['thumbnail_has_expired'] = false;
                } else {
                    $video->setThumbnailHasExpired(true);
                }
                $entityManager->persist($video);
                $entityManager->flush();
                $update = true;
            }
            $inc++;
        }
        return $this->json([
            'results' => $videos,
            'update' => $update,
        ]);
    }
}
