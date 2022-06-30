<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserMovieRepository;
use App\Service\BetaSeriesService;
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

    /**
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/{_locale}/personal/movies', name: 'app_personal_movies', requirements: ['_locale' => 'fr|en|de|es'])]
    public function userMovies(Request $request, UserMovieRepository $userMovieRepository, ImageConfiguration $imageConfiguration): Response
    {
        /** TODO  Export Movie List as a json file */
        /** TODO  Progressive Load of Movie List */

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $movies = $userMovieRepository->findUserMovies($user->getId());
        $imageConfig = $imageConfiguration->getConfig();

        $total = 0;
        foreach ($movies as $movie) {
            $total += $movie['runtime'];
        }
        $runtime['total'] = $total;
        $runtime['minutes'] = $total % 60;
        $runtime['hours'] = floor($total/60) % 24;
        $runtime['days'] = floor($total/60/24) % 30.41666667;
        $runtime['months'] = floor($total/60/24/30.41666667) % 12;
        $runtime['years'] = floor($total/60/24/365);

        return $this->render('user_account/user_movies.html.twig', [
            'discovers' => $movies,
            'runtime' => $runtime,
            'locale' => $request->getLocale(),
            'imageConfig' => $imageConfig,
        ]);
    }

    #[Route('/{_locale}/personal/movies/export', name: 'app_personal_movies_export', requirements: ['_locale' => 'fr|en|de|es'])]
    public function export(Request $request, UserMovieRepository $userMovieRepository): JsonResponse
    {
        $id = $request->query->get('id');
        $locale = $request->getLocale();
        $movies = $userMovieRepository->findAllUserMovies($id);
        $count = count($movies);
        $json = '{"total_results":'.$count.',"results":'.json_encode($movies).'}';

        $sample = '{<br>'
            .'&nbsp;&nbsp;&nbsp;&nbsp;<span>"total_results":</span> ' . $count . ',<br>'
            .'&nbsp;&nbsp;&nbsp;&nbsp;<span>"results":</span> [<br>'
            .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{<br>'
            .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>"id":</span> '.$movies[0]['id'].'<span>,</span><br>'
            .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>"title":</span> "'.$movies[0]['title'].'"<span>,</span><br>'
            .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>"original_title":</span> "'.$movies[0]['original_title'].'"<span>,</span><br>'
            .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>"poster_path":</span> "\\'.$movies[0]['poster_path'].'"<span>,</span><br>'
            .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>"release_date":</span> "'.$movies[0]['release_date'].'"<span>,</span><br>'
            .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>"movie_db_id":</span> '.$movies[0]['movie_db_id'].',<span>,</span><br>'
            .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>"runtime":</span> '.$movies[0]['runtime'].'<span>,</span><br>'
            .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>"user_id":</span> '.$movies[0]['user_id'].'<span>,</span><br>'
            .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>"user_movie_id":</span> '.$movies[0]['user_movie_id'].'<br>'
            .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;}';

        if ($count > 1) {

            if ($count > 2) {
                switch ($locale) {
                    case 'fr':     $sample .= ',<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<i>[...] /* et '.($count-2).' autre'.($count>3?'s':'').' */</i><br>'; break;
                    case 'en':     $sample .= ',<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<i>[...] /* and '.($count-2).' more */</i><br>'; break;
                    case 'de':     $sample .= ',<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<i>[...] /* und '.($count-2).' '.($count>3?'andere':'weiterer').' */</i><br>'; break;
                    case 'es':     $sample .= ',<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<i>[...] /* y otro'.($count>3?'s ':' ').($count-2).' */</i><br>'; break;
                }
            }
            $sample .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{<br>'
                .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>"id":</span> '.$movies[$count-1]['id'].'<span>,</span><br>'
                .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>"title":</span> "'.$movies[$count-1]['title'].'"<span>,</span><br>'
                .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>"original_title":</span> "'.$movies[$count-1]['original_title'].'"<span>,</span><br>'
                .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>"poster_path":</span> "\\'.$movies[$count-1]['poster_path'].'"<span>,</span><br>'
                .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>"release_date":</span> "'.$movies[$count-1]['release_date'].'"<span>,</span><br>'
                .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>"movie_db_id":</span> '.$movies[$count-1]['movie_db_id'].'<span>,</span><br>'
                .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>"runtime":</span> '.$movies[$count-1]['runtime'].'<span>,</span><br>'
                .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>"user_id":</span> '.$movies[$count-1]['user_id'].'<span>,</span><br>'
                .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>"user_movie_id":</span> '.$movies[$count-1]['user_movie_id'].'<br>'
                .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;}';
        }

        $sample .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;]<br>}';

        /** @var User $user */
        $user = $this->getUser();
        $dir = $this->getParameter('movie_list_directory');
        $filename = $this->getFilename($user);

        $file = fopen($dir . '/' . $filename, "w");
        fwrite($file, $json);
        fclose($file);

        $url = $this->generateUrl('app_json');// . $filename;
        dump($url);

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

    #[Route('/{_locale}/personal/movies/ids', name: 'app_json_ids', requirements: ['_locale' => 'fr|en|de|es'])]
    public function jsonUserMovieIds(MovieController $movieController, UserMovieRepository $userMovieRepository): JsonResponse
    {
        return $this->json([
            'movie_ids' => $movieController->getUserMovieIds($userMovieRepository),
        ]);
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

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function setRandomBanner(BetaSeriesService $betaSeriesService): array
    {
        $standing = $betaSeriesService->showsList(rand(1, 10));
        $discovers = json_decode($standing, true, 512, 0);
        $discover = $discovers['shows'][rand(0, 19)];
        $banner['image'] = $discover['images']['show'] ? : $discover['images']['banner'];
        $banner['title'] = $discover['title'];
        return $banner;
    }
}
