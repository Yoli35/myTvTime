<?php

namespace App\Command;

use App\Controller\SerieController;
use App\Entity\Networks;
use App\Entity\SerieViewing;
use App\Repository\NetworksRepository;
use App\Repository\SerieRepository;
use App\Repository\SerieViewingRepository;
use App\Repository\UserRepository;
use App\Service\TMDBService;
use DateTimeImmutable;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:getTheMovieDatabaseTV',
    description: 'Get informations for a TV Show on TMDB (The Movie Database)',
)]
class GetTheMovieDatabaseTVCommand extends Command
{
    private TMDBService $tmdbService;
    private SerieController $serieController;
    private SerieRepository $serieRepository;
    private UserRepository $userRepository;
    private SerieViewingRepository $viewingRepository;
    private NetworksRepository $networkRepository;

    public function __construct(TMDBService            $tmdbService,
                                SerieController        $serieController,
                                SerieRepository        $serieRepository,
                                UserRepository         $userRepository,
                                SerieViewingRepository $viewingRepository,
                                NetworksRepository $networkRepository
    )
    {
        $this->tmdbService = $tmdbService;
        $this->serieController = $serieController;
        $this->serieRepository = $serieRepository;
        $this->userRepository = $userRepository;
        $this->viewingRepository = $viewingRepository;
        $this->networkRepository = $networkRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('userid', InputArgument::REQUIRED, 'User\'s Id')
            ->addArgument('id', InputArgument::REQUIRED, 'Serie\'s Id')
            ->setHelp('This Command recovers information from the television series');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tvId = $input->getArgument('id');
        $userId = $input->getArgument('userid');

        $user = $this->userRepository->find($userId);

        switch ($tvId) {
            case 'network':
                $tvs = $this->serieRepository->findAll();
                $n = 0;

                foreach ($tvs as $serie) {

                    $tmdbTv = json_decode($this->tmdbService->getTv($serie->getSerieId(), 'fr'), true);

                    $networks = $tmdbTv['networks'];
                    foreach ($networks as $network) {
                        $m2mNetwork = $this->networkRepository->findOneBy(['name' => $network['name']]);

                        if ($m2mNetwork == null) {
                            $m2mNetwork = new Networks();
                            $m2mNetwork->setName($network['name']);
                            $m2mNetwork->setLogoPath($network['logo_path']);
                            $m2mNetwork->setOriginCountry($network['origin_country']);
                            $this->networkRepository->add($m2mNetwork, true);
                        }
                        $serie->addNetwork($m2mNetwork);
                    }
                    $this->serieRepository->add($serie, true);

                    $io->success(++$n . ' - Networks for "' . $serie->getName() . '" have been updated.');
                }
                break;

            case 'all':
                $tvs = $this->serieRepository->findAll();

                foreach ($tvs as $serie) {

                    if ($serie->getOriginalName() == null) {

                        $tmdbTv = json_decode($this->tmdbService->getTv($serie->getSerieId(), 'fr'), true);

                        $serie->setOriginalName($tmdbTv['original_name']);
                        $this->serieRepository->add($serie, true);

                        $io->success('Original name for "' . $serie->getName() . '" have been updated to the user ' . $user);
                    }
                }
                break;

            case 'status':
                $tvs = $this->serieRepository->findAll();

                foreach ($tvs as $serie) {

                    $tmdbTv = json_decode($this->tmdbService->getTv($serie->getSerieId(), 'fr'), true);
                    $modified = 0;
                    $fields = '';
                    /*
                     * Les quatre champs suivants ont été ajoutés après la création de la table
                     * ========================================================================
                     * updated_at : modifiée par l'utilisateur, épisodes vus, par exemple
                     * modified_at : modifiée par The Movie Database, nombre de saisons / épisodes
                     * status : statut de la série (terminée, annulée, reconduite)
                     * serie_completed : tous les épisodes de toutes les saisons ont été vus
                     */
                    if ($serie->getUpdatedAt() === null) {
                        $serie->setUpdatedAt($serie->getAddedAt());
                        $fields = 'updated_at';
                        $modified++;
                    }
                    if ($serie->getModifiedAt() === null) {
                        $serie->setModifiedAt($serie->getAddedAt());
                        $fields .= 'modified_at';
                        $modified++;
                    }
                    if ($serie->getStatus() === null) {
                        $serie->setStatus($tmdbTv['status']);
                        if (strlen($fields)) $fields .= ' / ';
                        $fields .= 'status';
                        $modified++;
                    }
                    if ($serie->isSerieCompleted() === null) {
                        $serie->setSerieCompleted(false);
                        if (strlen($fields)) $fields .= ' / ';
                        $fields .= 'serie_completed';
                        $modified++;
                    }
                    /*
                     * Les nombres de saison et d'épisode ont changé
                     */
                    if ($serie->getNumberOfSeasons() !== $tmdbTv['number_of_seasons']) {
                        $serie->setNumberOfSeasons($tmdbTv['number_of_seasons']);
                        $serie->setModifiedAt(new DateTimeImmutable());
                        if (strlen($fields)) $fields .= ' / ';
                        $fields .= 'number_of_seasons';
                        $modified++;
                    }
                    if ($serie->getNumberOfEpisodes() !== $tmdbTv['number_of_episodes']) {
                        $serie->setNumberOfEpisodes($tmdbTv['number_of_episodes']);
                        $serie->setModifiedAt(new DateTimeImmutable());
                        if (strlen($fields)) $fields .= ' / ';
                        $fields .= 'number_of_episodes';
                        $modified++;
                    }
                    /*
                     * Si quelque chose a changé, l'enregistrement est mis à jour
                     */
                    if ($modified) {
                        $this->serieRepository->add($serie, true);
                        $io->success($fields . ' for "' . $serie->getName() . '" ' . ($modified > 1 ? 'have' : 'has') . ' been updated to the user ' . $user);
                    }
                }
                break;

            case 'episode':
                if ($user) {

                    $io->success('Utilisateur: ' . $user->getUsername());
                    $series = $this->serieRepository->findAllUserSeries($user->getId());

                    foreach ($series as $serie) {
                        $tv = json_decode($this->tmdbService->getTv($serie->getSerieId(), 'fr'), true);

                        if ($tv == null) {
                            $io->error('Les données de la série « ' . $serie->getName() . ' » (' . $serie->getSerieId() . ') n\'ont pu être récupérées.');
                        } else {
                            /** @var SerieViewing $viewing */
                            $viewing = $this->viewingRepository->findOneBy(['user' => $user, 'serie' => $serie]);

                            if ($viewing == null) {
                                $viewing = new SerieViewing();
                                $viewing->setUser($user);
                                $viewing->setSerie($serie);
                                $viewing->setViewing($this->serieController->createViewingTab($tv));
                                $viewing->setViewedEpisodes(0);
                            } else {
                                $viewing->setViewing($this->serieController->updateViewing($tv, $viewing, $this->viewingRepository));
                            }
                            $this->viewingRepository->add($viewing, true);
                            $n = $viewing->getViewedEpisodes();
                            $io->success($serie->getName() . ' : ' . $n . ' / ' . $serie->getNumberOfEpisodes() . ' épisode' . ($n > 1 ? 's' : ''));
                        }
                    }
                }
                break;
            default:

                break;
        }

        return Command::SUCCESS;
    }
}
