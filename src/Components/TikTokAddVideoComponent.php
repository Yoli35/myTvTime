<?php

namespace App\Components;

use App\Entity\TikTokVideo;
use App\Entity\User;
use App\Repository\TikTokVideoRepository;
use App\Service\TikTokService;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('tik_tok_add_video')]
class TikTokAddVideoComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $link = '';
    #[LiveProp]
    public string $locale = '';

    private User $user;
    private TikTokService $tikTokService;
    private TikTokVideoRepository $tikTokVideoRepository;

    public function __construct(Security $security, TikTokService $tikTokService, TikTokVideoRepository $tikTokVideoRepository)
    {
        $this->user = $security->getUser();
        $this->tikTokService = $tikTokService;
        $this->tikTokVideoRepository = $tikTokVideoRepository;
    }

    public function mount($locale): void
    {
        $this->locale = $locale;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[ArrayShape(['videos' => "array", 'count' => "int"])]
    public function tik_tok_results(): array
    {
        // https://www.tiktok.com/@thebillykeogh/video/7105165118501113134?is_from_webapp=1&sender_device=pc
        // https://www.tiktok.com/@scout2015/video/6718335390845095173
        // https://www.tiktok.com/@thebillykeogh/video/7028634202757451055?is_from_webapp=1&sender_device=pc&web_id=7113996049588979205
        $thisLink = $this->link;
        dump($thisLink);

        if (str_contains($thisLink, "https://www.tiktok.com/") && str_contains($thisLink, "video")) {
            dump('Seems to be a TikTok video link');

            $hasParam = strpos($thisLink, '?');
            if ($hasParam) {
                $thisLink = substr($thisLink, 0, $hasParam);
            }
            dump($hasParam, $thisLink);

            $videoId = substr($thisLink, -19);
            dump($videoId);

            $video = $this->tikTokVideoRepository->findBy(['videoId' => $videoId]);

            if ($video == null) {
                $tiktok = json_decode($this->tikTokService->getVideo($thisLink), true);
                dump($tiktok);
                $video = new TikTokVideo();
                $video->setVersion($tiktok['version']);
                $video->setType($tiktok['type']);
                $video->setVideoId($videoId);
                $video->setTitle($tiktok['title']);
                $video->setAuthorUrl($tiktok['author_url']);
                $video->setAuthorName($tiktok['author_name']);
                $video->setWidth($tiktok['width']);
                $video->setHeight($tiktok['height']);
                $video->setHtml($tiktok['html']);
                $video->setThumbnailUrl($tiktok['thumbnail_url']);
                $video->setThumbnailWidth($tiktok['thumbnail_width']);
                $video->setThumbnailHeight($tiktok['thumbnail_height']);
                $video->setProviderUrl($tiktok['provider_url']);
                $video->setProviderName($tiktok['provider_name']);
                $video->setAddedAt(new \DateTimeImmutable());
                $video->addUser($this->user);

                $this->tikTokVideoRepository->add($video, true);
            }
        }
        $videos = $this->tikTokVideoRepository->findUserTikToksByDate($this->user->getId());
        return ['videos' => $videos, 'count' => count($videos)];
    }
}