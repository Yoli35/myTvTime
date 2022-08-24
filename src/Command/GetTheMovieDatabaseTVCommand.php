<?php

namespace App\Command;

use App\Entity\Network;
use App\Entity\Serie;
use App\Repository\NetworkRepository;
use App\Repository\SerieRepository;
use App\Repository\UserRepository;
use App\Service\CallTmdbService;
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
    private CallTmdbService $callTmdbService;
    private SerieRepository $serieRepository;
    private UserRepository $userRepository;
    private NetworkRepository $networkRepository;

    public function __construct(CallTmdbService   $callTmdbService,
                                SerieRepository   $serieRepository,
                                UserRepository    $userRepository,
                                NetworkRepository $networkRepository
    )
    {
        $this->callTmdbService = $callTmdbService;
        $this->serieRepository = $serieRepository;
        $this->userRepository = $userRepository;
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

                    $tmdbTv = json_decode($this->callTmdbService->getTv($serie->getSerieId(), 'fr'), true);

                    $networks = $tmdbTv['networks'];
                    foreach ($networks as $network) {
                        $m2mNetwork = $this->networkRepository->findOneBy(['name' => $network['name']]);

                        if ($m2mNetwork == null) {
                            $m2mNetwork = new Network();
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

                    if ($serie->getBackdropPath() == null) {

                        $tmdbTv = json_decode($this->callTmdbService->getTv($serie->getSerieId(), 'fr'), true);

                        $serie->setBackdropPath($tmdbTv['backdrop_path']);
                        $this->serieRepository->add($serie, true);

                        $io->success('Backdrop\'s "' . $serie->getName() . '" has been updated to the user ' . $user);
                    }
                }
                break;
            default:
                $standing = $this->callTmdbService->getTv($tvId, 'fr');
                $tv = json_decode($standing, true);

                if (is_array($tv['networks']) && count($tv['networks'])) {
                    $network = $tv['networks'][0];
                } else {
                    $network = null;
                }
                $serie = $this->serieRepository->findOneBy(['serieId' => $tvId]);

                if ($serie == null) {
                    $serie = new Serie();
                }

                // Si elle existe déjà, mise à jour des données
                $serie->setName($tv['name']);
                $serie->setOverview($tv['overview']);
                $serie->setPosterPath($tv['poster_path']);
                $serie->setSerieId($tv['id']);
                $serie->setFirstDateAir(new DateTimeImmutable($tv['first_air_date'] . 'T00:00:00'));
                if ($network) {
                    $serie->setNetwork($network['name']);
                    $serie->setNetworkLogoPath($network['logo_path']);
                }
                $serie->addUser($user);

                $this->serieRepository->add($serie, true);

                $io->success('"' . $serie->getName() . '" has been added to the user ' . $user);
                break;
        }

        return Command::SUCCESS;
    }
}
