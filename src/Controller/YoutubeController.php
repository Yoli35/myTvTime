<?php

namespace App\Controller;

use App\Entity\Settings;
use App\Entity\User;
use App\Entity\UserYVideo;
use App\Entity\VideoSeriesMatch;
use App\Entity\YoutubeChannel;
use App\Entity\YoutubePlaylist;
use App\Entity\YoutubeVideo;
use App\Entity\YoutubeVideoSeries;
use App\Entity\YoutubeVideoTag;
use App\Form\YoutubeVideoSeriesType;
use App\Repository\SerieViewingRepository;
use App\Repository\SettingsRepository;
use App\Repository\UserRepository;
use App\Repository\UserYVideoRepository;
use App\Repository\YoutubeChannelRepository;
use App\Repository\YoutubePlaylistRepository;
use App\Repository\YoutubeVideoRepository;
use App\Repository\YoutubeVideoSeriesRepository;
use App\Repository\YoutubeVideoTagRepository;
use App\Service\DateService;
use DateInterval;
use DateTimeImmutable;
use Google\Exception;
use Google\Service\YouTube\ChannelListResponse;
use Google\Service\YouTube\PlaylistImageListResponse;
use Google\Service\YouTube\PlaylistItemListResponse;
use Google\Service\YouTube\PlaylistListResponse;
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
        private readonly DateService                  $dateService,
        private readonly SerieViewingRepository       $serieViewingRepository,
        private readonly SettingsRepository           $settingsRepository,
        private readonly TranslatorInterface          $translator,
        private readonly UserRepository               $userRepository,
        private readonly UserYVideoRepository         $userYVideoRepository,
        private readonly YoutubeChannelRepository     $channelRepository,
        private readonly YoutubePlaylistRepository    $playlistRepository,
        private readonly YoutubeVideoRepository       $videoRepository,
        private readonly YoutubeVideoSeriesRepository $videoSeriesRepository,
        private readonly YoutubeVideoTagRepository    $videoTagRepository,
    )
    {
        $client = new Google_Client();
        $client->setApplicationName('mytvtime');
        $client->setScopes(['https://www.googleapis.com/auth/youtube.readonly',]);
        $client->setAuthConfig('../config/google/mytvtime-349019-001b2f815d02.json');
        $client->setAccessType('offline');

        $this->service_YouTube = new Google_Service_YouTube($client);
    }

    /**
     * @throws \Exception
     */
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

        $vids = $this->videoRepository->findAllWithChannelByDateSQL($user->getId(), $sort, $order);
        $videoCount = $this->getVideosCount($user);
        $totalRuntime = $this->getTotalRuntime($user);
        $firstView = $this->getFirstView($user);
        $time2Human = $this->getTime2human($totalRuntime/*, $request->getLocale()*/);
        $preview = $this->getPreview();

        $videoSeriesList = $this->userYVideoRepository->getUserVideoSeries($user->getId());
        usort($videoSeriesList, function ($a, $b) {
            return $a['title'] <=> $b['title'];
        });
        $videoSeries = new YoutubeVideoSeries();
        $videoSeries->setFormat('(.+)');
        $videoSeries->setRegex(true);
        $videoSeries->addMatch(new VideoSeriesMatch(false, '([0-9]+)', 'season', 1, 1, 'UNSIGNED'),);
        $videoSeries->addMatch(new VideoSeriesMatch(false, '([0-9]+)', 'episode', 1, 2, 'UNSIGNED'),);
        $videoSeries->addMatch(new VideoSeriesMatch(false, '([0-9]+)', 'part', 1, 3, 'UNSIGNED'),);
        $userSeries = $this->serieViewingRepository->userSeries($user->getId(), $user->getPreferredLanguage() ?? $request->getLocale());
        $userSeries = array_combine(array_column($userSeries, 'name'), array_column($userSeries, 'id'));

        $videoSeriesForm = $this->createForm(YoutubeVideoSeriesType::class, $videoSeries, [
            'allow_extra_fields' => true,
            'user_series' => $userSeries
        ]);

        return $this->render('youtube/index.html.twig', [
            'videos' => $this->getVideos($vids),
            'list' => $videoSeriesList,
            'form' => $videoSeriesForm->createView(),
            'videoCount' => $videoCount,
            'totalRuntime' => $totalRuntime,
            'firstView' => $firstView,
            'time2Human' => $time2Human,
            'settings' => $settings,
            'justAdded' => false,
            'preview' => $preview,
            'from' => 'youtube',
            'breadcrumb' => $this->youtubeBreadcrumb(),
        ]);
    }

    #[Route('/{_locale}/youtube/playlists', name: 'app_youtube_playlists', requirements: ['_locale' => 'fr|en|de|es'])]
    public function playlists(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();
        $playlists = $this->playlistRepository->findBy(['user' => $user], ['id' => 'DESC'], 20, 0);

        $playlistList = array_map(function ($p) {
            $playlistId = $p->getPlaylistId();
            $playlist = $this->getPlaylist($playlistId);
            $item = $playlist->getItems()[0];
            $snippet = $item->getSnippet();
            if ($snippet->getThumbnails()) {
                $thumbnails = $snippet->getThumbnails();
                $thumbnail = $thumbnails->getStandard() ?? $thumbnails->getHigh() ?? $thumbnails->getMedium() ?? $thumbnails->getDefault() ?? null;
            } else {
                $thumbnail = null;
            }
            $thumbnailUrl = $thumbnail ? $thumbnail->getUrl() : '/images/youtube/playlist_default.jpg';
            $playlistCount = $item->getContentDetails()->getItemCount();
            $newVideos = false;

            if ($p->getNumberOfVideos() != $playlistCount) {
                $p->setNumberOfVideos($playlistCount);
                $this->playlistRepository->save($p, true);
                $newVideos = true;
            }

            return [
                'id' => $p->getId(),
                'playlistId' => $p->getPlaylistId(),
                'snippet' => $item->getSnippet(),
                'thumbnailUrl' => $thumbnailUrl,
                'playListCount' => $playlistCount,
                'newVideos' => $newVideos,
            ];
        }, $playlists);

        usort($playlistList, function ($a, $b) {
            return $b['snippet']->getPublishedAt() <=> $a['snippet']->getPublishedAt();
        });

        dump(['playlists' => $playlistList]);

        return $this->render('youtube/playlists.html.twig', [
            'playlists' => $playlistList,
            'breadcrumb' => $this->youtubeBreadcrumb(),
        ]);
    }

    #[Route('/{_locale}/youtube/playlist/{id}', name: 'app_youtube_playlist', requirements: ['_locale' => 'fr|en|de|es'])]
    public function playlist($id): Response
    {
        $playlist = $this->playlistRepository->findOneBy(['id' => $id]);
        $playlistId = $playlist->getPlaylistId();

        $playlist = $this->getPlaylist($playlistId);
        $playlistItems = $this->getPlaylistItems($playlistId);

        $videos = array_map(function ($video) {
            $snippet = $video->getSnippet();
            $videoId = $snippet->getResourceId()->getVideoId();
            $videoItem = $this->getVideoSnippet($videoId)->getItems()[0];
            return [
                'videoId' => $videoId,
                'videoSnippet' => $videoItem->getSnippet(),
                'videoDuration' => $this->formatDuration($this->iso8601ToSeconds($videoItem->getContentDetails()->getDuration())),
                'channelId' => $snippet->getVideoOwnerChannelId(),
                'channelSnippet' => $this->getChannelSnippet($snippet->getVideoOwnerChannelId())->getItems()[0]->getSnippet(),
            ];
        }, $playlistItems->getItems());

        return $this->render('youtube/playlist.html.twig', [
            'playlist' => $playlist->getItems()[0],
            'playlistSnippet' => $playlist->getItems()[0]->getSnippet(),
            'playlistContentDetails' => $playlist->getItems()[0]->getContentDetails(),
            'playListCount' => $playlist->getItems()[0]->getContentDetails()->getItemCount(),
            'videoList' => $videos,
            'playlistItems' => $playlistItems,
            'breadcrumb' => $this->youtubeBreadcrumb(),
        ]);
    }

    #[Route('/{_locale}/youtube/more', name: 'app_youtube_more', requirements: ['_locale' => 'fr|en|de|es'])]
    public function moreVideos(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $userId = $request->query->get('id');
        $sort = $request->query->get('sort');
        $order = $request->query->get('order');
        $offset = $request->query->get('offset', 0);
        $limit = $request->query->get('limit', 20);
        /** @var YoutubeVideo [] $vids */
        $vids = $this->videoRepository->findAllWithChannelByDateSQL($userId, $sort, $order, $offset, $limit);

        return $this->json([
            'results' => $this->getVideos($vids),
        ]);
    }

    #[Route('/{_locale}/youtube/video/{id}', name: 'app_youtube_video', requirements: ['_locale' => 'fr|en|de|es'])]
    public function video(Request $request, YoutubeVideo $youtubeVideo): Response
    {
        $userAlreadyLinked = $request->query->get('user-already-linked');

        $tags = $this->videoTagRepository->findAllByLabel();
        $tagArr = array_map(function ($tag) {
            return ['id' => $tag['id'], 'label' => $tag['label'], 'selected' => false];
        }, $this->videoTagRepository->getTags());
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
//        dump($youtubeVideo->getYoutubeVideoComments()->first());

        return $this->render('youtube/video.html.twig', [
                'video' => $youtubeVideo,
                'description' => $description,
                'tagArr' => $tagArr,
                'other_tags' => array_diff($tags, $youtubeVideo->getTags()->toArray()),
                'userAlreadyLinked' => $userAlreadyLinked,
            ]
        );
    }

    #[Route('/{_locale}/youtube/search', name: 'app_youtube_search', requirements: ['_locale' => 'fr|en|de|es'])]
    public function search(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();
        $tagArr = array_map(function ($tag) {
            return ['id' => $tag['id'], 'label' => $tag['label'], 'selected' => false];
        }, $this->videoTagRepository->getTags());

        // /บรรยากาศรัก Love in The Air l EP\.*([0-9]+) \[([0-9])\/([0-9])].+/gm

        return $this->render('youtube/search.html.twig', [
            'tagArr' => $tagArr,
            'textArr' => [
                "modify" => $this->translator->trans("Modify"),
                "cancel" => $this->translator->trans("Cancel"),
                "apply" => $this->translator->trans("Apply"),
                "select_all" => $this->translator->trans("Select all"),
                "deselect_all" => $this->translator->trans("Deselect all"),
                "modify_tag_list" => $this->translator->trans("Modify tag list"),
                "add_video_to_tag" => $this->translator->trans("Add video to tag"),
                "add_video_to_tags" => $this->translator->trans("Add video to tags"),
                "set_visibility" => $this->translator->trans("Set visibility"),
                "delete" => $this->translator->trans("Delete selected videos"),
                "video" => $this->translator->trans("video"),
                "videos" => $this->translator->trans("videos"),
            ],
            'breadcrumb' => [
                ['name' => $this->translator->trans('My Youtube Videos'), 'url' => $this->generateUrl('app_youtube'), 'separator' => '●'],
                ['name' => $this->translator->trans('Youtube video search'), 'url' => $this->generateUrl('app_youtube_search')],
            ],
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
        $videos = [];
        $visibilities = [];
        $tagIds = explode(',', $list);
        $count = count($tagIds);

        // Toutes les vidéos
        if ($list) {
            $videoIds = $videoRepository->videosByTag($user->getId(), $list, $count, $method);
            $ids = array_column($videoIds, 'id');
            $videos = $videoRepository->findBy(['id' => $ids], ['publishedAt' => 'DESC']);
            $yVideos = $this->userYVideoRepository->getVisibilityFromList($user->getId(), $ids);
            foreach ($yVideos as $yVideo) {
                $visibilities[$yVideo->getVideo()->getId()] = $yVideo->isHidden();
            }
        }
        $videos = array_map(function ($video) use ($visibilities) {
            return [
                'id' => $video->getId(),
                'title' => $video->getTitle(),
                'thumbnailPath' => $video->getThumbnailHighPath(),
                'publishedAt' => $video->getPublishedAt(),
                'tags' => array_map(function ($tag) {
                    return [
                        'id' => $tag->getId(),
                        'label' => $tag->getLabel(),
                    ];
                }, $video->getTags()->toArray()),
                'contentDuration' => $this->formatDuration($video->getContentDuration()),
                'hidden' => $visibilities[$video->getId()],
                'channel' => [
                    'title' => $video->getChannel()->getTitle(),
                    'customUrl' => $video->getChannel()->getCustomUrl(),
                    'youtubeId' => $video->getChannel()->getYoutubeId(),
                    'thumbnailDefaultUrl' => $video->getChannel()->getThumbnailDefaultUrl(),
                ],
            ];
        }, $videos);

        return $this->json(
            [
                'block' => $this->render('blocks/youtube/_video_search.html.twig', [
                    'videos' => $videos,
                    'list' => $tagIds,
                    'type' => '',
                ]),
                'videoCount' => count($videos),
            ]);
    }

    #[Route('/{_locale}/youtube/video/add/tag/{id}/{tag}/{tagId}', name: 'app_youtube_video_add_tag', requirements: ['_locale' => 'fr|en|de|es'])]
    public function addTag(YoutubeVideo $youtubeVideo, $tag, $tagId): Response
    {
        $newTag = null;
        $tagAdded = false;
        if ($tagId) {
            $newTagId = $tagId;
            $newTag = $this->videoTagRepository->find($tagId);
            $videoTagIds = array_map(function ($tag) {
                return $tag->getId();
            }, $youtubeVideo->getTags()->toArray());
//            dump([
//                'tag' => $tag,
//                'tag id' => $tagId,
//                'video tag Ids' => $videoTagIds
//            ]);
            if (!in_array($newTagId, $videoTagIds)) {
                $youtubeVideo->addTag($newTag);
                $this->videoRepository->add($youtubeVideo, flush: true);
                $tagAdded = true;
            }
        } else {
            $newTagId = 0;

            $videoTags = array_map(function ($tag) {
                return $tag->getLabel();
            }, $youtubeVideo->getTags()->toArray());
//            dump([
//                'tag' => $tag,
//                'tag id' => $tagId,
//                'video tags' => $videoTags
//            ]);
            if (!in_array($tag, $videoTags)) {

                $newTag = $this->videoTagRepository->findOneBy(['label' => $tag]);

                if (!$newTag) {
                    $newTag = new YoutubeVideoTag();
                    $newTag->setLabel($tag);
                    $this->videoTagRepository->add($newTag, true);
                    $newTag = $this->videoTagRepository->findOneBy(['label' => $tag]);
                }
                $newTagId = $newTag->getId();
            }
            if ($newTag) {
                $youtubeVideo->addTag($newTag);
                $this->videoRepository->add($youtubeVideo, true);
                $newTagId = $newTag->getId();
                $tagAdded = true;
            }
        }

        return $this->json([
            "new_tag" => $tag,
            "new_tag_id" => $newTagId,
            "tag_added" => $tagAdded,
        ]);
    }

    #[Route('/{_locale}/youtube/video/remove/tag/{id}/{tag}', name: 'app_youtube_video_remove_tag', requirements: ['_locale' => 'fr|en|de|es'])]
    public function removeTag($tag, YoutubeVideo $youtubeVideo): Response
    {
        $videoTag = $this->videoTagRepository->find($tag);
        $youtubeVideo->removeTag($videoTag);
        $this->videoRepository->add($youtubeVideo, true);

        $videoTags = [];
        $tags = $youtubeVideo->getTags()->toArray();
        foreach ($tags as $t) {
            $videoTags[] = $t->getLabel();
        }

        return $this->json([
            'result' => true,
            'tags' => $videoTags,
        ]);
    }

    #[Route('/{_locale}/youtube/video/set/visibility', name: 'app_youtube_video_set_visibility', requirements: ['_locale' => 'fr|en|de|es'])]
    public function setVisibility(Request $request): Response
    {
        $ids = $request->query->get('ids');
        $visibility = $request->query->get('visibility');
        $hidden = $visibility == "hidden";
        $videos = $this->videoRepository->findBy(['id' => explode(',', $ids)]);
        $yVideo = $this->userYVideoRepository->findBy(['user' => $this->getUser(), 'video' => $videos]);

        foreach ($yVideo as $yv) {
            $yv->setHidden($hidden);
            $this->userYVideoRepository->save($yv);
        }
        $this->userYVideoRepository->flush();

        //$message = sprintf("%d video%s are now %s", count($videos), count($videos) > 1 ? "s" : "", $hidden ? "hidden" : "visible");
        $message = $this->translator->trans("count video%s% are now visibility", [
            'count' => count($videos),
            '%s%' => count($videos) > 1 ? "s" : "",
            'visibility' => $hidden ? "hidden" : "visible",
        ]);

//        dump([
//            'ids' => $ids,
//            'videos' => $videos,
//            'yVideo' => $yVideo,
//        ]);

        return $this->json([
            'success' => true,
            'message' => $message
        ]);
    }

    #[Route('/{_locale}/youtube/video/delete/{id}', name: 'app_youtube_video_delete', requirements: ['_locale' => 'fr|en|de|es'])]
    public function removeVideo(YoutubeVideo $video): JsonResponse
    {
        //    $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $yvideo = $this->userYVideoRepository->findOneBy(['user' => $user, 'video' => $video]);
        $user->removeYoutubeVideo($video);
        $user->removeUserYVideo($yvideo);
        $video->removeUserYVideo($yvideo);
        $this->userYVideoRepository->remove($yvideo, true);
        $this->userRepository->save($user, true);

        return $this->json([$video->getTitle()]);
    }

    #[Route('/{_locale}/youtube/video/list/delete/', name: 'app_youtube_video_list_delete', requirements: ['_locale' => 'fr|en|de|es'])]
    public function removeVideoList(Request $request): JsonResponse
    {
        //    $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $list = explode(',', $request->query->get('list'));
        $count = count($list);

        foreach ($list as $id) {
            $video = $this->videoRepository->find($id);
            $yvideo = $this->userYVideoRepository->findOneBy(['user' => $user, 'video' => $video]);
            $user->removeYoutubeVideo($video);
            $user->removeUserYVideo($yvideo);
            $video->removeUserYVideo($yvideo);
            $this->userYVideoRepository->remove($yvideo);
        }
        $this->userRepository->save($user, true);

        return $this->json([
            'success' => true,
            'message' => $count . " " . $this->translator->trans($count > 1 ? 'videos deleted!' : 'video deleted!'),
        ]);
    }

    #[Route('/{_locale}/youtube/add/video', name: 'app_youtube_add_video', requirements: ['_locale' => 'fr|en|de|es'], methods: ['GET'])]
    public function addVideo(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $locale = $request->getLocale();
        $providedLink = $request->query->get('link');
//        dump($providedLink);
        $justAdded = 0;
        $userAlreadyLinked = false;
        $status = "error";
        $message = "An error occurred";
        $subMessage = "";

        if (str_contains($providedLink, "shorts")) {
            // https://youtube.com/shorts/7qHLAXcEYUo?si=RduCPo0vSodq4syo
            // https://www.youtube.com/shorts/7qHLAXcEYUo?si=RduCPo0vSodq4syo
            if (str_contains($providedLink, "?si=")) {
                $providedLink = preg_replace("/https:\/\/(?:www\.)?youtube\.com\/shorts\/(.+)\?si=.+/", "$1", $providedLink);
            } else {
                // https://www.youtube.com/shorts/7KFxzeyse2g
                // https://youtube.com/shorts/7KFxzeyse2g
                $providedLink = preg_replace("/https:\/\/(?:www\.)?youtube\.com\/shorts\/(.+)/", "$1", $providedLink);
            }
        } elseif (str_contains($providedLink, "youtu.be")) {
            // https://youtu.be/7uhgBHGybEM?si=vpNycqOeAjk_sDck
            if (str_contains($providedLink, "?si=")) {
                $providedLink = preg_replace("/https:\/\/youtu\.be\/(.+)\?si=.+/", "$1", $providedLink);
            } else {
                // https://youtu.be/at9h35V8rtQ
                $providedLink = preg_replace("/https:\/\/youtu\.be\/(.+)/", "$1", $providedLink);
            }
        } elseif (str_contains($providedLink, 'watch')) {
            // https://www.youtube.com/watch?v=at9h35V8rtQ
            // https://www.youtube.com/watch?v=IzHJ7Jnj2LU&pp=wgIGCgQQAhgB
            $providedLink = preg_replace("/https:\/\/www\.youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)(?>&[a-z_-]+=.+)*/", "$1", $providedLink);
        } elseif (str_contains($providedLink, 'live')) {
            // https://www.youtube.com/live/vBWcWIim5Js?feature=share
            $providedLink = preg_replace("/https:\/\/www\.youtube\.com\/live\/([a-zA-Z0-9_-]+)(?>\?[a-zA-Z]+=.+)*/", "$1", $providedLink);
        }

        if (strlen($providedLink) == 11) {
//            dump($providedLink);
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
                if (array_key_exists('default', $thumbnails) && $thumbnails['default']['url']) $channel->setThumbnailDefaultUrl($thumbnails['default']['url']);
                if (array_key_exists('medium', $thumbnails) && $thumbnails['medium']['url']) $channel->setThumbnailMediumUrl($thumbnails['medium']['url']);
                if (array_key_exists('high', $thumbnails) && $thumbnails['high']['url']) $channel->setThumbnailHighUrl($thumbnails['high']['url']);
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
                $this->newYVideo($user, $newVideo);
                $message = $this->translator->trans("Video added!");

                $justAdded = $newVideo->getId();
            } else {
                // Si le lien a déjà été ajouté, on vérifie que l'utilisateur n'est pas déjà lié à la vidéo
                $userIds = array_map(function ($user) {
                    return $user->getId();
                }, $link->getUsers()->toArray());
                if (in_array($user->getId(), $userIds)) {
                    $userAlreadyLinked = true;
                    $message = $this->translator->trans("Video already added!");
                } // Sinon, on lie l'utilisateur à la vidéo
                else {
                    $link->addUser($user);
                    $this->videoRepository->add($link, true);
                    $this->newYVideo($user, $link);
                    $message = $this->translator->trans("Video added!");
                }
                $justAdded = $link->getId();
            }
            $status = "success";
            $subMessage = "<a href='/" . $locale . "/youtube/video/" . $justAdded . "'>🔗 ";
            $subMessage .= $this->translator->trans("Go to the video page to see it");
            $subMessage .= " 🔗</a>";
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
            $vids = $this->videoRepository->findAllWithChannelByDateSQL($user->getId(), $sort, $order);
            $videos = $this->getVideos($vids);
            $videoCount = $this->getVideosCount($user);
            $firstView = $this->getFirstView($user);
            $h1innerText = $videoCount . " " . $this->translator->trans('videos') . " " . $this->translator->trans('since') . " " . $this->dateService->formatDate($firstView, "Europe/Paris", $request->getLocale());
            $totalRuntime = $this->getTotalRuntime($user);
            $time2Human = $this->getTime2human($totalRuntime);

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

    #[Route('/{_locale}/youtube/add/playlist', name: 'app_youtube_add_playlist', requirements: ['_locale' => 'fr|en|de|es'], methods: ['GET'])]
    public function addPlaylist(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $locale = $request->getLocale();
        $providedLink = $request->query->get('link');
//        dump($providedLink);
        if (strlen($providedLink) > 34) {
            $providedLink = preg_replace("/https:\/\/www\.youtube\.com\/playlist\?list=(.+)/", "$1", $providedLink);
        }

        $ytPlaylist = $this->getPlaylist($providedLink);
        $title = $ytPlaylist->getItems()[0]->getSnippet()->getTitle();

        $playlist = $this->playlistRepository->findOneBy(['playlistId' => $providedLink]);
        if ($playlist) {
            $message = $title . ' - ' . $this->translator->trans("Playlist already added!");
            $status = "warning";
        } else {
            $playlist = new YoutubePlaylist();
            $playlist->setPlaylistId($providedLink);
            $playlist->setUser($user);
            $this->playlistRepository->save($playlist, true);
            $message = $title . ' - ' . $this->translator->trans("Playlist added!");
            $status = "success";
        }
        $this->addFlash($status, $message);

        return $this->json([
            'status' => $status,
            'message' => $message,
        ]);
    }

    #[Route('/{_locale}/youtube/video/series/{id}', name: 'app_youtube_video_series', requirements: ['_locale' => 'fr|en|de|es'])]
    public function loadVideoSeries(Request $request, YoutubeVideoSeries $series): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $videos = $this->videoSeriesRepository->findVideosBySeries($user->getId(), $series);
        $matches = $series->getMatches();
        $videos = array_map(function ($video) use ($matches, $user) {
            $v = [
                'id' => $video['id'],
                'link' => $video['link'],
                'title' => $video['title'],
                'thumbnailPath' => $video['thumbnailHighPath'],
                // 2024-01-25 -> 25/01/2024
                'publishedAt' => $this->dateService->newDateImmutable($video['publishedAt'], $user->getTimezone() ?? "Europe/Paris")->format('d/m/Y'),
//                'tags' => array_map(function ($tag) {
//                    return [
//                        'id' => $tag['id'],
//                        'label' => $tag['label'],
//                    ];
//                }, $video['tags']),
                'contentDuration' => $this->formatDuration($video['contentDuration']),
                'hidden' => $video['hidden'],
                'channel' => [
                    'title' => $video['channelTitle'],
                    'customUrl' => $video['channelCustomUrl'],
                    'youtubeId' => $video['channelYoutubeId'],
                    'thumbnailDefaultUrl' => $video['channelThumbnailDefaultUrl'],
                ],
            ];
            $v['matches'] = [];
            foreach ($matches as $match) {
                $v['matches'][] = ['name' => $match['name'], 'value' => $video[$match['name']]];
            }
            return $v;
        }, $videos);
        dump($videos);
        return $this->json(['videos' => $videos]);
    }

    #[Route('/{_locale}/youtube/preview/video/series', name: 'app_youtube_preview_video_series', requirements: ['_locale' => 'fr|en|de|es'], methods: ['GET'])]
    public function previewVideoSeries(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->query->get('data'), true);
        dump(['data' => $data, 'request query' => $request->query->get('data')]);
        $videos = $this->videoSeriesRepository->findVideosByFormat($user->getId(), $data['format'], $data['regex']);
        return $this->json(['videos' => $videos]);
    }

    public function youtubeBreadcrumb(): array
    {
        return [
            ['name' => $this->translator->trans('My Youtube Videos'), 'url' => $this->generateUrl('app_youtube'), 'separator' => '•'],
            ['name' => $this->translator->trans('My Youtube playlists'), 'url' => $this->generateUrl('app_youtube_playlists'), 'separator' => '•'],
            ['name' => $this->translator->trans('Youtube video search'), 'url' => $this->generateUrl('app_youtube_search')],
        ];
    }

    public function getVideos($vids): array
    {
        $videos = [];

        foreach ($vids as $vid) {
            $video = [];
            $video['id'] = $vid['id'];
            $video['thumbnailPath'] = $vid['thumbnailHighPath'];
            $video['title'] = $vid['title'];
            $video['contentDuration'] = $this->formatDuration($vid['contentDuration']);
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
            usort($video['tags'], function ($a, $b) {
                return $a['label'] <=> $b['label'];
            });
        }
        return $videos;
    }

    public function formatDuration(int $durationInSecond): string
    {
        $h = floor($durationInSecond / 3600);
        $m = floor(($durationInSecond % 3600) / 60);
        $s = $durationInSecond % 60;
        $duration = "";
        if ($h > 0) {
            $duration .= $h . ":";
        }
        if ($m < 10) {
            $m = "0" . $m;
        }
        $duration .= $m . ":";
        if ($s < 10) {
            $s = "0" . $s;
        }
        $duration .= $s;

        //dump(['durationInSecond' => $durationInSecond, 'h' => $h, 'm' => $m, 's' => $s, 'duration' => $duration]);

        return $duration;
    }

    public function newYVideo(User $user, YoutubeVideo $video): void
    {
        $ytvideo = new UserYVideo();
        $ytvideo->setUser($user);
        $ytvideo->setVideo($video);
        $ytvideo->setHidden(false);
        $this->userYVideoRepository->save($ytvideo, true);
    }

    public function getVideosCount(User $user): int
    {
//        return count($user->getYoutubeVideos());
        return $this->videoRepository->getUserYTVideosCount($user->getId()) ?? 0;
    }

    public function getTotalRuntime(User $user): int
    {
//        return $this->videoRepository->getUserYTVideosRuntime($user->getId()) ?? 0;
        return $this->videoRepository->getUserYTVideosDuration($user->getId()) ?? 0;
    }

    public function getFirstView($user): ?DateTimeImmutable
    {
        $firstAddedVideo = $this->videoRepository->firstAddedYTVideo($user->getId());
        if ($firstAddedVideo) {
            $last = $firstAddedVideo->getAddedAt();
        } else {
            $last = new DateTimeImmutable();
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

    private function getPlaylist($playlistId): PlaylistListResponse
    {
        return $this->service_YouTube->playlists->listPlaylists('contentDetails, snippet', ['id' => $playlistId]);
    }

    private function getPlaylistItems($playlistId): PlaylistItemListResponse
    {
        return $this->service_YouTube->playlistItems->listPlaylistItems('contentDetails,snippet', ['playlistId' => $playlistId, 'maxResults' => 50]);
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

    private function getTime2human($secondes): string
    {
        if ($secondes) {
            // convert total runtime ($total in secondes) in years, months, days, hours, minutes, secondes
            $now = new DateTimeImmutable();
            try {
                // past = now - total
                $past = $now->sub(new DateInterval('PT' . $secondes . 'S'));
            } catch (\Exception) {
                $past = $now;
            }
            // "5156720 secondes" → "5 156 720 secondes"
            $secondesStr = number_format($secondes, 0, '', ' ');

            $diff = $now->diff($past);
            // diff string with years, months, days, hours, minutes, seconds
            $runtimeString = $this->translator->trans('Time spent watching Youtube') . " : ";
            $runtimeString .= $secondesStr . ' ' . $this->translator->trans('seconds i.e.') . " ";
            $runtimeString .= $diff->days ? $diff->days . ' ' . ($diff->days > 1 ? $this->translator->trans('days') : $this->translator->trans('day')) . ($diff->y + $diff->m + $diff->d + $diff->h + $diff->i + $diff->s ? (', ' . $this->translator->trans('or') . ' ') : '') : '';
            $runtimeString .= $diff->y ? ($diff->y . ' ' . ($diff->y > 1 ? $this->translator->trans('years') : $this->translator->trans('year')) . ($diff->m + $diff->d + $diff->h + $diff->i + $diff->s ? ', ' : '')) : '';
            $runtimeString .= $diff->m ? ($diff->m . ' ' . ($diff->m > 1 ? $this->translator->trans('months') : $this->translator->trans('month')) . ($diff->d + $diff->h + $diff->i + $diff->s ? ', ' : '')) : '';
            $runtimeString .= $diff->d ? ($diff->d . ' ' . ($diff->d > 1 ? $this->translator->trans('days') : $this->translator->trans('day')) . ($diff->h + $diff->i + $diff->s ? ', ' : '')) : '';
            $runtimeString .= $diff->h ? ($diff->h . ' ' . ($diff->h > 1 ? $this->translator->trans('hours') : $this->translator->trans('hour')) . ($diff->i + $diff->s ? ', ' : '')) : '';
            $runtimeString .= $diff->i ? ($diff->i . ' ' . ($diff->i > 1 ? $this->translator->trans('minutes') : $this->translator->trans('minute')) . ($diff->s ? ', ' : '')) : '';
            $runtimeString .= $diff->s ? ($diff->s . ' ' . ($diff->s > 1 ? $this->translator->trans('seconds') : $this->translator->trans('second'))) : '';

//            dump($runtimeString);
        } else {
            $runtimeString = "";
        }
        return $runtimeString;
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
