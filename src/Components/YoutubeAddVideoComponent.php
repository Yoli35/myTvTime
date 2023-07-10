<?php

namespace App\Components;

use App\Controller\UserController;
use App\Controller\YoutubeController;
use App\Entity\YoutubeChannel;
use App\Entity\YoutubeVideo;
use App\Repository\SettingsRepository;
use App\Repository\UserRepository;
use App\Repository\YoutubeChannelRepository;
use App\Repository\YoutubeVideoRepository;
use App\Repository\YoutubeVideoTagRepository;
use DateInterval;

//use Google\ApiCore\ValidationException;
//use Google\Cloud\Translate\V3\TranslationServiceClient;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Google\Exception;
use Google\Service\YouTube\ChannelListResponse;
use Google\Service\YouTube\VideoListResponse;
use Google_Client;
use Google_Service_YouTube;

//use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('youtube_add_video', csrf: false)]
class YoutubeAddVideoComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $link = '';
    #[LiveProp(writable: true)]
    public bool $page = true;
    #[LiveProp(writable: true)]
    public string $sort = 'addedAt';
    #[LiveProp(writable: true)]
    public string $order = 'DESC';
    #[LiveProp]
    public int $justAdded = 0;
    #[LiveProp]
    public bool $userAlreadyLinked = false;
    #[LiveProp]
    public int $user_id = 0;
    #[LiveProp]
    public int $videoCount = 0;
    #[LiveProp]
    public int $totalRuntime = 0;
    #[LiveProp]
    public string $locale = '';
    #[LiveProp]
    public string $preview = '';
    #[LiveProp]
    public string $preview_url = "";
    #[LiveProp]
    public string $preview_title = "";
    #[LiveProp]
    public array $videos = [];
    #[LiveProp]
    public DateTimeImmutable $firstView;
    #[LiveProp]
    public string $time2Human = "";

    private string $oldSort = "";
    private string $oldOrder = "";

    private Google_Service_YouTube $service_YouTube;


    /**
     * @throws Exception
     */
    public function __construct(
        private readonly EntityManagerInterface    $entityManager,
        private readonly SettingsRepository        $settingsRepository,
        private readonly UserController            $userController,
        private readonly UserRepository            $userRepository,
        private readonly YoutubeChannelRepository  $channelRepository,
        private readonly YoutubeController         $youtubeController,
        private readonly YoutubeVideoRepository    $videoRepository,
        private readonly YoutubeVideoTagRepository $videoTagRepository,
    )
    {
        $client = new Google_Client();
        $client->setApplicationName('mytvtime');
        $client->setScopes(['https://www.googleapis.com/auth/youtube.readonly',]);
        $client->setAuthConfig('../config/google/mytvtime-349019-001b2f815d02.json');
        $client->setAccessType('offline');

        $this->service_YouTube = new Google_Service_YouTube($client);
    }

    public function mount($id, $preview, $settings, $locale): void
    {
        $this->user_id = $id;
        $this->locale = $locale;
        $this->preview = $preview;

        $this->sort = $settings['sort'];
        $this->order = $settings['order'];

        $this->oldSort = $this->sort;
        $this->oldOrder = $this->order;

        $this->videos = $this->getVideos();
        $this->videoCount = $this->getVideosCount();
        $this->totalRuntime = $this->getTotalRuntime();
        $this->firstView = $this->getFirstView();
        $this->time2Human = $this->getTime2human();
        list($this->preview_url, $this->preview_title) = $this->get_preview();
    }

    public function newVideo(): array
    {
        $thisLink = $this->link;
        $this->justAdded = 0;
//        $this->userController->isFullyConnected();

        if ($this->oldSort !== $this->sort || $this->oldOrder !== $this->order) {
            $this->saveSettings();
        }

        // https://www.youtube.com/watch?v=sXmwkTMDjkA
        if (str_contains($thisLink, "shorts")) {
            if (str_contains($thisLink, "www")) {
                // https://www.youtube.com/shorts/7KFxzeyse2g
                $thisLink = preg_replace("/https:\/\/www\.youtube\.com\/shorts\/(.+)/", "$1", $thisLink);
            } else {
                // https://youtube.com/shorts/XxpFBkm5XqI?feature=share
                $thisLink = preg_replace("/https:\/\/youtube\.com\/shorts\/(.+)\?feature=share/", "$1", $thisLink);
            }
        } elseif (str_contains($thisLink, "youtu.be")) {
            // https://youtu.be/at9h35V8rtQ
            $thisLink = preg_replace("/https:\/\/youtu\.be\/(.+)/", "$1", $thisLink);
        } elseif (str_contains($thisLink, 'watch')) {
            // https://www.youtube.com/watch?v=at9h35V8rtQ
            // https://www.youtube.com/watch?v=IzHJ7Jnj2LU&pp=wgIGCgQQAhgB
            $thisLink = preg_replace("/https:\/\/www\.youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)(?>&[a-z_-]+=.+)*/", "$1", $thisLink);
        } elseif (str_contains($thisLink, 'live')) {
            // https://www.youtube.com/live/vBWcWIim5Js?feature=share
            $thisLink = preg_replace("/https:\/\/www\.youtube\.com\/live\/([a-zA-Z0-9_-]+)(?>\?[a-zA-Z]+=.+)*/", "$1", $thisLink);
        }

        if (strlen($thisLink) == 11) {

            $user = $this->userRepository->find($this->user_id);

            $link = $this->videoRepository->findOneBy(['link' => $thisLink]);

            // Si le lien n'a pas déjà été ajouté 12345678912
            if ($link == null) {

                $videoListResponse = $this->getVideoSnippet($thisLink);
                $items = $videoListResponse->getItems();
                $item = $items[0];
                $snippet = $item['snippet'];

                $channel = $this->channelRepository->findOneBy(['youtubeId' => $snippet['channelId']]);

                $channelListResponse = $this->getChannelSnippet($snippet['channelId']);
                $items = $channelListResponse->getItems();
                $item = $items[0];
                $snippet = $item['snippet'];
                $thumbnails = (array)$snippet['thumbnails'];
                $localized = $snippet['localized'];

                if ($channel == null) {
                    $channel = new YoutubeChannel();
                }
                //
                // if channel already stored in db, it might have change everything
                // so update all infos
                //
                $channel->setYoutubeId($item['id']);
                $channel->setTitle($snippet['title']);
                $channel->setDescription($snippet['description']);
                $channel->setCustomUrl($snippet['customUrl']);
                $channel->setPublishedAt(date_create_immutable($snippet['publishedAt']));
                if (array_key_exists('default', $thumbnails)) $channel->setThumbnailDefaultUrl($thumbnails['default']['url']);
                if (array_key_exists('medium', $thumbnails)) $channel->setThumbnailMediumUrl($thumbnails['medium']['url']);
                if (array_key_exists('high', $thumbnails)) $channel->setThumbnailHighUrl($thumbnails['high']['url']);
                $channel->setLocalizedDescription($localized['description']);
                $channel->setLocalizedTitle($localized['title']);
                $channel->setCountry($snippet['country']);

                $this->channelRepository->add($channel, true);

                $items = $videoListResponse->getItems();
                $item = $items[0];
                $snippet = $item['snippet'];
                $thumbnails = (array)$snippet['thumbnails'];
                $localized = $snippet['localized'];
                $contentDetails = $item['contentDetails'];

                $newVideo = new YoutubeVideo();
                $newVideo->setLink($item->id);
                $newVideo->setCategoryId($snippet['categoryId']);
                $newVideo->setChannel($channel);
                $newVideo->setDefaultAudioLanguage($snippet['defaultAudioLanguage'] ?: "");
                $newVideo->setDescription($snippet['description']);
                $newVideo->setPublishedAt(date_create_immutable($snippet['publishedAt']));
                $newVideo->setTitle($snippet['title']);
                if (array_key_exists('default', $thumbnails)) $newVideo->setThumbnailDefaultPath($thumbnails['default'] ? $thumbnails['default']['url'] : null);
                if (array_key_exists('medium', $thumbnails)) $newVideo->setThumbnailMediumPath($thumbnails['medium'] ? $thumbnails['medium']['url'] : null);
                if (array_key_exists('high', $thumbnails)) $newVideo->setThumbnailHighPath($thumbnails['high'] ? $thumbnails['high']['url'] : null);
                if (array_key_exists('standard', $thumbnails)) $newVideo->setThumbnailStandardPath($thumbnails['standard'] ? $thumbnails['standard']['url'] : null);
                if (array_key_exists('maxres', $thumbnails)) $newVideo->setThumbnailMaxresPath($thumbnails['maxres'] ? $thumbnails['maxres']['url'] : null);
                $newVideo->setLocalizedDescription($localized['description']);
                $newVideo->setLocalizedTitle($localized['title']);
                $newVideo->setContentDefinition($contentDetails['definition']);
                $newVideo->setContentDimension($contentDetails['dimension']);
                $newVideo->setContentDuration($this->iso8601ToSeconds($contentDetails['duration']));
                $newVideo->setContentProjection($contentDetails['projection']);
                $addedAt = new DateTimeImmutable();
                $newVideo->setAddedAt($addedAt->setTimezone((new DateTime())->getTimezone()));
                $newVideo->addUser($user);

                $this->videoRepository->add($newVideo, true);

                $this->justAdded = $newVideo->getId();

                $this->videos = $this->getVideos();
                $this->videoCount = $this->getVideosCount();
                $this->totalRuntime = $this->getTotalRuntime();
                $this->time2Human = $this->getTime2human();
            } else {
                // Si le lien a déjà été ajouté, on vérifie que l'utilisateur n'est pas déjà lié à la vidéo
                $users = $link->getUsers();
                $this->userAlreadyLinked = false;
                foreach ($users as $u) {
                    if ($u->getId() == $user->getId()) {
                        $this->userAlreadyLinked = true;
                    }
                }
                // Si l'utilisateur n'est pas encore lié à la vidéo, on le lie
                if (!$this->userAlreadyLinked) {
                    $link->addUser($user);
                    $this->videoRepository->add($link, true);
                }
                $this->justAdded = $link->getId();
            }
        }

        $firstVideo = count($this->videos) ? $this->videos[0] : [];
        if (gettype($firstVideo) == 'array') {

            $this->videos = $this->getVideos();
            $this->videoCount = $this->getVideosCount();
            $this->totalRuntime = $this->getTotalRuntime();
            $this->time2Human = $this->getTime2human();
        }
        return $this->videos;
    }

    public function getVideosCount(): int
    {
        return count($this->userRepository->find($this->user_id)->getYoutubeVideos());
    }

    public function getVideos(): array
    {
        $vids = $this->videoRepository->findAllWithChannelByDate($this->user_id, $this->sort, $this->order);
        $vids = $this->youtubeController->getVideos($vids);

        return array_map(function ($vid) {
            $vid['publishedAt'] = $vid['publishedAt']->format('Y-m-d H:i:s');
            return $vid;
        }, $vids);
    }

    public function getTotalRuntime(): int
    {
        return $this->videoRepository->getUserYTVideosRuntime($this->user_id) ?: 0;
    }

    public function getFirstView(): ?DateTimeImmutable
    {
        if (count($this->videos)) {
            $firstAddedVideo = $this->videoRepository->firstAddedYTVideo($this->user_id);
            $last = $firstAddedVideo->getAddedAt();
        } else {
            $last = new DateTimeImmutable("now");
        }
        return $last;
    }

    public function get_preview(): array|null
    {
        $videoListResponse = $this->getVideoSnippet($this->preview);
        $items = $videoListResponse->getItems();
        $item = $items[0];
        $snippet = $item['snippet'];
        $thumbnails = (array)$snippet['thumbnails'];

        if (array_key_exists('medium', $thumbnails))
            return [$thumbnails['medium']['url'], $snippet['title']];

        if (array_key_exists('default', $thumbnails))
            return [$thumbnails['default']['url'], $snippet['title']];

        return ['', ''];
    }

    private function getVideoSnippet($videoId): VideoListResponse
    {
        return $this->service_YouTube->videos->listVideos('contentDetails, snippet', ['id' => $videoId]);
    }

    private function getChannelSnippet($channelId): ChannelListResponse
    {
        return $this->service_YouTube->channels->listChannels('snippet', ['id' => $channelId]);
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

    private function getTime2human(): string
    {
        $ss = $this->totalRuntime;
        if ($ss) {
            $l = $this->locale;
            $words = ["timeSpent1" => ["en" => "Time spent watching Youtube", "fr" => "Temps passé devant youtube", "es" => "Tiempo dedicado a ver Youtube", "de" => "Zeit, die Sie mit Youtube verbracht haben"], "timeSpent2" => ["en" => "secondes i.e.", "fr" => "secondes c.à.d.", "es" => "segundos, es decir,", "de" => "Sekunden d.h."], "month" => ["en" => "month", "fr" => "mois", "es" => "mes", "de" => "Monat"], "months" => ["en" => "months", "fr" => "mois", "es" => "meses", "de" => "Monate"], "day" => ["en" => "day", "fr" => "jour", "es" => "día", "de" => "Tag"], "days" => ["en" => "days", "fr" => "jours", "es" => "días", "de" => "Tage"], "hour" => ["en" => "hour", "fr" => "heure", "es" => "hora", "de" => "Stunde"], "hours" => ["en" => "hours", "fr" => "heures", "es" => "horas", "de" => "Stunden"], "minute" => ["en" => "minute", "fr" => "minute", "es" => "minuto", "de" => "Minute"], "minutes" => ["en" => "minutes", "fr" => "minutes", "es" => "minutos", "de" => "Minuten"], "seconde" => ["en" => "seconde", "fr" => "seconde", "es" => "segundo", "de" => "Sekunde"], "secondes" => ["en" => "secondes", "fr" => "secondes", "es" => "segundos", "de" => "Sekunden"], "and" => ["en" => "and", "fr" => "et", "es" => "y", "de" => "und"],];
            $s = $ss % 60;
            $m = intval(floor(($ss % 3600) / 60));
            $h = intval(floor(($ss % 86400) / 3600));
            $d = intval(floor(($ss % 2592000) / 86400));
            $M = intval(floor($ss / 2592000));

            $result = $words['timeSpent1'][$l] . " : " . $ss . " " . $words['timeSpent2'][$l] . " ";
            if ($M) {
                $result .= $M . " " . ($M > 1 ? $words['months'][$l] : $words['month'][$l]);
            }
            if ($d) {
                if ($M) {
                    $result .= ($s || $m || $h) ? ", " : " " . $words['and'][$l] . " ";
                }
                $result .= $d . " " . ($d > 1 ? $words['days'][$l] : $words['day'][$l]);
            }
            if ($h > 0) {
                if ($M || $d) {
                    $result .= ($s || $m) ? ", " : " " . $words['and'][$l] . " ";
                }
                $result .= $h . " " . ($h > 1 ? $words['hours'][$l] : $words['hour'][$l]);
            }
            if ($m) {
                if ($M || $d || $h) {
                    $result .= ($s) ? ", " : " " . $words['and'][$l] . " ";
                }
                $result .= $m . " " . ($m > 1 ? $words['minutes'][$l] : $words['minute'][$l]);
            }
            if ($s) {
                if ($M || $d || $h || $m) {
                    $result .= " " . $words['and'][$l] . " ";
                }
                $result .= $s . " " . ($s > 1 ? $words['secondes'][$l] : $words['seconde'][$l]);
            }
            return $result;
        }
        return "";
    }

    private function saveSettings(): void
    {
        $user = $this->userRepository->findOneBy(['id' => $this->user_id]);
        $settings = $this->settingsRepository->findOneBy(['user' => $user, 'name' => 'youtube']);
        $settings->setData(['sort' => $this->sort, 'order' => $this->order]);
        $this->settingsRepository->save($settings, true);
        $this->oldSort = $this->sort;
        $this->oldOrder = $this->order;
    }
}
