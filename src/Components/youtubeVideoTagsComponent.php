<?php

namespace App\Components;

use App\Entity\YoutubeVideoTag;
use App\Repository\YoutubeChannelRepository;
use App\Repository\YoutubeVideoRepository;
use App\Repository\YoutubeVideoTagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('youtube_video_tags')]
class youtubeVideoTagsComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $tag_query = '';
    #[LiveProp]
    public int $user_id = 0;
    #[LiveProp]
    public int $video_id = 0;
    #[LiveProp]
    public array $videoTags = [];

    private EntityManagerInterface $entityManager;
    private YoutubeVideoRepository $videoRepository;
    private YoutubeChannelRepository $channelRepository;
    private YoutubeVideoTagRepository $tagRepository;

    public function __construct(YoutubeVideoTagRepository $tagRepository, YoutubeVideoRepository $videoRepository, YoutubeChannelRepository $channelRepository, EntityManagerInterface $entityManager)
    {
        $this->tagRepository = $tagRepository;
        $this->videoRepository = $videoRepository;
        $this->channelRepository = $channelRepository;
        $this->entityManager = $entityManager;
//
//        $client = new Google_Client();
//        $client->setApplicationName('mytvtime');
//        $client->setScopes(['https://www.googleapis.com/auth/youtube.readonly',]);
//        $client->setAuthConfig('../config/google/mytvtime-349019-001b2f815d02.json');
//        $client->setAccessType('offline');
//
//        $this->service_YouTube = new Google_Service_YouTube($client);
    }

    public function mount($id, $video): void
    {
        $this->user_id = $id;
        $this->video_id = $video;
    }

    public function results(): array
    {
        $tag = $this->tag_query;
        $tags = [];
        if (strlen($tag)) {
            $tags = $this->tagRepository->findByLabel($tag);
        }
        $this->videoTags = $this->videoRepository->find($this->video_id)->getTags()->toArray();

        return array_diff($tags, $this->videoTags);
    }
}