<?php

namespace App\Controller;

use App\Entity\Settings;
use App\Entity\User;
use App\Entity\YoutubeChannel;
use App\Entity\YoutubeVideo;
use App\Entity\YoutubeVideoTag;
use App\Repository\SettingsRepository;
use App\Repository\UserRepository;
use App\Repository\YoutubeChannelRepository;
use App\Repository\YoutubeVideoRepository;
use App\Repository\YoutubeVideoTagRepository;
use App\Service\DateService;
use DateInterval;
use DateTimeImmutable;
use Google\Exception;
use Google\Service\YouTube\ChannelListResponse;
use Google\Service\YouTube\VideoListResponse;
use Google_Client;
use Google_Service_YouTube;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class YoutubeController extends AbstractController
{
    //
    // Clé API : AIzaSyDIBSBnQs6LAxrCO4Bj8uNbbqcJXt78W_M
    //

    private Google_Service_YouTube $service_YouTube;

    /**
     * @throws Exception
     */
    public function __construct(
        private readonly DateService               $dateService,
        private readonly SettingsRepository        $settingsRepository,
        private readonly TranslatorInterface       $translator,
        private readonly YoutubeChannelRepository  $channelRepository,
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

    #[Route('/{_locale}/youtube', name: 'app_youtube', requirements: ['_locale' => 'fr|en|de|es'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $settings = $this->settingsRepository->findOneBy(['user' => $user, 'name' => "youtube"]);
        if ($settings == null) {
            $settings = new Settings();
            $settings->setUser($this->getUser());
            $settings->setName("youtube");
            $settings->setData(['sort' => 'addedAt', 'order' => 'DESC', 'page' => 1]);
            $this->settingsRepository->save($settings, true);
        }
        $settings = $settings->getData();
        $order = $settings['order'];
        $sort = $settings['sort'];
//        $page = $settings['page'];
//        dump([
//            "data" => $settings,
//            "order" => $order,
//            "sort" => $sort,
//            "page" => $page,
//        ]);

        $vids = $this->videoRepository->findAllWithChannelByDate($user->getId(), $sort, $order);
        $videoCount = $this->getVideosCount($user);
        $totalRuntime = $this->getTotalRuntime($user);
        $firstView = $this->getFirstView($user);
        $time2Human = $this->getTime2human($totalRuntime, $request->getLocale());
        $preview = $this->getPreview();

        return $this->render('youtube/index.html.twig', [
            'videos' => $this->getVideos($vids),
            'videoCount' => $videoCount,
            'totalRuntime' => $totalRuntime,
            'firstView' => $firstView,
            'time2Human' => $time2Human,
            'settings' => $settings,
            'justAdded' => false,
            'preview' => $preview,
            'from' => 'youtube',
        ]);
    }

    #[Route('/youtube/more', name: 'app_youtube_more')]
    public function moreVideos(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var YoutubeVideo [] $vids */
        $vids = $this->videoRepository->findAllWithChannelByDate($request->query->get('id'), $request->query->get('sort'), $request->query->get('order'), $request->query->get('offset'));

        return $this->json([
            'results' => $this->getVideos($vids),
        ]);
    }

    public function getVideos($vids): array
    {
        $videos = [];

        foreach ($vids as $vid) {
            $video = [];
            $video['id'] = $vid['id'];
            $video['thumbnailMediumPath'] = $vid['thumbnailMediumPath'];
            $video['title'] = $vid['title'];
            $video['contentDuration'] = $vid['contentDuration'];
            $video['publishedAt'] = $vid['publishedAt'];
            $video['channel'] = [];
            $video['channel']['title'] = $vid['channelTitle'];
            $video['channel']['customUrl'] = $vid['channelCustomUrl'];
            $video['channel']['youtubeId'] = $vid['channelYoutubeId'];
            $video['channel']['thumbnailDefaultUrl'] = $vid['channelThumbnailDefaultUrl'];

            $video['tags'] = [];

            $videos[] = $video;
        }
        $videoIds = array_map(fn($v) => $v['id'], $videos);
        $tags = $this->videoTagRepository->findVideosTags($videoIds);

        foreach ($videos as &$video) {
            $video['tags'] = [];
            foreach ($tags as $tag) {
                if ($tag['videoId'] == $video['id']) {
                    $video['tags'][] = $tag;
                }
            }
        }
        return $videos;
    }

    #[Route('/{_locale}/youtube/video/{id}', name: 'app_youtube_video', requirements: ['_locale' => 'fr|en|de|es'])]
    public function video(Request $request, YoutubeVideo $youtubeVideo): Response
    {
        $userAlreadyLinked = $request->query->get('user-already-linked');

        $tags = $this->videoTagRepository->findAllByLabel();
        $description = preg_replace(
            [
                '/(https:\/\/\S+)/',
                '/(http:\/\/\S+)/',
                '/([A-Za-z_-][A-Za-z0-9_-]*@[a-z0-9_-]+(\.[a-z0-9_-]+)+)/'
            ],
            [
                '<a href="$1" target="_blank" rel="noopener">$1</a>',
                '<a href="$1" target="_blank" rel="noopener">$1</a>',
                '<a href="mailto:$1">$1</a>'
            ],
            $youtubeVideo->getDescription());
        $description = nl2br($description);

        return $this->render('youtube/show.html.twig', [
                'video' => $youtubeVideo,
                'description' => $description,
                'other_tags' => array_diff($tags, $youtubeVideo->getTags()->toArray()),
                'userAlreadyLinked' => $userAlreadyLinked,
            ]
        );
    }

    #[Route('/{_locale}/youtube/search', name: 'app_youtube_search', requirements: ['_locale' => 'fr|en|de|es'])]
    public function search(YoutubeVideoTagRepository $tagRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();
        $allTags = $tagRepository->findAllByLabel();
        return $this->render('youtube/search.html.twig', [
            'allTags' => $allTags,
            'user' => $user,
        ]);
    }

    #[Route('/{_locale}/youtube/video_by_tag', name: 'app_youtube_video_by_tag', requirements: ['_locale' => 'fr|en|de|es'])]
    public function searchVideoByTag(Request $request, YoutubeVideoRepository $videoRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();
        $list = $request->query->get("tags");
        $method = $request->query->get("m");
        $ids = [];
        $videos = [];
        $tagIds = explode(',', $list);
        $count = count($tagIds);

        // Toutes les vidéos
        if ($list) {
            $videoIds = $videoRepository->videosByTag($user->getId(), $list, $count, $method);
            foreach ($videoIds as $videoId) {
                $ids[] = $videoId['id'];
            }
            $videos = $videoRepository->findBy(['id' => $ids], ['publishedAt' => 'DESC']);
        }

        return $this->render('blocks/youtube/_video_search.html.twig', [
            'videos' => $videos,
            'list' => $tagIds,
            'type' => '',
        ]);
    }

    #[Route('/{_locale}/youtube/video/add/tag/{id}/{tag}', name: 'app_youtube_video_add_tag', requirements: ['_locale' => 'fr|en|de|es'])]
    public function addTag($tag, YoutubeVideo $youtubeVideo, YoutubeVideoTagRepository $tagRepository, YoutubeVideoRepository $videoRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $existingTags = $tagRepository->findAll();
        $newTagId = -1;
        $fromList = false; // Was the tag in the list <datalist>

        $videoTags = $youtubeVideo->getTags()->toArray();
        if (!in_array($tag, $videoTags)) {

            $newTag = $tagRepository->findOneBy(['label' => $tag]);

            if (!$newTag) {
                $newTag = new YoutubeVideoTag();
                $newTag->setLabel($tag);
                $tagRepository->add($newTag, true);
                $newTag = $tagRepository->findOneBy(['label' => $tag]);
            } else {
                $fromList = true;
            }
            $youtubeVideo->addTag($newTag);
            $videoRepository->add($youtubeVideo, true);
            $newTagId = $newTag->getId();
        }

        $others_tags = [];
        $diff = array_diff($existingTags, $youtubeVideo->getTags()->toArray());
        foreach ($diff as $t) {
            $others_tags[] = $t->getLabel();
        }

        return $this->json([
            "new_tag" => $tag,
            "new_tag_id" => $newTagId,
            "other_tags" => $others_tags,
            "rebuild_list" => $fromList,
        ]);
    }

    #[Route('/{_locale}/youtube/video/remove/tag/{id}/{tag}', name: 'app_youtube_video_remove_tag', requirements: ['_locale' => 'fr|en|de|es'])]
    public function removeTag($tag, YoutubeVideo $youtubeVideo, YoutubeVideoRepository $videoRepository, YoutubeVideoTagRepository $tagRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $videoTag = $tagRepository->find($tag);
        $youtubeVideo->removeTag($videoTag);
        $videoRepository->add($youtubeVideo, true);
//        $tagRepository->add($videoTag, true);

        $videoTags = [];
        $tags = $youtubeVideo->getTags()->toArray();
        foreach ($tags as $t) {
            $videoTags[] = $t->getLabel();
        }

        return $this->json([
            'tags' => $videoTags,
        ]);
    }

    #[Route('/{_locale}/youtube/video/delete/{id}', name: 'app_youtube_video_delete', requirements: ['_locale' => 'fr|en|de|es'])]
    public function removeVideo($id, UserRepository $userRepository, YoutubeVideoRepository $youtubeVideoRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $video = $youtubeVideoRepository->find($id);
        $user->removeYoutubeVideo($video);
        $userRepository->save($user, true);

        return $this->json([$video->getTitle()]);
    }

    #[Route('/{_locale}/youtube/add/video', name: 'app_youtube_add_video', requirements: ['_locale' => 'fr|en|de|es'], methods: ['GET'])]
    public function addVideo(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $locale = $request->getLocale();
        $providedLink = $request->query->get('link');
        dump($providedLink);
        $justAdded = 0;
        $userAlreadyLinked = false;
        $status = "error";
        $message = "An error occurred";
        $subMessage = "";

        if (str_contains($providedLink, "shorts")) {
            if (str_contains($providedLink, "www")) {
                // https://www.youtube.com/shorts/7KFxzeyse2g
                $providedLink = preg_replace("/https:\/\/www\.youtube\.com\/shorts\/(.+)/", "$1", $providedLink);
            } else {
                // https://youtube.com/shorts/XxpFBkm5XqI?feature=share
                $providedLink = preg_replace("/https:\/\/youtube\.com\/shorts\/(.+)\?feature=share/", "$1", $providedLink);
            }
        } elseif (str_contains($providedLink, "youtu.be")) {
            // https://youtu.be/at9h35V8rtQ
            $providedLink = preg_replace("/https:\/\/youtu\.be\/(.+)/", "$1", $providedLink);
        } elseif (str_contains($providedLink, 'watch')) {
            // https://www.youtube.com/watch?v=at9h35V8rtQ
            // https://www.youtube.com/watch?v=IzHJ7Jnj2LU&pp=wgIGCgQQAhgB
            $providedLink = preg_replace("/https:\/\/www\.youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)(?>&[a-z_-]+=.+)*/", "$1", $providedLink);
        } elseif (str_contains($providedLink, 'live')) {
            // https://www.youtube.com/live/vBWcWIim5Js?feature=share
            $providedLink = preg_replace("/https:\/\/www\.youtube\.com\/live\/([a-zA-Z0-9_-]+)(?>\?[a-zA-Z]+=.+)*/", "$1", $providedLink);
        }

        if (strlen($providedLink) == 11) {
            dump($providedLink);
            $link = $this->videoRepository->findOneBy(['link' => $providedLink]);

            // Si le lien n'a pas déjà été ajouté 12345678912
            if ($link == null) {

                $videoListResponse = $this->getVideoSnippet($providedLink);
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
                $newVideo->setAddedAt($this->dateService->newDateImmutable('now', 'Europe/Paris'));
                $newVideo->addUser($user);

                $this->videoRepository->add($newVideo, true);

                $justAdded = $newVideo->getId();

                $status = "ok";
                $message = $this->translator->trans("Video added!");
                $subMessage = "<a href='/" . $locale . "/youtube/video/" . $justAdded . "'>🔗 ";
                $subMessage .= $this->translator->trans("Go to the video page to see it");
                $subMessage .= " 🔗</a>";
            } else {
                // Si le lien a déjà été ajouté, on vérifie que l'utilisateur n'est pas déjà lié à la vidéo
                $users = $link->getUsers();
                foreach ($users as $u) {
                    if ($u->getId() == $user->getId()) {
                        $userAlreadyLinked = true;
                        $status = "ok";
                        $message = $this->translator->trans("Video already added!");
                        $subMessage = "<a href='/" . $locale . "/youtube/video/" . $link->getId() . "'>🔗 ";
                        $subMessage .= $this->translator->trans("Go to the video page to see it");
                        $subMessage .= " 🔗</a>";
                    }
                }
                // Si l'utilisateur n'est pas encore lié à la vidéo, on le lie
                if (!$userAlreadyLinked) {
                    $link->addUser($user);
                    $this->videoRepository->add($link, true);
                    $status = "ok";
                    $message = $this->translator->trans("Video added!");
                    $subMessage = "<a href='/" . $locale . "/youtube/video/" . $link->getId() . "'>🔗 ";
                    $subMessage .= $this->translator->trans("Go to the video page to see it");
                    $subMessage .= " 🔗</a>";
                }
                $justAdded = $link->getId();
            }
        }

        $settings = $this->settingsRepository->findOneBy(['user' => $user, 'name' => "youtube"]);
        $data = $settings->getData();
        $gotoVideoPage = intval($data['page']);

        if ($gotoVideoPage) {
            $videosBlock = "";
            $h1innerText = "";
            $videoCount = 0;
            $totalRuntime = 0;
            $time2Human = "";
        } else {
            $sort = $data['sort'];
            $order = $data['order'];
            $vids = $this->videoRepository->findAllWithChannelByDate($user->getId(), $sort, $order);
            $videos = $this->getVideos($vids);
            $videoCount = $this->getVideosCount($user);
            $firstView = $this->getFirstView($user);
            $h1innerText = $videoCount . " " . $this->translator->trans('videos') . " " . $this->translator->trans('since') . " " . $this->dateService->formatDate($firstView, "Europe/Paris", $request->getLocale());
            $totalRuntime = $this->getTotalRuntime($user);
            $time2Human = $this->getTime2human($totalRuntime, $request->getLocale());

            $videosBlock = $this->render('blocks/youtube/_videos.html.twig', [
                'videos' => $videos,
                'type' => '',
            ]);
        }

        return $this->json([
            'status' => $status,
            'message' => $message,
            'subMessage' => $subMessage,
            'justAdded' => $justAdded,
            'gotoVideoPage' => $gotoVideoPage,
            'userAlreadyLinked' => $userAlreadyLinked,
            'videosBlock' => $videosBlock,
            'videoCount' => $videoCount,
            'h1innerText' => $h1innerText,
            'totalRuntime' => $totalRuntime,
            'time2Human' => $time2Human,
        ]);
    }

    public function getVideosCount(User $user): int
    {
        return count($user->getYoutubeVideos());
    }

    public function getTotalRuntime(User $user): int
    {
        return $this->videoRepository->getUserYTVideosRuntime($user->getId()) ?? 0;
    }

    public function getFirstView($user): ?DateTimeImmutable
    {
        $firstAddedVideo = $this->videoRepository->firstAddedYTVideo($user->getId());
        if ($firstAddedVideo) {
            $last = $firstAddedVideo->getAddedAt();
        } else {
            $last = new DateTimeImmutable("now");
        }
        return $last;
    }

    public function getPreview(): array|null
    {
        $previews = ['FhNiY_n0rmc', 'UoRyxgdFJ5Y', 'NCHMT-nQ-8c', 'tBTZ96Iit2g', 'T94JsAgK1X8', 'W9b8ifsDons', 'qOVT9rYda2o', 'qOVT9rYda2o', 'esNfg_XbXMY', 'lqttiQMLTbI', '9sLiQ7DKJ2g', 'q5D55G7Ejs8', 'R4bkKkooa-A', 'ieDIpgso4no', 'n0GSZtPEQs0', 'sbriUP3Pp5s', 'kDsC-fHC0vE', '2k-I_8lhS0w', 'iHTntTTa2io', 'uhMKEd18m_s', 'pVoRFDjq8-g', 'P5UZgiENdx0', 'at9h35V8rtQ', 'Mf1TwEySpno', '2kqvfoUUhA4', 'MUxcCgx4VlI', '6qiK5oQ_Vwk', '85gW-XY3fSE', '1Z5SRVURcIA', 'u044iM9xsWU', 'dWtG6DFFb1E', 'gmKINSHqryc', 'l8e8-8K1G0Y', 'xD_5BsMDBHY'];
        $preview_index = array_rand($previews);
        $preview = $previews[$preview_index];

        $videoListResponse = $this->getVideoSnippet($preview);
        $items = $videoListResponse->getItems();
        $item = $items[0];
        $snippet = $item['snippet'];

        $thumbnails = (array)$snippet['thumbnails'];
        if (array_key_exists('medium', $thumbnails))
            return ['link' => $preview, 'url' => $thumbnails['medium']['url'], 'title' => $snippet['title']];
        if (array_key_exists('default', $thumbnails))
            return ['link' => $preview, 'url' => $thumbnails['default']['url'], 'title' => $snippet['title']];
        return ['link' => '', 'url' => '', 'title' => ''];
    }

    private function getVideoSnippet($videoId): VideoListResponse
    {
        return $this->service_YouTube->videos->listVideos('contentDetails, snippet', ['id' => $videoId]);
    }

    private function getChannelSnippet($channelId): ChannelListResponse
    {
        return $this->service_YouTube->channels->listChannels('snippet', ['id' => $channelId]);
    }

    private function iso8601ToSeconds($input): int
    {
        try {
            $duration = new DateInterval($input);
            $hours_to_seconds = $duration->h * 60 * 60;
            $minutes_to_seconds = $duration->i * 60;
            $seconds = $duration->s;
            return $hours_to_seconds + $minutes_to_seconds + $seconds;
        } catch (\Exception) {
            return 0;
        }
    }

    private function getTime2human($ss, $locale): string
    {
        if ($ss) {
            $l = $locale;
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

    #[Route('/youtube/settings/save', name: 'youtube_settings_save', methods: ['GET'])]
    public function saveSettings(Request $request): Response
    {
        $sort = $request->query->get('sort');
        $order = $request->query->get('order');
        $page = $request->query->get('page');
        /** @var User $user */
        $user = $this->getUser();
        $settings = $this->settingsRepository->findOneBy(['user' => $user, 'name' => 'youtube']);
        $data = $settings->getData();
        $message = "Settings saved";
        $subMessage = "";

        if ($sort !== null) {
            $data['sort'] = $sort;
            $subMessage = "Sort by $sort";
        }
        if ($order !== null) {
            $data['order'] = $order;
            $subMessage = "Sort in $order order";
        }

        if ($page !== null) {
            $page = intval($page);
            $data['page'] = $page;
            $subMessage = $page ? "Go to the video page after" : "Stay on current page";
        }
        $settings->setData($data);
        $this->settingsRepository->save($settings, true);

        return $this->json([
            'status' => 'ok',
            'message' => $this->translator->trans($message),
            'subMessage' => $this->translator->trans($subMessage),
        ]);
    }
}
