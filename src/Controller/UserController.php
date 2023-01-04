<?php

namespace App\Controller;

use App\Entity\Friend;
use App\Entity\MovieCollection;
use App\Entity\Settings;
use App\Entity\User;
use App\Form\Type\ChangePasswordType;
use App\Form\UserType;
use App\Repository\FriendRepository;
use App\Repository\MovieCollectionRepository;
use App\Repository\SettingsRepository;
use App\Repository\UserMovieRepository;
use App\Repository\UserRepository;
use App\Service\BetaSeriesService;
use App\Service\LogService;
use App\Service\TMDBService;
use App\Service\FileUploader;
use App\Service\ImageConfiguration;
use DateTime;
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
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserController extends AbstractController
{
    private string $json_header = '"json_format":"myTvTime","json_version":"1.0",';

    public function __construct(private readonly LocaleSwitcher      $localeSwitcher,
                                private readonly LogService          $logService,
                                private readonly FriendRepository    $friendRepository,
                                private readonly UserRepository      $userRepository,
                                private readonly DateTimeFormatter   $dateTimeFormatter,
                                private readonly TranslatorInterface $translator)
    {
    }

    #[Route('/{_locale}/user', name: 'app_personal_list', requirements: ['_locale' => 'fr|en|de|es'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->logService->log($request, $this->getUser());
        /** @var User $user */
        $user = $this->getUser();

        $users = $this->userRepository->findAll();
        $friends = $this->friendRepository->findBy(['owner' => $user, 'approved' => true]);
        $friendsOf = $this->friendRepository->findBy(['recipient' => $user, 'approved' => true]);
        $friends = array_merge($friends, $friendsOf);

        dump($user, $users, $friends);

        return $this->render('user/index.html.twig', [
            'user' => $user,
            'users' => $users,
            'friends' => $friends,
        ]);
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
        $this->logService->log($request, $this->getUser());
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

        $friends = $friendRepository->findBy(['owner' => $user, 'approved' => true]);
        $friendRequests = $friendRepository->findBy(['owner' => $user, 'acceptedAt' => null, 'approved' => false]);

        return $this->render('user/profile.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'friends' => $friends,
            'friendRequests' => $friendRequests,
            'from' => 'profile',
            'locale' => $request->getLocale(),
        ]);
    }

    public function checkPendingFriendRequest()
    {
        $user = $this->getUser();
        $pendingRequests = $this->friendRepository->findBy(['recipient' => $user, 'acceptedAt' => null]);

        foreach ($pendingRequests as $request) {
            dump($request);
            $openLetter = ['a', 'e', 'é', 'h', 'i', 'o', 'u'];
            $name = $request->getOwner()->getUsername() ?: $request->getOwner()->getEmail();
            $firstLetter = strtolower($name)[0];

            $this->addFlash('friendship', "Vous avez une demande d'amitié en attente de la part "
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

        dump($friends, $friendRequests);
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

        return $this->json([]);
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
     */
    #[Route('/{_locale}/user/movies', name: 'app_personal_movies', requirements: ['_locale' => 'fr|en|de|es'])]
    public function userMovies(Request $request, UserMovieRepository $userMovieRepository, MovieController $movieController, MovieCollectionRepository $collectionRepository, SettingsRepository $settingsRepository, ImageConfiguration $imageConfiguration): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->logService->log($request, $this->getUser());

        /** @var User $user */
        $user = $this->getUser();

        $movies = $this->getUserMovies($user->getId(), 0, $userMovieRepository);
        $imageConfig = $imageConfiguration->getConfig();

        $items = $userMovieRepository->getUserMoviesRuntime($user->getId());
        $total = 0;
        foreach ($items as $item) {
            $total += $item['runtime'];
        }
        $runtime['total'] = $total;
        $runtime['minutes'] = $total % 60;
        $runtime['hours'] = floor($total / 60) % 24;
        $runtime['days'] = floor($total / 60 / 24) % 30;
        $runtime['months'] = floor($total / 60 / 24 / 30.41666667) % 12;
        $runtime['years'] = floor($total / 60 / 24 / 365);

        $settings = $settingsRepository->findOneBy(['user' => $user, 'name' => 'pinned collection']);
        if (!$settings) {
            $settings = new Settings();
            $settings->setUser($user);
            $settings->setName('pinned collection');
            $settings->setData([["pinned" => 0, "collection_id" => 0]]);
            $settingsRepository->save($settings, true);
        }

        return $this->render('user/movies.html.twig', [
            'discovers' => $movies,
            'userMovies' => $movieController->getUserMovieIds($userMovieRepository),
            'count' => count($items),
            'runtime' => $runtime,
            'locale' => $request->getLocale(),
            'imageConfig' => $imageConfig,
            'user' => $user,
            'dRoute' => 'app_movie',
            'from' => 'user movies',
            'collections' => $collectionRepository->findBy(['user' => $user]),
            'settings' => $settings,
        ]);
    }

    #[Route('/user/movies/more', name: 'app_personal_movies_more')]
    public function userMoviesMore(Request $request, UserMovieRepository $userMovieRepository): Response
    {
        return $this->json([
//            'results' => $userMovieRepository->findUserMovies($request->query->get('id'), $request->query->get('offset')),
            'results' => $this->getUserMovies($request->query->get('id'), $request->query->get('offset'), $userMovieRepository),
        ]);
    }

    public function getUserMovies($userId, $offset, $userMovieRepository): array
    {
        $userMovies = $userMovieRepository->findUserMovies($userId, $offset);
        $movies = [];
        foreach ($userMovies as $userMovie) {
            $movie = $userMovie;
            $collections = $userMovieRepository->userMovieGetCollections($userMovie['id'], $userId);
            $movie['my_collections'] = $collections;
            $movies[] = $movie;
        }
        return $movies;
    }

    #[Route('/{_locale}/user/collection/{id}', name: 'app_personal_movie_collection', requirements: ['_locale' => 'fr|en|de|es'])]
    public function getCollection(Request $request, $id, MovieCollectionRepository $collectionRepository, TMDBService $TMDBService, UserMovieRepository $movieRepository, SettingsRepository $settingsRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var MovieCollection $collection */
        $collection = $collectionRepository->find($id);
        $movies = $this->moviesToArray($request, $collectionRepository->getMoviesByReleaseDate($id, 'DESC'), $TMDBService, $movieRepository);

        $settings = $settingsRepository->findOneBy(['user' => $user, 'name' => 'pinned collection']);
        if (!$settings) {
            $settings = new Settings();
            $settings->setUser($user);
            $settings->setName('pinned collection');
        }
        $settings->setData([["pinned" => 1, "collection_id" => $id]]);
        $settingsRepository->save($settings, true);

        return $this->json([
            'title' => $collection->getTitle(),
            'movies' => $movies
        ]);
    }

    #[Route('/user/collection/pin/status', name: 'app_personal_movie_collection_pin_status', methods: ['GET'])]
    public function setCollectionPinStatus(Request $request, SettingsRepository $settingsRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $settings = $settingsRepository->findOneBy(['user' => $user, 'name' => 'pinned collection']);
        if (!$settings) {
            $settings = new Settings();
            $settings->setUser($user);
            $settings->setName('pinned collection');
        }
        $data = $settings->getData();
//        dump($data);
        $data[0]['pinned'] = $request->query->get('pin');
        $settings->setData($data);
        $settingsRepository->save($settings, true);

        return $this->json([
        ]);
    }

    private function moviesToArray($request, $movies, $TMDBService, $movieRepository): array
    {
        $tab = [];
        $locale = $request->getLocale();
        foreach ($movies as $movie) {
            $overview = $movie['overview_' . $locale];
            if ($overview === null) {
                $standing = $TMDBService->getMovie($movie['movie_db_id'], $locale);
                $movieDetail = json_decode($standing, true);
                $overview = $movieDetail['overview'];

                $m = $movieRepository->findOneBy(['id' => $movie['id']]);
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
                $movieRepository->add($m, true);
            }
            $movie['description'] = $overview;
            $tab[] = $movie;
        }

        return $tab;
    }

    /**
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/{_locale}/user/movies/add', name: 'app_personnel_movie_add', requirements: ['_locale' => 'fr|en|de|es'])]
    public function add(Request $request, TMDBService $callTmdbService, UserMovieRepository $userMovieRepository, EntityManagerInterface $entityManager, MovieController $movieController): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $movieId = $request->query->get('movie_db_id');
        $progressValue = $request->query->get('progress_value');
        $locale = $request->getLocale();

        $userMovie = $movieController->addMovie($user, $movieId, $locale, $callTmdbService, $userMovieRepository, $entityManager);

        return $this->json(['title' => $userMovie->getTitle(), 'progress_value' => $progressValue]);
    }

    #[Route('/{_locale}/user/movies/export', name: 'app_personal_movies_export', requirements: ['_locale' => 'fr|en|de|es'])]
    public function export(Request $request, UserMovieRepository $userMovieRepository): JsonResponse
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
    public function updateSample(Request $request, UserMovieRepository $userMovieRepository, UserRepository $userRepository): JsonResponse
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
    public function jsonUserMovieIds(MovieController $movieController, UserMovieRepository $userMovieRepository): JsonResponse
    {
        return $this->json([
            'movie_ids' => $movieController->getUserMovieIds($userMovieRepository),
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

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function setRandomBanner(BetaSeriesService $betaSeriesService): array
    {
        $standing = $betaSeriesService->showsList(rand(1, 10));
        $discovers = json_decode($standing, true);
        $discover = $discovers['shows'][rand(0, 19)];
        $banner['image'] = $discover['images']['show'] ?: $discover['images']['banner'];
        $banner['title'] = $discover['title'];
        return $banner;
    }

    #[Route('/still-connected/', name: 'app_user_connected')]
    public function stillConnected(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json(['connected' => ($user !== null)]);
    }

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
