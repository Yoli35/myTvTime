<?php

namespace App\Command;

use App\Entity\UserMovie;
use App\Service\TMDBService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(name: 'app:user-movie-update', description: 'This Command updates the "runtime" Field in "user_movie" Table',)]
class UserMovieUpdateCommand extends Command
{
    private TMDBService $callTmdbService;
    private ManagerRegistry $managerRegistry;
    private EntityManagerInterface $entityManager;

    public function __construct(TMDBService $callTmdbService, ManagerRegistry $managerRegistry, EntityManagerInterface $entityManager)
    {
        $this->callTmdbService = $callTmdbService;
        $this->managerRegistry = $managerRegistry;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            // the command help shown when running the command with the "--help" option
            ->setHelp('This Command updates the "runtime" Field in "user_movie" Table');
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $repoUM = $this->managerRegistry->getRepository(UserMovie::class);
        /** @var UserMovie $userMovies */
        $userMovies = $repoUM->findAll();

        $total_runtime = 0;
        foreach ($userMovies as $userMovie) {
            $output->write($userMovie->getTitle().' : ');
            $standing = $this->callTmdbService->getMovie($userMovie->getMovieDbId(), 'en');
            $movie = json_decode($standing, true);
            $runtime = $movie['runtime'];
            $output->writeln($runtime.' minutes.');
            $total_runtime += $runtime;

            $userMovie->setRuntime($runtime);
            $this->entityManager->persist($userMovie);
        }

        $this->entityManager->flush();
        $io->success($total_runtime.' minutes watched !');

        return Command::SUCCESS;
    }
}
