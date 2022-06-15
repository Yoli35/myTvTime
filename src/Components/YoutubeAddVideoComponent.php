<?php

namespace App\Components;

use App\Entity\YoutubeChannel;
use App\Entity\YoutubeVideo;
use App\Repository\YoutubeChannelRepository;
use App\Repository\YoutubeVideoRepository;
use DateInterval;
use DateTimeInterface;
use Google\Exception;
use Google\Service\YouTube\ChannelListResponse;
use Google\Service\YouTube\VideoListResponse;
use Google_Client;
use Google_Service_YouTube;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('youtube_add_video')]
class YoutubeAddVideoComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $link = '';
    #[LiveProp]
    public int $id;

    private YoutubeVideoRepository $repoYTV;
    private YoutubeChannelRepository $repoYTC;

    public function __construct(YoutubeVideoRepository $repoYTV, YoutubeChannelRepository $repoYTC)
    {
        $this->repoYTV = $repoYTV;
        $this->repoYTC = $repoYTC;
    }

    public function mount($id)
    {
        $this->id = $id;
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function video_results(): array
    {
        // https://www.youtube.com/watch?v=esNfg_XbXMY
        if (strlen($this->link) == 11) {

            $link = $this->repoYTV->findBy(['link' => $this->link, 'userId' => $this->id]);

            // Si le lien n'a pas déjà été ajouté
            if ($link == null) {

                $videoListResponse = $this->getVideoSnippet($this->link);
                $items = $videoListResponse->getItems();
                $item = $items[0];
                $snippet = $item['snippet'];
                dump($snippet['channelId'], $snippet['publishedAt']);
                $channel = $this->repoYTC->findOneBy(['youtubeId' => $snippet['channelId']]);

                if ($channel == null) {

                    $channelListResponse = $this->getChannellSnippet($snippet['channelId']);
                    $items = $channelListResponse->getItems();
                    $item = $items[0];
                    $snippet = $item['snippet'];
                    $thumbnails = (array)$snippet['thumbnails'];
                    $localised = $snippet['localized'];

                    $channel = new YoutubeChannel();
                    $channel->setYoutubeId($item['id']);
                    $channel->setTitle($snippet['title']);
                    $channel->setDescription($snippet['description']);
                    $channel->setCustomUrl($snippet['customUrl']);
                    $channel->setPublishedAt(date_create_immutable($snippet['publishedAt']));
                    if (array_key_exists('default', $thumbnails)) $channel->setThumbnailDefaultUrl($thumbnails['default']['url']);
                    if (array_key_exists('medium', $thumbnails)) $channel->setThumbnailMediumUrl($thumbnails['medium']['url']);
                    if (array_key_exists('high', $thumbnails)) $channel->setThumbnailHighUrl($thumbnails['high']['url']);
                    $channel->setLocalizedDescription($localised['description']);
                    $channel->setLocalizedTitle($localised['title']);
                    $channel->setCountry($snippet['country']);

                    $this->repoYTC->add($channel, true);
                }

                $items = $videoListResponse->getItems();
                $item = $items[0];
                $snippet = $item['snippet'];
                $thumbnails = (array)$snippet['thumbnails'];
                $localised = $snippet['localized'];
                $contentDetails = $item['contentDetails'];

                $new = new YoutubeVideo();
                $new->setLink($item->id);
                $new->setUserId($this->id);
                $new->setCategoryId($snippet['categoryId']);
                $new->setChannel($channel);
                $new->setDefaultAudioLanguage($snippet['defaultAudioLanguage'] ?: "");
                $new->setDescription($snippet['description']);
                $new->setPublishedAt(date_create_immutable($snippet['publishedAt']));
                $new->setTitle($snippet['title']);
                if (array_key_exists('default', $thumbnails)) $new->setThumbnailDefaultPath($thumbnails['default']['url']);
                if (array_key_exists('medium', $thumbnails)) $new->setThumbnailMediumPath($thumbnails['medium']['url']);
                if (array_key_exists('high', $thumbnails)) $new->setThumbnailHighPath($thumbnails['high']['url']);
                if (array_key_exists('standard', $thumbnails)) $new->setThumbnailStandardPath($thumbnails['standard']['url']);
                if (array_key_exists('maxres', $thumbnails)) $new->setThumbnailMaxresPath($thumbnails['maxres']['url']);
                $new->setLocalizedDescription($localised['description']);
                $new->setLocalizedTitle($localised['title']);
                $new->setContentDefinition($contentDetails['definition']);
                $new->setContentDimension($contentDetails['dimension']);
                $new->setContentDuration($this->iso8601ToSeconds($contentDetails['duration']));
                $new->setContentProjection($contentDetails['projection']);


                $this->repoYTV->add($new, true);

                $this->link = '';
            }
        }
        /** TODO ajouter le temps total */
        return $this->repoYTV->findAllByDate($this->id);
    }

    /**
     * @throws Exception
     */
    private function getVideoSnippet($videoId): VideoListResponse
    {
        $client = new Google_Client();
        $client->setApplicationName('mytvtime');
        $client->setScopes(['https://www.googleapis.com/auth/youtube.readonly',]);
        $client->setAuthConfig('../config/google/mytvtime-349019-001b2f815d02.json');
        $client->setAccessType('offline');

        $service = new Google_Service_YouTube($client);

        return $service->videos->listVideos('contentDetails, snippet', ['id' => $videoId]);
    }

    /**
     * @throws Exception
     */
    private function getChannellSnippet($channelId): ChannelListResponse
    {
        $client = new Google_Client();
        $client->setApplicationName('mytvtime');
        $client->setScopes(['https://www.googleapis.com/auth/youtube.readonly',]);
        $client->setAuthConfig('../config/google/mytvtime-349019-001b2f815d02.json');
        $client->setAccessType('offline');

        $service = new Google_Service_YouTube($client);

        return $service->channels->listChannels('snippet', ['id' => $channelId]);
    }

    /**
     * @throws \Exception
     */
    private function iso8601ToSeconds($input): int
    {
        $duration = new DateInterval($input);
        $hours_to_seconds = $duration->h * 60 * 60;
        $minutes_to_seconds = $duration->i * 60;
        $seconds = $duration->s;
        return $hours_to_seconds + $minutes_to_seconds + $seconds;
    }
}