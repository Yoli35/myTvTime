<?php

namespace App\Components;

use App\Entity\User;
use App\Entity\YoutubeChannel;
use App\Entity\YoutubeVideo;
use App\Repository\YoutubeChannelRepository;
use App\Repository\YoutubeVideoRepository;
use DateInterval;
//use Google\ApiCore\ValidationException;
//use Google\Cloud\Translate\V3\TranslationServiceClient;
use Google\Exception;
use Google\Service\YouTube\ChannelListResponse;
use Google\Service\YouTube\VideoListResponse;
use Google_Client;
use Google_Service_YouTube;
use JetBrains\PhpStorm\ArrayShape;
//use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Security;
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
    public string $locale = '';
    #[LiveProp]
    public string $preview = '';

//    private TranslationServiceClient $translationClient;
//    private TranslatorInterface $translator;
    private User $user;
    private YoutubeVideoRepository $repoYTV;
    private YoutubeChannelRepository $repoYTC;
    private Google_Service_YouTube $service_YouTube;

    /**
     * @throws Exception
     */
    public function __construct(Security $security, YoutubeVideoRepository $repoYTV, YoutubeChannelRepository $repoYTC)
    {
        $this->repoYTV = $repoYTV;
        $this->repoYTC = $repoYTC;

        $this->user = $security->getUser();

        $client = new Google_Client();
        $client->setApplicationName('mytvtime');
        $client->setScopes(['https://www.googleapis.com/auth/youtube.readonly',]);
        $client->setAuthConfig('../config/google/mytvtime-349019-001b2f815d02.json');
        $client->setAccessType('offline');

        $this->service_YouTube = new Google_Service_YouTube($client);
    }

    public function mount($preview, $locale): void
    {
        $this->locale = $locale;
        $this->preview = $preview;
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    #[ArrayShape(['videos' => "\App\Entity\YoutubeVideo[]", 'count' => "int", 'seconds2human' => "string"])]
    public function video_results(): array
    {

        // https://www.youtube.com/watch?v=at9h35V8rtQ
        // https://youtu.be/at9h35V8rtQ
        $thisLink = $this->link;

        if (in_array(strlen($thisLink), [28, 43])) {
            $thisLink = substr($thisLink, -11);
        }

        if (strlen($thisLink) == 11) {

            $link = $this->repoYTV->findBy(['link' => $thisLink, 'userId' => $this->user->getId()]);

            // Si le lien n'a pas déjà été ajouté
            if ($link == null) {

                $videoListResponse = $this->getVideoSnippet($thisLink);
                $items = $videoListResponse->getItems();
                $item = $items[0];
                $snippet = $item['snippet'];

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
                $new->setUserId($this->user->getId());
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
            }
        }
        /** @var YoutubeVideo[] $videos */
        $videos = $this->repoYTV->findAllByDate($this->user->getId());
        $total = 0;
        foreach ($videos as $video) {
            $total += $video->getContentDuration();
        }

        return ['videos' => $videos, 'count' => count($videos), 'seconds2human' => $this->seconds2human($total)];
    }

    public function getLinkPreview(): array|null
    {
        $videoListResponse = $this->getVideoSnippet($this->preview);
        $items = $videoListResponse->getItems();
        $item = $items[0];
        $snippet = $item['snippet'];
        $thumbnails = (array)$snippet['thumbnails'];

        if (array_key_exists('medium', $thumbnails))
            return ['url'=>$thumbnails['medium']['url'], 'title'=>$snippet['title']];

        if (array_key_exists('default', $thumbnails))
            return ['url'=>$thumbnails['default']['url'], 'title'=>$snippet['title']];

        return null;
    }

    private function getVideoSnippet($videoId): VideoListResponse
    {
        return $this->service_YouTube->videos->listVideos('contentDetails, snippet', ['id' => $videoId]);
    }

    private function getChannellSnippet($channelId): ChannelListResponse
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

    private function seconds2human($ss): string
    {
        $l = $this->locale;
        $words = ["timeSpent1" => ["en" => "Time spent watching Youtube", "fr" => "Temps passé devant youtube", "es" => "Tiempo dedicado a ver Youtube", "de" => "Zeit, die Sie mit Youtube verbracht haben"], "timeSpent2" => ["en" => "secondes i.e.", "fr" => "secondes c.à.d.", "es" => "segundos, es decir,", "de" => "Sekunden d.h."], "mounth" => ["en" => "mounth", "fr" => "mois", "es" => "mes", "de" => "Monat"], "mounths" => ["en" => "mounths", "fr" => "mois", "es" => "meses", "de" => "Monate"], "day" => ["en" => "day", "fr" => "jour", "es" => "día", "de" => "Tag"], "days" => ["en" => "days", "fr" => "jours", "es" => "días", "de" => "Tage"], "hour" => ["en" => "hour", "fr" => "heure", "es" => "hora", "de" => "Stunde"], "hours" => ["en" => "hours", "fr" => "heures", "es" => "horas", "de" => "Stunden"], "minute" => ["en" => "minute", "fr" => "minute", "es" => "minuto", "de" => "Minute"], "minutes" => ["en" => "minutes", "fr" => "minutes", "es" => "minutos", "de" => "Minuten"], "seconde" => ["en" => "seconde", "fr" => "seconde", "es" => "segundo", "de" => "Sekunde"], "secondes" => ["en" => "secondes", "fr" => "secondes", "es" => "segundos", "de" => "Sekunden"], "and" => ["en" => "and", "fr" => "et", "es" => "y", "de" => "und"],];
        $s = $ss % 60;
        $m = intval(floor(($ss % 3600) / 60));
        $h = intval(floor(($ss % 86400) / 3600));
        $d = intval(floor(($ss % 2592000) / 86400));
        $M = intval(floor($ss / 2592000));

        $result = $words['timeSpent1'][$l] . " : " . $ss . " " . $words['timeSpent2'][$l] . " ";
        if ($M) {
            $result .= $M . " " . ($M > 1 ? $words['mounths'][$l] : $words['mounth'][$l]);
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
//
//    /**
//     * @throws ValidationException
//     * @throws ApiException
//     */
//    private function translate($phrase): string
//    {
//        $toBeTranslate = $this->translator->trans($phrase);
//        $translated = '';
//
//        if ($this->locale !== 'en') {
//
//            $content = [$toBeTranslate];
//            $targetLanguage = $this->locale;
//            $response = $this->translationClient->translateText($content, $targetLanguage, TranslationServiceClient::locationName('mytvtime-349019', 'global'));
//            foreach ($response->getTranslations() as $key => $translation) {
//                $translated .= $translation->getTranslatedText();
//            }
//        }
//        return strlen($translated) ? $translated : $phrase;
//    }
}