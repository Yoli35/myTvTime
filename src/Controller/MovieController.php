<?php

namespace App\Controller;

//use App\Entity\ImageConfig;
//use App\Entity\Movie;
use App\Service;
use Doctrine\Persistence\ManagerRegistry;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use Google\Cloud\Translate\V3\TranslationServiceClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

class MovieController extends AbstractController
{
    static array $r = ["adult" => false, "backdrop_path" => "/ol85vXUrOnXpfVU6ZVFBoqTNGGt.jpg", "belongs_to_collection" => ["id" => 374509, "name" => "Godzilla (Original) - Saga", "poster_path" => "/1wgaCmaVswtrX69Y61MGy2cfxnA.jpg", "backdrop_path" => "/dx9YSup5zEOjxYwG4UkYBVAZIXo.jpg"], "budget" => 0, "genres" => [["id" => 28, "name" => "Action"], ["id" => 12, "name" => "Aventure"], ["id" => 878, "name" => "Science-Fiction"]], "homepage" => "", "id" => 15766, "imdb_id" => "tt0058544", "original_language" => "ja", "original_title" => "三大怪獣　地球最大の決戦", "overview" => "Un étrange astéroïde s'écrase sur Terre et l'équipe du professeur Murai part enquêter sur place. Pendant ce temps, une femme étrange prétendant être une Martienne annonce que, si l'humanité ne se repent pas, elle sera détruite. La Martienne prophétise le retour de Godzilla et Rodan, ainsi que la venue d'un monstre de l'espace appelé King Ghidorah. Les prophéties se réalisent et Ghidrah sème la terreur. Seule solution : Godzilla, Rodan et Mothra doivent accepter de coopérer pour vaincre le monstre de l'espace.", "popularity" => 12.358, "poster_path" => "/7aakWz9ENpzFYxV631pPEZgMi2s.jpg", "production_companies" => [["id" => 882, "logo_path" => "/fRSWWjquvzcHjACbtF53utZFIll.png", "name" => "Toho", "origin_country" => "JP"]], "production_countries" => [["iso_3166_1" => "JP", "name" => "Japan"]], "release_date" => "1964-12-20", "revenue" => 3237880, "runtime" => 92, "spoken_languages" => [["english_name" => "Japanese", "iso_639_1" => "ja", "name" => "日本語"]], "status" => "Released", "tagline" => "", "title" => "Ghidrah, le monstre à trois têtes", "video" => false, "vote_average" => 7.2, "vote_count" => 164];

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/{_locale}/movie/{id}', name: 'app_movie', requirements: ['_locale' => 'fr|en|de|es'])]
    public function index(Request $request, $id, ManagerRegistry $doctrine, Service\CallTmdbService $callTmdbService, HomeController $homeController): Response
    {
        // Clé d'API (v3 auth)
        //      f7e3c5fe794d565b471334c9c5ecaf96
        // Jeton d'accès en lecture à l'API (v4 auth)
        //      eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJmN2UzYzVmZTc5NGQ1NjViNDcxMzM0YzljNWVjYWY5NiIsInN1YiI6IjYyMDJiZjg2ZTM4YmQ4MDA5MWVjOWIzOSIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.9-8i4TOkKXtPZE_nkXk1ZvAlbDYgAdtcrCR6R8Dv3Wg

        $movieId = $id;
        $locale = $request->getLocale();
        $standing = $callTmdbService->getMovie($movieId, $locale);
        $movieDetail = json_decode($standing, true, 512, 0);
        $standing = $callTmdbService->getMovieRecommendations($movieId, $locale);
        $recommendations = json_decode($standing, true, 512, 0);
        $standing = $callTmdbService->getMovieCredits($movieId, $locale);
        $credits = json_decode($standing, true);
        $standing = $callTmdbService->getMovieReleaseDates($movieId);
        $releaseDates = json_decode($standing, true, 512, 0);
        $standing = $callTmdbService->getCountries();
        $countries = json_decode($standing, true, 512, 0);
        $imageConfig = $homeController->getImageConfig($doctrine);

        $cast = $credits['cast'];
        $crew = $credits['crew'];
//        $crew = usort($credits['crew'], 'memberCmp');
        $releaseDates = $this->getLocaleDates($releaseDates['results'], $countries, $locale);

        return $this->render('movie/index.html.twig', [
            'movie' => $movieDetail,
            'recommendations' => $recommendations['results'],
            'dates' => $releaseDates,
//          'countries' => $countries,
            'cast' => $cast,
            'crew' => $crew,
            'imageConfig' => $imageConfig,
            'locale' => $locale,
        ]);
    }

