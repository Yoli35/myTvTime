<?php

namespace App\Command;

use App\Entity\Rating;
use App\Repository\FavoriteRepository;
use App\Repository\RatingRepository;
use App\Repository\UserRepository;
use App\Service\DateService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:export:user:movies',
    description: 'Export user movies',
)]
class ExportUserMoviesCommand extends Command
{
    public function __construct(
        private readonly DateService        $dateService,
        private readonly FavoriteRepository $favoriteRepository,
        private readonly RatingRepository   $ratingRepository,
        private readonly UserRepository     $userRepository,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'User id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userId = $input->getOption('user');

        if (!$userId) {
            $userId = $io->ask('User\'s Id');
            if (!$userId) {
                $io->error('User\'s Id is required');
                return Command::FAILURE;
            }
        }
        $count = 0;
        $t0 = microtime(true);
        $now = $this->dateService->newDateImmutable('now', 'Europe/Paris');
        $io->writeln('Export Command started at ' . $now->format('Y-m-d H:i:s'));
        $user = $this->userRepository->find($userId);
        $userMovies = $user->getMovies();
        $allExportedMovies = [];

        foreach ($userMovies as $movie) {
            $io->writeln($movie->getTitle());
            $movieRating = $this->ratingRepository->findOneBy([
                'user' => $user,
                'movie' => $movie,
            ]);
            $movieFavorite = $this->favoriteRepository->findOneBy([
                'type' => 'movie', // 'movie' or 'serie
                'mediaId' => $movie->getId(),
                'userId' => $user->getId(),
            ]);
            $exportedMovie = [
                'title' => $movie->getTitle(),
                'id' => $movie->getMovieDbId(),
                'rating' => $movieRating?->getValue(),
                'favorite' => $movieFavorite !== null,
            ];
            $allExportedMovies[] = $exportedMovie;
            $count++;
        }

        $date = $now->format('Y-m-d H-i-s');
        $jsonFile = fopen('exportedMovies ' . $date . '.json', 'w');
        fwrite($jsonFile, json_encode([
            'exportedAt' => $date,
            'moviesCount' => $count,
            'movies' => $allExportedMovies,
        ], JSON_PRETTY_PRINT));
        fclose($jsonFile);

        $line = sprintf("Done. %d series exported", $count);
        $io->success($line);

        $t1 = microtime(true);
        $line = sprintf("Execution time: %.2f seconds", $t1 - $t0);
        $io->writeln($line);
        $now = $this->dateService->newDateImmutable('now', 'Europe/Paris');
        $line = 'Export Command ended at ' . $now->format('Y-m-d H:i:s');
        $io->writeln($line);

        return Command::SUCCESS;
    }
}
