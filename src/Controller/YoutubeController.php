<?php

namespace App\Controller;

use App\Entity\YoutubeVideo;
use App\Entity\YoutubeVideoTag;
use App\Form\YoutubeVideoType;
use App\Repository\YoutubeVideoRepository;
use App\Repository\YoutubeVideoTagRepository;
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
    public function moreVideos(Request $request, YoutubeVideoRepository $youtubeVideoRepository): Response
    {
        /** @var YoutubeVideo [] $vids */
        $vids = $youtubeVideoRepository->findAllByDate($request->query->get('id'), $request->query->get('offset'));
        $videos = [];

        foreach ($vids as $vid) {

            $video = [];
            $video['id'] = $vid->getId();
            $video['thumbnailMediumPath'] = $vid->getThumbnailMediumPath();
            $video['title'] = $vid->getTitle();
            $video['contentDuration'] = $vid->getContentDuration();
            $video['publishedAt'] = $vid->getPublishedAt();
            $video['channel'] = [];
            $video['channel']['title'] = $vid->getChannel()->getTitle();
            $video['channel']['customUrl'] = $vid->getChannel()->getCustomUrl();
            $video['channel']['youtubeId'] = $vid->getChannel()->getYoutubeId();
            $video['channel']['thumbnailDefaultUrl'] = $vid->getChannel()->getThumbnailDefaultUrl();

            $video['tags'] = [];
            $tags = $vid->getTags();
            foreach ($tags as $tag) {
                $serializedTag = [];
                $serializedTag['id'] = $tag->getId();
                $serializedTag['label'] = $tag->getLabel();
                $video['tags'][] = $serializedTag;
            }

            $videos[] = $video;
        }

        return $this->json([
            'results' => $videos,
        ]);
    }

    #[Route('/{_locale}/youtube/video/{id}', name: 'app_youtube_video', requirements: ['_locale' => 'fr|en|de|es'])]
    public function video(YoutubeVideoTagRepository $repository, YoutubeVideo $youtubeVideo): Response
    {
        $tags = $repository->findAllByLabel();
        $description = nl2br($youtubeVideo->getDescription());
        $description = preg_replace('@([^>"])(https?://[a-z0-9\./+,%\@\?=#_-]+)@i', '$1<a href="$2" target="_blank">$2</a>', $description);
        $description = preg_replace('#([A-Za-z_-][A-Za-z0-9\._-]*@[a-z0-9_-]+(\.[a-z0-9_-]+)+)#','<a href="mailto:$1">$1</a>', $description);

        return $this->render('youtube/video.html.twig', [
                'video' => $youtubeVideo,
                'description' => $description,
                'other_tags' => array_diff($tags, $youtubeVideo->getTags()->toArray()),
            ]
        );
    }

    #[Route('/{_locale}/youtube/video/add/tag/{id}/{tag}', name: 'app_youtube_video_add_tag', requirements: ['_locale' => 'fr|en|de|es'])]
    public function addTag($tag, YoutubeVideo $youtubeVideo, YoutubeVideoTagRepository $tagRepository, YoutubeVideoRepository $videoRepository): Response
    {
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
            }
            else {
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
    public function removeTag($id, $tag, YoutubeVideo $youtubeVideo, YoutubeVideoRepository $videoRepository, YoutubeVideoTagRepository $tagRepository): Response
    {
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

    private function urlsToLinks($text): array|string|null
    {
        return preg_replace(
            array(
            '/(?(?=<a[^>]*>.+<\/a>) (?:<a[^>]*>.+<\/a>) | ([^="\']?)((?:https?|ftp|bf2|):\/\/[^<> \n\r]+) )/ix',
            '/<a([^>]*)target="?[^"\']+"?/i',
            '/<a([^>]+)>/i',
            '/(^|\s)(www.[^<> \n\r]+)/ix',
            '/(([_A-Za-z0-9-]+)(\\.[_A-Za-z0-9-]+)*@([A-Za-z0-9-]+) (\\.[A-Za-z0-9-]+)*)/ix'
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
