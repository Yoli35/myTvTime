<?php

namespace App\Controller;

use App\Entity\Friend;
use App\Entity\MovieList;
use App\Entity\Settings;
use App\Entity\User;
use App\Form\Type\ChangePasswordType;
use App\Form\UserMovieSortType;
use App\Form\UserType;
use App\Repository\EpisodeViewingRepository;
use App\Repository\FriendRepository;
use App\Repository\MovieListRepository;
use App\Repository\SeasonViewingRepository;
use App\Repository\SerieViewingRepository;
use App\Repository\SettingsRepository;
use App\Repository\MovieRepository;
use App\Repository\UserRepository;
use App\Service\TMDBService;
use App\Service\FileUploader;
use App\Service\ImageConfiguration;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Bundle\TimeBundle\DateTimeFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserController extends AbstractController
{
    private string $json_header = '"json_format":"myTvTime","json_version":"1.0",';

    public function __construct(private readonly DateTimeFormatter        $dateTimeFormatter,
                                private readonly EpisodeViewingRepository $episodeViewingRepository,
                                private readonly FriendRepository         $friendRepository,
                                private readonly ImageConfiguration       $imageConfiguration,
                                private readonly LocaleSwitcher           $localeSwitcher,
                                private readonly MovieController          $movieController,
                                private readonly MovieListRepository      $movieListRepository,
                                private readonly MovieRepository          $movieRepository,
                                private readonly SeasonViewingRepository  $seasonViewingRepository,
                                private readonly SerieViewingRepository   $serieViewingRepository,
                                private readonly SettingsRepository       $settingsRepository,
                                private readonly TMDBService              $TMDBService,
                                private readonly TranslatorInterface      $translator,
                                private readonly UserRepository           $userRepository
    )
    {
    }

    #[Route('/{_locale}/user/search', name: 'app_personal_list', requirements: ['_locale' => 'fr|en|de|es'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $users = $this->userRepository->findAll();
        usort($users, function ($u1, $u2) {
            return strtolower($u1->getUsername()) <=> strtolower($u2->getUsername());
        });
        $friends = $this->getFriends($user);

        return $this->render('user/index.html.twig', [
            'user' => $user,
            'users' => $users,
            'friends' => $friends,
        ]);
    }

    public function getFriends($user): array
    {
        $friends = $this->friendRepository->findBy(['owner' => $user, 'approved' => true]);
        $friendsOf = $this->friendRepository->findBy(['recipient' => $user, 'approved' => true]);
        return array_merge($friends, $friendsOf);
    }

    /**
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/{_locale}/user/profile', name: 'app_personal_profile', requirements: ['_locale' => 'fr|en|de|es'])]
    public function profile(Request $request, EntityManagerInterface $entityManager, FileUploader $fileUploader, FriendRepository $friendRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
//        $this->logService->log($request, $this->getUser());
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $avatarFile */
            $avatarFile = $form->get('dropThumbnail')->getData();
            if ($avatarFile) {
                $avatarFileName = $fileUploader->upload($avatarFile, 'avatar');
                $fileToBeRemoved = $user->getAvatar();
                if ($fileToBeRemoved) {
                    $fileUploader->removeFile($fileToBeRemoved, 'avatar');
                }
                $user->setAvatar($avatarFileName);
            }
            /** @var UploadedFile $bannerFile */
            $bannerFile = $form->get('dropBanner')->getData();
            if ($bannerFile) {
                $bannerFileName = $fileUploader->upload($bannerFile, 'banner');
                $fileToBeRemoved = $user->getBanner();
                if ($fileToBeRemoved) {
                    $fileUploader->removeFile($fileToBeRemoved, 'banner');
                }
                $user->setBanner($bannerFileName);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $userPreferredLanguage = $user->getPreferredLanguage();
            $this->localeSwitcher->setLocale($userPreferredLanguage);

            return $this->redirectToRoute('app_personal_profile');
        }

        $friends = $this->getFriends($user);
        $friendRequests = $friendRepository->findBy(['owner' => $user, 'acceptedAt' => null, 'approved' => false]);

        $series = $this->serieViewingRepository->findBy(['user' => $user]);
        $seasons = $this->seasonViewingRepository->findBy(['serieViewing' => $series]);
        $episodes = $this->episodeViewingRepository->findBy(['season' => $seasons]);
        $episodesNotViewed = $this->episodeViewingRepository->findBy(['season' => $seasons, 'viewedAt' => null]);

        return $this->render('user/profile.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'friends' => $friends,
            'friendRequests' => $friendRequests,
            'episodes' => ['viewed' => count($episodes) - count($episodesNotViewed), 'total' => count($episodes)],
            'from' => 'profile',
            'locale' => $request->getLocale(),
        ]);
    }

    public function checkPendingFriendRequest(): void
    {
        $user = $this->getUser();
        if (!$user) {
            return;
        }
        $pendingRequests = $this->friendRepository->findBy(['recipient' => $user, 'acceptedAt' => null]);

        foreach ($pendingRequests as $request) {
            $openLetter = ['a', 'e', 'é', 'h', 'i', 'o', 'u'];
            $name = $request->getOwner()->getUsername() ?? $request->getOwner()->getEmail();
            $firstLetter = strtolower($name)[0];

            $this->addFlash('friendship', $this->translator->trans("You have a friend request from") . " "
                . (in_array($firstLetter, $openLetter) ? "d'" : "de ")
                . $name
                . ", " . $this->dateTimeFormatter->formatDiff($request->getCreatedAt()) . "."
                . "<button class='btn flash-accept' data-id='" . $request->getId() . "'>" . $this->translator->trans("Accept") . "</button>"
                . "<button class='btn flash-reject' data-id='" . $request->getId() . "'>" . $this->translator->trans("Reject") . "</button>");
        }
    }

    #[Route('/user/friendship/accept/{id}', name: 'app_personal_friendship_accept', methods: ['GET'])]
    public function acceptPendingFriendRequest($id): JsonResponse
    {
        $request = $this->friendRepository->find($id);
        $request->setAcceptedAt(new DateTimeImmutable());
        $request->setApproved(true);
        $this->friendRepository->save($request, true);

        return $this->json([]);
    }

    #[Route('/user/friendship/reject/{id}', name: 'app_personal_friendship_reject', methods: ['GET'])]
    public function rejectPendingFriendRequest($id): JsonResponse
    {
        $request = $this->friendRepository->find($id);
        $request->setAcceptedAt(new DateTimeImmutable());
        $request->setApproved(false);
        $this->friendRepository->save($request, true);

        return $this->json([]);
    }

    #[Route('/{_locale}/user/friends/', name: 'app_personal_friends', requirements: ['_locale' => 'fr|en|de|es'])]
    public function getFriendsAndRequests(FriendRepository $friendRepository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $friends = $friendRepository->findBy(['owner' => $user, 'approved' => true]);
        $friendRequests = $friendRepository->findBy(['owner' => $user, 'acceptedAt' => null, 'approved' => false]);

        return $this->json([
            'friends' => $this->getFriendsData($friends),
            'friendRequests' => $this->getFriendsData($friendRequests)],
        );
    }

    public function getFriendsData($friends): array
    {
        $friendsData = [];
        /** @var Friend $friend */
        foreach ($friends as $friend) {
            $friendData['id'] = $friend->getRecipient()->getId();
            $friendData['username'] = $friend->getRecipient()->getUsername();
            $friendData['avatar'] = $friend->getRecipient()->getAvatar();
            $friendsData[] = $friendData;
        }

        return $friendsData;
    }

    #[Route('/user/friend/new/{ownerId}/{recipientId}', name: 'app_personal_friend_new', methods: ['GET'])]
    public function newFriendRequest($ownerId, $recipientId): JsonResponse
    {
        $friend = new Friend();
        $friend->setOwner($this->userRepository->find($ownerId));
        $friend->setRecipient($this->userRepository->find($recipientId));
        $this->friendRepository->save($friend, true);

        return $this->json(['status' => 200]);
    }

    #[Route('/{_locale}/phpinfo', name: 'app_php_info', requirements: ['_locale' => 'fr|en|de|es'])]
    public function phpInfo(): Response
    {
        return $this->redirect('/phpinfo.php');
    }

    /**
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \Exception
     */
    #[Route('/{_locale}/user/movies', name: 'app_personal_movies', requirements: ['_locale' => 'fr|en|de|es'])]
    public function userMovies(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $sortSettings = $this->settingsRepository->findOneBy(['user' => $user, 'name' => 'user movie sort']);
        if (!$sortSettings) {
            $sortSettings = new Settings($user, 'user movie sort', ['sort' => 'id', 'order' => 'DESC']);
            $this->settingsRepository->save($sortSettings, true);
        }
        $sort = $sortSettings->getData()['sort'];
        $order = $sortSettings->getData()['order'];

        $form = $this->createForm(UserMovieSortType::class,
            ['sort' => $sort, 'order' => $order],
            ['method' => 'POST', 'csrf_protection' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sort = $form->get('sort')->getData();
            $order = $form->get('order')->getData();
            $sortSettings->setData(['sort' => $sort, 'order' => $order]);
            $this->settingsRepository->save($sortSettings, true);
        }

        $movies = $this->getUserMovies($user->getId(), 0, $sort, $order);

        $imageConfig = $this->imageConfiguration->getConfig();

        $items = $this->movieRepository->getUserMoviesRuntime($user->getId());
        $total = array_reduce($items, function ($carry, $item) {
            return $carry + $item['runtime'];
        });

        // convert total runtime ($total in minutes) in years, months, days, hours, minutes
        $now = new DateTimeImmutable();
        // past = now - total
        $past = $now->sub(new DateInterval('PT' . $total . 'M'));

        $diff = $now->diff($past);
        // diff string with years, months, days, hours, minutes
        $runtimeString = $diff->days ? $diff->days . ' ' . ($diff->days > 1 ? $this->translator->trans('days') : $this->translator->trans('day')) . ', ' . $this->translator->trans('or') . ' ' : '';
        $runtimeString .= $diff->y ? ($diff->y . ' ' . ($diff->y > 1 ? $this->translator->trans('years') : $this->translator->trans('year')) . ', ') : '';
        $runtimeString .= $diff->m ? ($diff->m . ' ' . ($diff->m > 1 ? $this->translator->trans('months') : $this->translator->trans('month')) . ', ') : '';
        $runtimeString .= $diff->d ? ($diff->d . ' ' . ($diff->d > 1 ? $this->translator->trans('days') : $this->translator->trans('day')) . ', ') : '';
        $runtimeString .= $diff->h ? ($diff->h . ' ' . ($diff->h > 1 ? $this->translator->trans('hours') : $this->translator->trans('hour')) . ', ') : '';
        $runtimeString .= $diff->i ? ($diff->i . ' ' . ($diff->i > 1 ? $this->translator->trans('minutes') : $this->translator->trans('minute'))) : '';

        $settings = $this->settingsRepository->findOneBy(['user' => $user, 'name' => 'pinned collection']);
        if (!$settings) {
            $settings = new Settings($user, 'pinned collection', [["pinned" => 0, "collection_id" => 0]]);
            $this->settingsRepository->save($settings, true);
        }

        return $this->render('user/movies.html.twig', [
            'discovers' => $movies,
            'form' => $form->createView(),
            'sort' => $sort,
            'order' => $order,
            'userMovies' => $this->movieController->getUserMovieIds(),
            'count' => count($items),
            'runtimeTotal' => $total,
            'runtimeString' => $runtimeString,
            'locale' => $request->getLocale(),
            'imageConfig' => $imageConfig,
            'user' => $user,
            'dRoute' => 'app_movie',
            'from' => 'app_personal_movies',
            'collections' => $this->movieListRepository->findBy(['user' => $user]),
            'settings' => $settings,
        ]);
    }

    #[Route('/user/movies/more', name: 'app_personal_movies_more')]
    public function userMoviesMore(Request $request, MovieRepository $movieRepository): Response
    {
        $imageConfig = $this->imageConfiguration->getConfig();
        $userMovieIds = $this->movieController->getUserMovieIds();
        $movies = $this->getUserMovies($request->query->get('id'), $request->query->get('offset'), $request->query->get('sort'), $request->query->get('order'));
        $blocks = array_map(function ($movie) use ($imageConfig, $userMovieIds) {
            return $this->renderView('blocks/movie/_discover.html.twig', [
                'discover' => $movie,
                'title' => $movie['title'],
                'poster' => $movie['poster_path'] ? $imageConfig['url'] . $imageConfig['poster_sizes'][3] . $movie['poster_path'] : null,
                'id' => $movie['movie_db_id'],
                'userMovies' => $userMovieIds,
                'from' => 'app_personal_movies',
                'more' => true,
            ]);
        }, $movies);
        return $this->json([
            'blocks' => $blocks,
        ]);
    }

    public function getUserMovies($userId, $offset, $sort, $order): array
    {
        $movieRepository = $this->movieRepository;
        $userMovies = $movieRepository->findUserMovies($userId, $offset, $sort, $order);

        $ids = array_map(function ($userMovie) {
            return $userMovie['id'];
        }, $userMovies);
        $movieLists = $this->movieRepository->userMovieGetMovieListsAll($ids, $userId);

        return array_map(function ($userMovie) use ($movieLists) {
            $movie = $userMovie;
            $movie['movie_lists'] = array_filter($movieLists, function ($movieList) use ($userMovie) {
                return ($userMovie['id'] === $movieList['movie_id']);
            });
            return $movie;
        }, $userMovies);
    }

    #[Route('/{_locale}/user/movie/list/{id}', name: 'app_personal_movie_list', requirements: ['_locale' => 'fr|en|de|es'])]
    public function getMovieList(Request $request, $id, MovieListRepository $movieListRepository, SettingsRepository $settingsRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var MovieList $collection */
        $collection = $movieListRepository->find($id);
        $movies = $this->moviesToArray($movieListRepository->getMoviesByReleaseDate($id, 'DESC'), $request->getLocale());

        $settings = $settingsRepository->findOneBy(['user' => $user, 'name' => 'pinned collection']);
        if (!$settings) {
            $settings = new Settings($user, 'pinned collection', [["pinned" => 1, "collection_id" => $id]]);
            $settings->setUser($user);
            $settings->setName('pinned collection');
        } else
            $settings->setData([["pinned" => 1, "collection_id" => $id]]);
        $settingsRepository->save($settings, true);

        return $this->json([
            'title' => $collection->getTitle(),
            'movies' => $movies
        ]);
    }

    #[Route('/user/collection/pin/status', name: 'app_personal_movie_list_pin_status', methods: ['GET'])]
    public function setCollectionPinStatus(Request $request, SettingsRepository $settingsRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $settings = $settingsRepository->findOneBy(['user' => $user, 'name' => 'pinned collection']);
        $data = $settings->getData();
        $data[0]['pinned'] = $request->query->get('pin');
        $settings->setData($data);
        $settingsRepository->save($settings, true);

        return $this->json([
        ]);
    }

    private function moviesToArray($movies, $locale): array
    {
        $tab = [];
        foreach ($movies as $movie) {
            $overview = $movie['overview_' . $locale];
            if ($overview === null) {
                $standing = $this->TMDBService->getMovie($movie['movie_db_id'], $locale);
                $movieDetail = json_decode($standing, true);
                $overview = $movieDetail['overview'];

                $m = $this->movieRepository->findOneBy(['id' => $movie['id']]);
                switch ($locale) {
                    case 'fr':
                        $m->setOverviewFr($overview);
                        break;
                    case 'en':
                        $m->setOverviewEn($overview);
                        break;
                    case 'de':
                        $m->setOverviewDe($overview);
                        break;
                    case 'es':
                        $m->setOverviewEs($overview);
                        break;
                }
                $this->movieRepository->save($m, true);
            }
            $movie['description'] = $overview;
            $tab[] = $movie;
        }

        return $tab;
    }

    #[Route('/{_locale}/user/movies/add', name: 'app_personnel_movie_add', requirements: ['_locale' => 'fr|en|de|es'])]
    public function add(Request $request, MovieController $movieController): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $movieId = $request->query->get('movie_db_id');
        $progressValue = $request->query->get('progress_value');
        $locale = $request->getLocale();

        $userMovie = $movieController->addMovie($user, $movieId, $locale);

        return $this->json(['title' => $userMovie->getTitle(), 'progress_value' => $progressValue]);
    }

    #[Route('/{_locale}/user/movies/export', name: 'app_personal_movies_export', requirements: ['_locale' => 'fr|en|de|es'])]
    public function export(Request $request, MovieRepository $userMovieRepository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $id = $user->getId();
        $movies = $this->movie2export($userMovieRepository->findAllUserMovies($id));
        $count = count($movies);
        $json = $this->formatJson('{' . $this->json_header . '"total_results":' . $count . ',"results":' . json_encode($movies) . '}');

        $filename = $this->saveFile($json, $user);
        $sample = $this->sample($movies, $request->getLocale());

        return $this->json([
            'count' => $count,
            'movies' => $movies,
            'json' => $json,
            'url' => '/movielist/',
            'file' => $filename,
            'sample' => $sample
        ]);
    }

    function movie2export($movies): array
    {
        $exports = [];
        $count = count($movies);
        for ($i = 0; $i < $count; $i++) {

            $movie = $movies[$i];
            $export['id'] = $movie['id'];
            $export['title'] = $movie['title'];
            $export['original_title'] = $movie['original_title'];
            $export['poster_path'] = $movie['poster_path'];
            $export['release_date'] = $movie['release_date'];
            $export['movie_db_id'] = $movie['movie_db_id'];
            $export['runtime'] = $movie['runtime'];

            $exports[] = $export;
        }
        return $exports;
    }

    #[Route('/{_locale}/movielist/updateSample', name: 'app_json_sample', requirements: ['_locale' => 'fr|en|de|es'])]
    public function updateSample(Request $request, MovieRepository $userMovieRepository, UserRepository $userRepository): JsonResponse
    {
        $userId = $request->get('user_id');
        $ids = $request->get('ids');
        $ids = json_decode($ids, true);
        $filename = $request->get('filename');

        $movies = $this->movie2export($userMovieRepository->getUserMovieFromIdList($userId, $ids));
        $sample = $this->sample($movies, $request->getLocale());

        $count = count($movies);
        $jsonMovies = json_encode($movies);
        $json = '{' . $this->json_header . '"total_results":' . $count . ',"results":' . $jsonMovies . '}';
        $json = $this->formatJson($json);
        $this->saveFile($json, $userRepository->find($userId), $filename);

        return $this->json([
            'sample' => $sample,
            'json' => $json,
        ]);
    }

    #[Route('/{_locale}/movielist/cleanup', name: 'app_json_cleanup', requirements: ['_locale' => 'fr|en|de|es'])]
    public function cleanup(Request $request): JsonResponse
    {
        $ret = unlink($this->getParameter('movie_list_directory') . '/' . $request->query->get('filename'));
        return $this->json([
            'result' => $ret,
        ]);
    }

    #[Route('/{_locale}/user/movies/ids', name: 'app_json_ids', requirements: ['_locale' => 'fr|en|de|es'])]
    public function jsonUserMovieIds(MovieController $movieController): JsonResponse
    {
        return $this->json([
            'movie_ids' => $movieController->getUserMovieIds(),
        ]);
    }

    private function saveFile(string $json, User $user, string $filename = null): string
    {
        $dir = $this->getParameter('movie_list_directory');
        if (!$filename) {
            $filename = $this->getFilename($user);
        }

        $file = fopen($dir . '/' . $filename, "w");
        fwrite($file, $json);
        fclose($file);

        return $filename;
    }

    private function getFilename($user): string
    {
        if ($user->getUsername()) {
            $filename = strtolower($user->getUsername());
        } else {
            $email = explode('@', $user->getEmail());
            $filename = $email[0];
        }
        $filename .= '_' . date("YmdHis") . '.json';
        return $filename;
    }

    private function sample($movies, $locale): string
    {
        $tab = '&nbsp;&nbsp;&nbsp;&nbsp;';

        $count = count($movies);

        $sample = $this->formatJson('{' . $this->json_header . '"total_results":' . $count . ',"results":', 0, $tab, '<br>');
        if ($count == 1) {
            $sample .= ' [<br>' . $tab . $tab;
            $sample .= $this->formatJson(json_encode($movies[0]), 2, $tab, '<br>');
            $sample .= '<br>' . $tab . ']';
        }
        if ($count == 2) {
            $sample .= ' ' . $this->formatJson(json_encode($movies), 1, $tab, '<br>');
        }
        if ($count > 2) {
            $sample .= ' [<br>' . $tab . $tab;
            $sample .= $this->formatJson(json_encode($movies[0]), 2, $tab, '<br>');

            $sample .= $this->andOther($locale, $count, $tab) . $tab . $tab;

            $sample .= $this->formatJson(json_encode($movies[$count - 1]), 2, $tab, '<br>');
            $sample .= '<br>' . $tab . ']';
        }
        $sample .= '<br>}';

        return preg_replace(
            ['#.("\w*"):\s("[a-zA-Z0-9\s\\\/\'.,:-]*"),#', '#.("\w*"):\s(\d*)#'],
            ['<span class="key">$1</span>: <span class="value-alpha">$2</span>,', '<span class="key">$1</span>: <span class="value-digit">$2</span>'],
            $sample);
    }

    private function andOther($locale, $count, $tab): string
    {
        return match ($locale) {
            'fr' => ',<br>' . $tab . $tab . '<i>[...] /* et ' . ($count - 2) . ' autre' . ($count > 3 ? 's' : '') . ' */</i><br>',
            'en' => ',<br>' . $tab . $tab . '<i>[...] /* and ' . ($count - 2) . ' more */</i><br>',
            'de' => ',<br>' . $tab . $tab . '<i>[...] /* und ' . ($count - 2) . ' ' . ($count > 3 ? 'andere' : 'weiterer') . ' */</i><br>',
            'es' => ',<br>' . $tab . $tab . '<i>[...] /* y otro' . ($count > 3 ? 's ' : ' ') . ($count - 2) . ' */</i><br>',
            default => '',
        };
    }

    /*
     * Manual formatter taken straight from https://github.com/umbrae/jsonlintdotcom
     *      -> https://github.com/umbrae/jsonlintdotcom/blob/master/c/js/jsl.format.js
     * From Javascript to Php
     * Provide json reformatting in a character-by-character approach, so that even
     * invalid JSON may be reformatted (to the best of its ability).
     */
    private function formatJson($json, $indentLevel = 0, $indentChars = null, $newLine = null): string
    {
        $il = strlen($json);
        $tab = $indentChars ?: "    ";
        $nl = $newLine ?: "\n";
        $newJson = "";
        $inString = false;

        for ($i = 0; $i < $il; $i++) {
            $currentChar = $json[$i];

            switch ($currentChar) {
                case '{':
                case '[':
                    if (!$inString) {
                        $newJson .= $currentChar . $nl . str_repeat($tab, $indentLevel + 1);
                        $indentLevel++;
                    } else {
                        $newJson .= $currentChar;
                    }
                    break;
                case '}':
                case ']':
                    if (!$inString) {
                        $indentLevel--;
                        $newJson .= $nl . str_repeat($tab, $indentLevel) . $currentChar;
                    } else {
                        $newJson .= $currentChar;
                    }
                    break;
                case ',':
                    if (!$inString) {
                        $newJson .= "," . $nl . str_repeat($tab, $indentLevel);
                    } else {
                        $newJson .= $currentChar;
                    }
                    break;
                case ':':
                    if (!$inString) {
                        $newJson .= ": ";
                    } else {
                        $newJson .= $currentChar;
                    }
                    break;
                case ' ':
                case "\n":
                case "\t":
                    if ($inString) {
                        $newJson .= $currentChar;
                    }
                    break;
                case '"':
                    if ($i > 0 && $json[$i - 1] !== '\\') {
                        $inString = !$inString;
                    }
                    $newJson .= $currentChar;
                    break;
                default:
                    $newJson .= $currentChar;
                    break;
            }
        }

        return $newJson;
    }

    #[Route('/still-connected/', name: 'app_user_connected')]
    public function stillConnected(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json(['connected' => ($user !== null)]);
    }

//    public function isFullyConnected(): void
//    {
//        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
//    }

    #[Route('/change-password', name: 'app_user_change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('newPassword')->getData()));
            $entityManager->flush();

            return $this->redirectToRoute('app_logout');
        }

        return $this->render('user/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
