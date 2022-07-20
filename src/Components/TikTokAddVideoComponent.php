<?php

namespace App\Components;

use App\Entity\TikTokVideo;
use App\Entity\User;
use App\Repository\TikTokVideoRepository;
use App\Repository\UserRepository;
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
    public int $id = 0;
    #[LiveProp]
    public string $locale = '';

    private TikTokService $tikTokService;
    private UserRepository $userRepository;
    private TikTokVideoRepository $tikTokVideoRepository;
    private EntityManagerInterface $entityManager;
    private array $videos = [];

    public function __construct(TikTokService $tikTokService, UserRepository $userRepository, TikTokVideoRepository $tikTokVideoRepository, EntityManagerInterface $entityManager)
    {
        $this->tikTokService = $tikTokService;
        $this->userRepository = $userRepository;
        $this->tikTokVideoRepository = $tikTokVideoRepository;
        $this->entityManager = $entityManager;
    }

    public function mount($id, $locale): void
    {
        $this->id = $id;
        $this->locale = $locale;
        $this->videos = $this->tikTokVideoRepository->findUserTikToksByDate($this->id);
//        $this->videos = $this->tikTokVideoRepository->findUserTikToksByDate($this->user->getId());
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
        // https://vm.tiktok.com/ZMNHRpwad/?k=1
        // https://www.tiktok.com/@jessicamartinez62s/video/7119662902562376965?is_from_webapp=1&sender_device=pc
        $thisLink = $this->link;
        $videoId = "";
        $valid = false;
        $is_there_a_new_one = false;

        if (str_contains($thisLink, "https://www.tiktok.com/") && str_contains($thisLink, "video")) {

            $hasParam = strpos($thisLink, '?');
            if ($hasParam) {
                $thisLink = substr($thisLink, 0, $hasParam);
            }
            $videoId = substr($thisLink, -19);
            $valid = true;
        }

        if ($valid) {

            $video = $this->tikTokVideoRepository->findBy(['videoId' => $videoId]);

            if ($video == null) {
                $standing = $this->tikTokService->getVideo($thisLink);
                // https://www.tiktok.com/@leoobbrown/video/7089218681154145542?is_from_webapp=1&sender_device=pc&web_id=7113996049588979205
                // dump($standing);
                $tiktok = json_decode($standing, true);

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
                $video->setThumbnailHasExpired(false);
                $video->setThumbnailWidth($tiktok['thumbnail_width']);
                $video->setThumbnailHeight($tiktok['thumbnail_height']);
                $video->setProviderUrl($tiktok['provider_url']);
                $video->setProviderName($tiktok['provider_name']);
                $addedAt = new \DateTimeImmutable();
                $video->setAddedAt($addedAt->setTimezone((new \DateTime())->getTimezone()));
                $video->addUser($this->userRepository->find($this->id));

                $this->tikTokVideoRepository->add($video, true);

                $is_there_a_new_one = true;
            }
        }

        if (!count($this->videos) || $is_there_a_new_one) {
            $this->videos = $this->tikTokVideoRepository->findUserTikToksByDate($this->id);
//            $this->videos = $this->tikTokVideoRepository->findUserTikToksByDate($this->user->getId());
        }
        $count = $this->tikTokVideoRepository->countUserTikToks($this->id);
//        $count = $this->tikTokVideoRepository->countUserTikToks($this->user->getId());

        return ['videos' => $this->videos, 'count' => $count[0]['count']];
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function thumbnail($item): string
    {
        // https://p16-sign-va.tiktokcdn.com/obj/tos-maliva-p-0068/6de148409b5d4a1fb4e752a0a2dd8001?x-expires=1656820800&x-signature=LV%2BoEEUMowEgWFRsWb97JEZfQDg%3D

        $tiktok = $item['tiktok'];
        $videoId = $tiktok['video_id'];
        $expired = false;

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
            /** @var TikTokVideo $video */
            $video = $this->tikTokVideoRepository->findOneBy(['videoId' => $videoId]);

            $link = $tiktok['author_url'] . '/video/' . $tiktok['video_id'];
            $standing = $this->tikTokService->getVideo($link);
            if ($standing) {
                $tiktok = json_decode($standing, true);
                $video->setThumbnailUrl($tiktok['thumbnail_url']);
                $video->setThumbnailHasExpired(false);
            } else {
                $video->setThumbnailHasExpired(true);
                $expired = true;
            }
            $this->entityManager->persist($video);
            $this->entityManager->flush();
        }
        return $expired ? '/images/default/tiktok_dark.jpg' : $tiktok['thumbnail_url'];
    }
}
