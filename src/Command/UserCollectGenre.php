<?php

namespace App\Command;

//use App\Controller\SerieController;
//use App\Entity\TvGenre;
//use App\Entity\UserTvPreference;
//use App\Repository\SerieRepository;
use App\Repository\SerieViewingRepository;
use App\Repository\TvGenreRepository;
use App\Repository\UserRepository;
use App\Repository\UserTvPreferenceRepository;
use App\Service\DateService;
use App\Service\TMDBService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:genre',
    description: 'Collect user\'s genre',
)]
class UserCollectGenre extends Command
{
    public function __construct(
        private readonly DateService                $dateService,
        private readonly LoggerInterface            $logger,
//        private readonly SerieController            $serieController,
//        private readonly SerieRepository            $serieRepository,
        private readonly SerieViewingRepository     $serieViewingRepository,
        private readonly TvGenreRepository          $tvGenreRepository,
        private readonly UserRepository             $userRepository,
        private readonly UserTvPreferenceRepository $userTvPreferenceRepository,
        private readonly TMDBService                $tmdbService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
//            ->addArgument('userid', InputArgument::OPTIONAL, 'User\'s Id')
//            ->addArgument('id', InputArgument::OPTIONAL, 'Serie\'s Id')
            ->addOption('user', 'u', InputOption::VALUE_OPTIONAL, 'User\'s Id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userId = $input->getOption('user');
        if ($userId) {
            $users = [$this->userRepository->find($userId)];
        } else {
            $users = $this->userRepository->findAll();
        }

        $userGenres = [];
        $count = 0;
        $now = $this->dateService->newDateImmutable('now', 'Europe/Paris');
        $io->writeln('Collect genre Command started at ' . $now->format('Y-m-d H:i:s'));

        foreach ($users as $user) {
            $id = $user->getId();
            $userGenres[$id] = [];

            $io->writeln('User: ' . $user->getUsername());
            $series = $this->serieViewingRepository->findBy(['user' => $user]);

            $progressBar = $io->createProgressBar(count($series));
            $progressBar->start();

            foreach ($series as $serie) {
                $tvSeries = json_decode($this->tmdbService->getTv($serie->getSerie()->getSerieId(), "fr"), true);
                if ($tvSeries) {
                    $tvGenres = $tvSeries['genres'];
                    foreach ($tvGenres as $tvGenre) {
                        $gId = $tvGenre['id'];
                        if (!array_key_exists($gId, $userGenres[$id])) {
                            $userGenres[$id][$gId] = [$tvGenre['name'], 1];
                            $count++;
                        } else {
                            $userGenres[$id][$gId][1] = $userGenres[$id][$gId][1] + 1;
                        }
                    }
                }
                $progressBar->advance();
            }
            $progressBar->finish();
            $io->newLine();
        }
//      dump($userGenres);
        $io->writeln('Updating database...');
        $progressBar = $io->createProgressBar($count);

        foreach ($userGenres as $userId => $genres) {
            $user = $this->userRepository->find($userId);
            foreach ($genres as $genreId => $genre) {
                $tvGenre = $this->tvGenreRepository->findOrCreate($genre[0], $genreId);
                $userTvPreference = $this->userTvPreferenceRepository->findOrCreate($user, $tvGenre);
                $userTvPreference->setVitality($genre[1]);
                $this->userTvPreferenceRepository->save($userTvPreference);
                $progressBar->advance();
            }
        }
        $this->userTvPreferenceRepository->flush();
        $progressBar->finish();
        $io->newLine();
        $io->writeln('Done.');

        $now = $this->dateService->newDateImmutable('now', 'Europe/Paris');
        $line = 'Next episode to air Command ended at ' . $now->format('Y-m-d H:i:s');
        $io->writeln($line);

        return Command::SUCCESS;
    }

    public function logs($messages, $level = 'info'): void
    {
        if (!is_iterable($messages)) {
            $messages = [$messages];
        }

        foreach ($messages as $message) {
            $this->logger->{$level}($message);
        }
    }
}
