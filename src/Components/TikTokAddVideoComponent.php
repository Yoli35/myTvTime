<?php

namespace App\Components;

use App\Entity\TikTokVideo;
use App\Entity\User;
use App\Repository\TikTokVideoRepository;
use App\Service\TikTokService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
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
    private EntityManagerInterface $entityManager;
    private array $videos = [];

    public function __construct(Security $security, TikTokService $tikTokService, TikTokVideoRepository $tikTokVideoRepository, EntityManagerInterface $entityManager)
    {
        $this->user = $security->getUser();
        $this->tikTokService = $tikTokService;
        $this->tikTokVideoRepository = $tikTokVideoRepository;
        $this->entityManager = $entityManager;
        
    }

    public function mount($locale): void
    {
        $this->locale = $locale;
        $this->videos = $this->tikTokVideoRepository->findUserTikToksByDate($this->user->getId());
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
        $thisLink = $this->link;
        $is_there_a_new_one = false;

        if (str_contains($thisLink, "https://www.tiktok.com/") && str_contains($thisLink, "video")) {

            $hasParam = strpos($thisLink, '?');
            if ($hasParam) {
                $thisLink = substr($thisLink, 0, $hasParam);
            }

            $videoId = substr($thisLink, -19);

            $video = $this->tikTokVideoRepository->findBy(['videoId' => $videoId]);

            if ($video == null) {
                $tiktok = json_decode($this->tikTokService->getVideo($thisLink), true);

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
                $addedAt = new \DateTimeImmutable();
                $video->setAddedAt($addedAt->setTimezone((new \DateTime())->getTimezone()));
                $video->addUser($this->user);

                $this->tikTokVideoRepository->add($video, true);

                $is_there_a_new_one = true;
            }
        }

        if (!count($this->videos) || $is_there_a_new_one) {
            $this->videos = $this->tikTokVideoRepository->findUserTikToksByDate($this->user->getId());
        }
        return ['videos' => $this->videos, 'count' => count($this->videos)];
    }

    public function thumbnail($item):string
    {
        // https://p16-sign-va.tiktokcdn.com/obj/tos-maliva-p-0068/6de148409b5d4a1fb4e752a0a2dd8001?x-expires=1656820800&x-signature=LV%2BoEEUMowEgWFRsWb97JEZfQDg%3D
        $tiktok = $item['tiktok'];
        $videoId = $tiktok['video_id'];

        //
        // Les liens des thumbnails expirent au bout de quelques heures
        //
        $thumbnail = $tiktok['thumbnail_url'];
        $x_expires = intval(substr($thumbnail, strpos($thumbnail, "x-expires") + 10, 10));

        $date = new DateTime();
        $now = $date->getTimestamp();

        //
        // Si c'est le cas, mise à jour de la vidéo avec un nouveau lien
        //
        if ($now >= $x_expires) {
            $link = $tiktok['author_url'] . '/video/' . $tiktok['video_id'];
            $tiktok = json_decode($this->tikTokService->getVideo($link), true);

            /** @var TikTokVideo $video */
            $video = $this->tikTokVideoRepository->findOneBy(['videoId' => $videoId]);
            $video->setThumbnailUrl($tiktok['thumbnail_url']);
            $this->entityManager->persist($video);
            $this->entityManager->flush();
        }
        return $tiktok['thumbnail_url'];
    }
}