    #[Assert\Callback]
    public function memberCmp($a, $b): int
    {
        return strcmp($a['job'], $b['job']);
    }

    public function getLocaleDates($dates, $countries, $locale): array
    {
        $locales = ['fr' => ['BE', 'BF', 'BJ', 'CA', 'CD', 'CG', 'CH', 'CI', 'FR', 'GA', 'GN', 'LU', 'MC', 'ML', 'NE', 'SN', 'TG'], 'en' => ['AU', 'CA', 'GB', 'IE', 'MT', 'NZ', 'SG', 'US'], 'de' => ['AT', 'BE', 'CH', 'DE', 'LI', 'LU'], 'es' => ['AR', 'CL', 'CR', 'CU', 'ES', 'HN', 'NI', 'PR', 'SV', 'VE']];

        $types = [1 => 'Premiere', 2 => 'Theatrical (limited)', 3 => 'Theatrical', 4 => 'Digital', 5 => 'Physical', 6 => 'TV'];
        $localeDates = [];
        $c = [];

        for ($i = 0; $i < count($countries); $i++) {
            $c[$countries[$i]['iso_3166_1']] = $countries[$i]['english_name'];
        }

        for ($i = 0; $i < count($dates); $i++) {
            $d = $dates[$i];
            if (in_array($d['iso_3166_1'], $locales[$locale])) {
                $d['country'] = $c[$d['iso_3166_1']];
                for ($j = 0; $j < count($d['release_dates']); $j++) {
                    $d['release_dates'][$j]['type'] = $types[$d['release_dates'][$j]['type']];
                }
                $localeDates[] = $d;
            }
        }

        return $localeDates;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/{_locale}/movie/genre/{genres}/{page}', name: 'app_movies_by_genre', requirements: ['_locale' => 'fr|en|de|es'], defaults: ['page' => 1])]
    public function moviesByGenres(Request $request, $page, $genres, ManagerRegistry $doctrine, Service\CallTmdbService $callTmdbService, HomeController $homeController): Response
    {
        $locale = $request->getLocale();
        $standing = $callTmdbService->moviesByGenres($page, $genres, $locale);
        $discovers = json_decode($standing, true, 512, 0);
        $standing = $callTmdbService->getGenres($locale);
        $possibleGenres = json_decode($standing, true, 512, 0);

        $currentGenres = explode(',', $genres); // "Action,Adventure" => ['Action', 'Adventure']
        $imageConfig = $homeController->getImageConfig($doctrine);

        return $this->render('movie/genre.html.twig', [
            'discovers' => $discovers,
            'genres' => $genres,
            'possible_genres' => $possibleGenres,
            'current_genres' => $currentGenres,
            'imageConfig' => $imageConfig,
            ]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/{_locale}/movie/date/{date}/{page}', name: 'app_movies_by_date', requirements: ['_locale' => 'fr|en|de|es'], defaults: ['page' => 1])]
    public function moviesByDate(Request $request, $page, $date, ManagerRegistry $doctrine, Service\CallTmdbService $callTmdbService, HomeController $homeController): Response
    {
        $locale = $request->getLocale();
        $standing = $callTmdbService->moviesByDate($page, $date, $locale);
        $discovers = json_decode($standing, true, 512, 0);
        $imageConfig = $homeController->getImageConfig($doctrine);

        $now = intval(date("Y"));
        $years = [];
        for ($i = $now; $i >= 1874; $i--) {
            $years[] = $i;
        }

        return $this->render('movie/date.html.twig', [
            'discovers' => $discovers,
            'date' => $date,
            'years' => $years,
            'imageConfig' => $imageConfig,
            ]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/{_locale}/movie/search/{query}/{page}', name: 'app_movies_search', requirements: ['_locale' => 'fr|en|de|es'], defaults: ['page' => 1, 'query' => ''])]
    public function moviesSearch(Request $request, $page, $query, ManagerRegistry $doctrine, Service\CallTmdbService $callTmdbService, HomeController $homeController): Response
    {
        if ($query == 'Recherche par nom') $query ='';
        $locale = $request->getLocale();
        $discovers = ['results'=> [], 'page' => 0, 'total_pages' => 0, 'total_results' => 0];
        if ($query && strlen($query)) {
            $standing = $callTmdbService->moviesSearch($page, $query, $locale);
            $discovers = json_decode($standing, true, 512, 0);
        }

        $imageConfig = $homeController->getImageConfig($doctrine);

        return $this->render('movie/search.html.twig', [
            'query' => $query,
            'discovers' => $discovers,
            'imageConfig' => $imageConfig,
            ]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/{_locale}/profile', name: 'profile_infos', requirements: ['_locale' => 'fr|en|de|es'], methods: "GET|POST")]
    public function getPersonInfos(Request $request, Service\CallTmdbService $callTmdbService): JsonResponse
    {
        $id = $request->query->get('id');
        $locale = $request->query->get('locale');
        $standing = $callTmdbService->getPerson($id, $locale);
        $person = json_decode($standing, true);
        $department = ['fr' => ['Acting' => ['Acteur', 'Actrice', 'Acteur'], 'ADR Mixer' => ['Mixeur ADR (post-synchro)', 'Mixer ADR (post-synchro)', 'Mixer ADR (post-synchro)'], 'Art Direction' => ['Direction artistique', 'Direction artistique', 'Direction artistique'], 'Assistant Director' => ['Assistant réalisateur', 'Assistante réalisatrice', 'Assistant réalisateur'], 'Casting' => ['Distribution', 'Distribution', 'Distribution'], 'Costume Design' => ['Créateur de costumes', 'Créatrice de costumes', 'Créateur de costumes'], 'Costume Supervisor' => ['Superviseur des costumes', 'Superviseuse des costumes', 'Superviseur des costumes'], 'Director of Photography' => ['Directeur de la photographie', 'Directrice de la photographie', 'Directeur de la photographie'], 'Editing' => ['Édition', 'Édition', 'Édition'], 'Editor' => ['Éditeur', 'Éditrice', 'Éditeur'], 'Executive Producer' => ['Producteur délégué', 'Productrice déléguée', 'Producteur délégué'], 'Foley Artist' => ['Bruiteur', 'Bruiteuse', 'Bruiteur'], 'Line producer' => ['Producteur exécutif', 'Productrice exécutive', 'Producteur exécutif'], 'Makeup Artist' => ['Maquilleur', 'Maquilleuse', 'Maquilleur'], 'Music Supervisor' => ['Superviseur musical', 'Superviseuse musical', 'Superviseur musical'], 'Original Music Composer' => ['Compositeur de musique originale', 'Compositrice de musique originale', 'Compositeur de musique originale'], 'Producer' => ['Producteur', 'Productrice', 'Producteur'], 'Production Design' => ['Conception de production', 'Conception de production', 'Conception de production'], 'Screenplay' => ['Scénario', 'Scénario', 'Scénario'], 'Screenstory' => ['Scénario', 'Scénario', 'Scénario'], 'Set Decoration' => ['Décorateur', 'Décoratrice', 'Décorateur'], 'Set Designer' => ['Scénographe', 'Scénographe', 'Scénographe'], 'Set Dresser' => ['Habilleur', 'Habilleuse', 'Habilleur'], 'Set Manager' => ['Régisseur', 'Régisseuse', 'Régisseur'], 'Sound' => ['Son', 'Son', 'Son'], 'Sound Effects Editor' => ['Éditeur d\'effets sonores', 'Éditeur d\'effets sonores', 'Éditeur d\'effets sonores'], 'Sound Mixer' => ['Ingénieur du son', 'Ingénieure du son', 'Ingénieur du son'], 'Sound Re-Recording Mixer' => ['Mixeur son', 'Mixeuse son', 'Mixeur son'], 'Still Photographer' => ['Photographe de plateau', 'Photographe de plateau', 'Photographe de plateau'], 'Stunt' => ['Cascadeur', 'Cascadeuse', 'Cascadeur'], 'Supervising Art Director' => ['Directeur artistique superviseur', 'Directrice artistique superviseure', 'Directeur artistique superviseur'], 'Supervising Sound Editor' => ['Supervision du montage son', 'Supervision du montage son', 'Supervision du montage son'], 'VFX Artist' => ['Artiste d\'effets visuels', 'Artiste d\'effets visuels', 'Artiste d\'effets visuels'], 'Visual Effects' => ['Effets visuels', 'Effets visuels', 'Effets visuels'], 'Visual Effects Producer' => ['Producteur Effets visuels', 'Producteur Effets visuels', 'Producteur Effets visuels'], 'Visual Effects Supervisor' => ['Superviseur Effets visuels', 'Superviseuse Effets visuels', 'Superviseur Effets visuels'], 'Writing' => ['Écriture', 'Écriture', 'Écriture']], 'de' => ['Acting' => ['Schauspieler', 'Schauspielerin', 'Schauspieler'], 'ADR Mixer' => ['ADR-Mix (post-synchro)', 'ADR-Mix (post-synchro)', 'ADR-Mix (post-synchro)'], 'Art Direction' => ['Künstlerische Leitung ', 'Künstlerische Leitung ', 'Künstlerische Leitung '], 'Assistant Director' => ['Regieassistent ', 'Regieassistentin ', 'Regieassistent '], 'Casting' => ['Casting', 'Casting', 'Casting'], 'Costume Design' => ['Kostümbildner', 'Kostümbildnerin', 'Kostümbildner'], 'Costume Supervisor' => ['Supervisor für Kostüme', 'Supervisorin für Kostüme', 'Supervisor für Kostüme'], 'Director of Photography' => ['Direktor für Fotografie', 'Direktorin für Fotografie', 'Direktor für Fotografie'], 'Editing' => ['', '', ''], 'Editor' => ['', '', ''], 'Executive Producer' => ['', '', ''], 'Foley Artist' => ['', '', ''], 'Makeup Artist' => ['', '', ''], 'Music Supervisor' => ['', '', ''], 'Original Music Composer' => ['', '', ''], 'Producer' => ['', '', ''], 'Production Design' => ['', '', ''], 'Screenplay' => ['', '', ''], 'Screenstory' => ['', '', ''], 'Set Decoration' => ['', '', ''], 'Set Designer' => ['', '', ''], 'Set Dresser' => ['', '', ''], 'Sound Effects Editor' => ['', '', ''], 'Sound Mixer' => ['', '', ''], 'Sound Re-Recording Mixer' => ['', '', ''], 'Still Photographer' => ['', '', ''], 'Supervising Art Director' => ['', '', ''], 'Supervising Sound Editor' => ['', '', ''], 'VFX Artist' => ['', '', ''], 'Visual Effects' => ['', '', ''], 'Visual Effects Producer' => ['', '', ''], 'Visual Effects Supervisor' => ['', '', ''],], 'es' => ['Acting' => ['Actor', 'Actress', 'Actor'], 'ADR Mixer' => ['Mezcla ADR (post-sincronización)', 'Mezcla ADR (post-sincronización)', 'Mezcla ADR (post-sincronización)'], 'Art Direction' => ['Dirección artística', 'Dirección artística', 'Dirección artística'], 'Assistant Director' => ['Asistente de dirección', 'Asistente de dirección', 'Asistente de dirección'], 'Casting' => ['Casting', 'Casting', 'Casting'], 'Costume Design' => ['Diseñador de vestuario', 'Diseñadora de vestuario', 'Diseñador de vestuario'], 'Costume Supervisor' => ['Supervisor de vestuario', 'Supervisora de vestuario', 'Supervisor de vestuario'], 'Director of Photography' => ['Director de fotografía', 'Director de fotografía', 'Director de fotografía'], 'Editing' => ['', '', ''], 'Editor' => ['', '', ''], 'Executive Producer' => ['', '', ''], 'Foley Artist' => ['', '', ''], 'Makeup Artist' => ['', '', ''], 'Music Supervisor' => ['', '', ''], 'Original Music Composer' => ['', '', ''], 'Producer' => ['', '', ''], 'Production Design' => ['', '', ''], 'Screenplay' => ['', '', ''], 'Screenstory' => ['', '', ''], 'Set Decoration' => ['', '', ''], 'Set Designer' => ['', '', ''], 'Set Dresser' => ['', '', ''], 'Sound Effects Editor' => ['', '', ''], 'Sound Mixer' => ['', '', ''], 'Sound Re-Recording Mixer' => ['', '', ''], 'Still Photographer' => ['', '', ''], 'Supervising Art Director' => ['', '', ''], 'Supervising Sound Editor' => ['', '', ''], 'VFX Artist' => ['', '', ''], 'Visual Effects' => ['', '', ''], 'Visual Effects Producer' => ['', '', ''], 'Visual Effects Supervisor' => ['', '', ''],],];

        return $this->json([
            'success' => true,
            'person' => $person,
            'department' => $department,
            'locale' => $locale,
            ]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ApiException
     * @throws ValidationException
     */
    #[Route('/{_locale}/imdb', name: 'imdb_infos', requirements: ['_locale' => 'fr|en|de|es'], methods: "GET|POST")]
    public function getPersonInfosOnIMDB(Request $request, Service\CallImdbService $callImdbService, TranslatorInterface $translator): JsonResponse
    {
        $name = $request->query->get('name');
        $standing = $callImdbService->searchName($name);
        $search = json_decode($standing, true);
        $result = $search['results'][0];
        $namePart = explode(" ", $name);

        if (!strcmp($result['title'], $name) || !strcmp($result['title'], $namePart[1] . " " . $namePart[0])) {
            $locale = $request->query->get('locale');
            $standing = $callImdbService->getPerson($result['id'], $locale);
            $person = json_decode($standing, true);
            $summary = $translator->trans($person['summary']);

            if ($locale !== 'en') {
                $config = ['credentials' => ["type" => "service_account", "project_id" => "mytvtime-349019", "private_key_id" => "001b2f815d020608bcf09f3278e808fa0c52a6b7", "private_key" => "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCyXis5yVetkdre\niZqN7yrzy0kIydA4G/g9Wyh+b6VpOEz2kFjG5tIcibsEh8TP1mHPn0N95zovYv9S\n4bR3xfz1TJq9rxiVDgBLiKqQj/r8quLup0Uows3NohxtommAx2MybUrd+hngbzFD\nom+ELBty1TW3bZOz5kTpwdLKfrS5BgPdR01bJCPFn4STcc9gxGJAKXHDBF1+nbTk\n6c7vWBvKJUR8mxY20FfNEVeCoNAfBfyne2ZqOO5G8LOGFoYSjx0hNib84eB1cGCg\ncV+Ue/cpxyd3mn2v4maScs0CdAXhEAUKlQLMgCrnBUInu7lb4rfO4WJCvcg4Lr1+\nCIp62cK1AgMBAAECggEABj1GDNDuuLsb6WHt3p4ppfqL9Ps+ReAwmFDag0W7hwk5\no/xbpqWHXwkwWhG3wD9zD3S2Qy61+ddgMBGGIxRxa1FBLnZ0CS7CsuG2ebUXpgQC\nSS/fuvPJiDJuBSXDxAX1gduR3V70zcWF9yQ0+24hjaxIo0B5hLb+3SBzE7NH9hrh\nTmA3kyenwIrrzu/n+sM/edQZIj0r3Irhg3oO48UJS7HrDWwssTfHhZoE89oMNZBy\nqthl3nYkjcHtC1PuYLeg+gLyoVucoGE71zqACvbD9RjCHnlUHbgO6Q+DnKjwmLRj\nSC6qwUp/ZjxLFIYGuOjKj1nqQaU+RYdcJ/zJmIbGFwKBgQDbD7w8QWYUCo31uXE+\nf5tR8/UBV7bphcBuMGb2EJEA0ifT5Se0ePnT8PW0EtZf/TYEG0wxKIcFZVIVaeFp\nbh1fpj13rwwo+EN1n5EAbMK+A+AiLeJR8shwKirooddEuVVKm7hDH/Q2i4IoaZCO\naxBurr6WwX3HycEcY+RDQYcwRwKBgQDQcc4SyjVyCGExvGLX8ArxPsvdpixxmmvt\nKbL9vXP1+wl47b6+xf4vuxr2XWvZFoQtjrCNUqrRKbQxF+k1zpkxfP1qM/TfvxNS\nPsIw32I+BWH/2JrnVh+kpxpP5Quc2MgS+nQfUqAW5JBMSxUV9EgZbVuCsLWKwhvs\nU++6mSYPIwKBgCRiP6xuXEr12dA3RbTQsvZwo3/elrXAjk5+4Yr7A2p0fUL3a5nR\nAgWOnvCStGJrBv61nfkINyzRQEnoNRUywdQyI0FupIFlgqbVotrENbAjqqVio5Vi\n0qG2jzvmLX/vnFfw9zDG7OPmVe7qYaUV6TvI8ETPzFlTjCxv9uioyJBfAoGAHn7H\n20/iCdDYB2K8Q0NHFoxNXxwUnHovF/9lxGGXOYGEnUCLC3YD/g+tniWExbnZlKCv\ni71waDFlv1j0MX8MQoU6vfLj/GgD96Be4K+Nu+0lrTyPTRD4iCo6Wz3zOPsuKjii\nDIMWEMNXqRHC//dBJRcusCwSIz7KvwR4qiAFxWkCgYEAsCRAxJrGJMrh41nGLink\nGBP8NsWxysJi2ObeKmWWGnu7Gr7Vc7TYNVAsYBIoRHEhhUrSxz+VgJnVDPW7aC5p\nncR3Q7gHMd91wRJD3muP0ocDkPrZXiqdK9oiEbhU33KJGR9OD1Dvpcxvvhhyfgi5\npB++X6dH68Y7UIC8hM2i7GY=\n-----END PRIVATE KEY-----\n", "client_email" => "translate@mytvtime-349019.iam.gserviceaccount.com", "client_id" => "106684530697242476361", "auth_uri" => "https://accounts.google.com/o/oauth2/auth", "token_uri" => "https://oauth2.googleapis.com/token", "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs", "client_x509_cert_url" => "https://www.googleapis.com/robot/v1/metadata/x509/translate%40mytvtime-349019.iam.gserviceaccount.com"]];

                $person['translated'] = '';
                $translationClient = new TranslationServiceClient($config);
                $content = [$summary];
                $targetLanguage = $locale;
                $response = $translationClient->translateText($content, $targetLanguage, TranslationServiceClient::locationName('mytvtime-349019', 'global'));

                foreach ($response->getTranslations() as $key => $translation) {
                    $person['translated'] .= $translation->getTranslatedText();
                }
            } else {
                $person['translated'] = '';
            }
            $success = true;
        } else {
            $person = null;
            $success = false;
        }

        return $this->json([
            'success' => $success,
            'person' => $person,
            ]);
    }
}
