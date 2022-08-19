<?php

namespace App\Command;

use App\Entity\Serie;
use App\Repository\SerieRepository;
use App\Repository\UserRepository;
use App\Service\CallTmdbService;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'app:getTheMovieDatabaseTV',
    description: 'Get informations for a TV Show on TMDB (The Movie Database)',
)]
class GetTheMovieDatabaseTVCommand extends Command
{
    private CallTmdbService $callTmdbService;
    private SerieRepository $serieRepository;
    private UserRepository $userRepository;

    public function __construct(CallTmdbService $callTmdbService,
                                SerieRepository $serieRepository,
                                UserRepository  $userRepository
    )
    {
        $this->callTmdbService = $callTmdbService;
        $this->serieRepository = $serieRepository;
        $this->userRepository = $userRepository;

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
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tvId = $input->getArgument('id');
        $userId = $input->getArgument('userid');

        $user = $this->userRepository->find($userId);

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
        $serie->setFirstDateAir(new \DateTimeImmutable($tv['first_air_date'] . 'T00:00:00'));
        if ($network) {
            $serie->setNetwork($network['name']);
            $serie->setNetworkLogoPath($network['logo_path']);
        }
        $serie->addUser($user);

        $this->serieRepository->add($serie, true);

        $io->success('"' . $serie->getName() . '" has been added to the user ' . $user);

        return Command::SUCCESS;
    }
}
