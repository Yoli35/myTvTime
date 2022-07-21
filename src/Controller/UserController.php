<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserMovie;
use App\Form\UserType;
use App\Repository\UserMovieRepository;
use App\Repository\UserRepository;
use App\Service\BetaSeriesService;
use App\Service\CallTmdbService;
use App\Service\FileUploader;
use App\Service\ImageConfiguration;
use App\Service\WeatherService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class UserController extends AbstractController
{
    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/{_locale}/personal/profile', name: 'app_personal_profile', requirements: ['_locale' => 'fr|en|de|es'])]
    public function index(Request $request, EntityManagerInterface $entityManager, FileUploader $fileUploader, BetaSeriesService $betaSeriesService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $avatarFile */
            $avatarFile = $form->get('avatar')->getData();
            if ($avatarFile) {
                $avatarFileName = $fileUploader->upload($avatarFile, 'avatar');
                $fileToBeRemoved = $user->getAvatar();
                if ($fileToBeRemoved) {
                    $fileUploader->removeFile($fileToBeRemoved, 'avatar');
                }
                $user->setAvatar($avatarFileName);
            }
            /** @var UploadedFile $bannerFile */
            $bannerFile = $form->get('banner')->getData();
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

            return $this->redirectToRoute('app_personal_profile');
        }

        $banner = [];
        if ($user->getBanner() == null) {
            $banner = $this->setRandomBanner($betaSeriesService);
        }
        return $this->render('user_account/index.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'banner' => $banner,
        ]);
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
    #[Route('/{_locale}/personal/movies', name: 'app_personal_movies', requirements: ['_locale' => 'fr|en|de|es'])]
    public function userMovies(Request $request, UserMovieRepository $userMovieRepository, ImageConfiguration $imageConfiguration): Response
    {
        /** TODO  Progressive Load of Movie List */

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $movies = $userMovieRepository->findUserMovies($user->getId());
        $imageConfig = $imageConfiguration->getConfig();

        $items = $userMovieRepository->getUserMoviesRuntime($user->getId());
        $total = 0;
        foreach ($items as $item) {
            $total += $item['runtime'];
        }
        $runtime['total'] = $total;
        $runtime['minutes'] = $total % 60;
        $runtime['hours'] = floor($total/60) % 24;
        $runtime['days'] = floor($total/60/24) % 30.41666667;
        $runtime['months'] = floor($total/60/24/30.41666667) % 12;
        $runtime['years'] = floor($total/60/24/365);

        return $this->render('user_account/user_movies.html.twig', [
            'discovers' => $movies,
            'count' => count($items),
            'runtime' => $runtime,
            'locale' => $request->getLocale(),
            'imageConfig' => $imageConfig,
            'dRoute' => 'app_movie',
        ]);
    }

    #[Route('/personal/movies/more', name: 'app_personal_movies_more')]
    public function userMoviesMore(Request $request, UserMovieRepository $userMovieRepository): Response
    {
        return $this->json([
            'results' => $userMovieRepository->findUserMovies($request->query->get('id'), $request->query->get('offset')),
        ]);
    }

    /**
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/{_locale}/personal/movies/add', name: 'app_personnel_movie_add', requirements: ['_locale' => 'fr|en|de|es'])]
    public function add(Request $request, CallTmdbService $callTmdbService, UserMovieRepository $userMovieRepository, EntityManagerInterface $entityManager, MovieController $movieController): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $movieId = $request->query->get('movie_db_id');
        $progressValue = $request->query->get('progress_value');
        $locale = $request->getLocale();

        $userMovie = $movieController->addMovie($user, $movieId, $locale, $callTmdbService, $userMovieRepository, $entityManager);

        return $this->json(['title' => $userMovie->getTitle(), 'progress_value' => $progressValue]);
    }

    #[Route('/{_locale}/personal/movies/export', name: 'app_personal_movies_export', requirements: ['_locale' => 'fr|en|de|es'])]
    public function export(Request $request, UserMovieRepository $userMovieRepository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $id = $request->query->get('id');
        $movies = $userMovieRepository->findAllUserMovies($id);
        $count = count($movies);
        $json = $this->formatJson('{"total_results":' . $count . ',"results":' . json_encode($movies) . '}');

        $filename = $this->saveFile($json, $user);
        $url = $this->generateUrl('app_json');
        $sample = $this->sample($request, $userMovieRepository, $id, null);

        return $this->json([
            'count' => $count,
            'movies' => $movies,
            'json' => $json,
            'url' => $url,
            'file' => $filename,
            'sample' => $sample
        ]);
    }

    #[Route('/movielist/', name: 'app_json')]
    public function jsonUrl()
    {

    }

    #[Route('/{_locale}/movielist/updateSample', name: 'app_json_sample', requirements: ['_locale' => 'fr|en|de|es'])]
    public function updateSample(Request $request, UserMovieRepository $userMovieRepository, UserRepository $userRepository): JsonResponse
    {
        $userId = $request->get('user_id');
        $ids = $request->get('ids');
        $ids = json_decode($ids, true);
        $filename = $request->get('filename');

        $sample = $this->sample($request, $userMovieRepository, $userId, $ids);

        $movies = $this->userMoviesFromList($userId, $userMovieRepository, $ids);
        $count = count($movies);
        $jsonMovies = json_encode($movies);
        $json = '{"total_results":' . $count . ',"results":' . $jsonMovies . '}';
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

    #[Route('/{_locale}/personal/movies/ids', name: 'app_json_ids', requirements: ['_locale' => 'fr|en|de|es'])]
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

    private function sample(Request $request, UserMovieRepository $userMovieRepository, int $user_id, mixed $ids):string
    {
        $tab = '&nbsp;&nbsp;&nbsp;&nbsp;';

        $movies = $this->userMoviesFromList($user_id, $userMovieRepository, $ids);
        $count = count($movies);

        $sample = '{<br>'.$tab.'"total_results": ' . $count . ',<br>'.$tab.'"results":';
        if ($count == 1) {
            $sample .= ' [<br>'.$tab.$tab;
            $sample .= $this->formatJson(json_encode($movies[0]), 2, $tab, '<br>');
            $sample .= '<br>'.$tab.']';
        }
        if ($count == 2) {
            $sample .= ' ' . $this->formatJson(json_encode($movies), 1, $tab, '<br>');
        }
        if ($count > 2) {
            $sample .= ' [<br>'.$tab.$tab;
            $sample .= $this->formatJson(json_encode($movies[0]), 2, $tab, '<br>');

            $sample .= $this->andOther($request->getLocale(), $count, $tab).$tab.$tab;

            $sample .= $this->formatJson(json_encode($movies[$count - 1]), 2, $tab, '<br>');
            $sample .= '<br>'.$tab.']';
        }
        $sample .= '<br>}';

        return preg_replace(
            ['#.("\w*"):\s("[a-zA-Z0-9\s\\\/\'.,:-]*"),#', '#.("\w*"):\s(\d*)#'],
            ['<span class="key">$1</span>: <span class="value-alpha">$2</span>,', '<span class="key">$1</span>: <span class="value-digit">$2</span>'],
            $sample);
    }

    private function userMoviesFromList(int $userId, UserMovieRepository $userMovieRepository, mixed $list): array
    {
        if ($list==null) {
            $list = [];
            $userMovies = $userMovieRepository->findUserMovieIds($userId);
            foreach ($userMovies as $userMovie) {
                $list[] = $userMovie['movie_db_id'];
            }
        }
        return $userMovieRepository->getUserMovieFromIdList($userId, $list);
    }

    private function andOther($locale, $count, $tab):string
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
        $banner['image'] = $discover['images']['show'] ? : $discover['images']['banner'];
        $banner['title'] = $discover['title'];
        return $banner;
    }
}
