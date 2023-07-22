<?php

namespace App\Command;

use App\Controller\SerieController;
use App\Repository\SerieRepository;
use App\Repository\SerieViewingRepository;
use App\Repository\UserRepository;
use App\Service\DateService;
use App\Service\TMDBService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:next-episode-to',
    description: 'Check for next episode to air and to watch',
)]
class NextEpisodeToAir extends Command
{
    public function __construct(
        private readonly DateService            $dateService,
        private readonly LoggerInterface        $logger,
        private readonly SerieController        $serieController,
        private readonly SerieRepository        $serieRepository,
        private readonly SerieViewingRepository $serieViewingRepository,
        private readonly UserRepository         $userRepository,
        private readonly TMDBService            $tmdbService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('userid', InputArgument::OPTIONAL, 'User\'s Id')
            ->addArgument('id', InputArgument::OPTIONAL, 'Serie\'s Id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userId = $input->getArgument('userid');
        $serieId = $input->getArgument('id');
        $canSkip = true;

        if ($userId && $serieId) {
            $user = $this->userRepository->find($userId);
            $serie = $this->serieRepository->find($serieId);
            $serieViewings = $this->serieViewingRepository->findBy(['user' => $user, 'serie' => $serie]);
            $canSkip = false;
        } else {
            $serieViewings = $this->serieViewingRepository->findAll();
        }
        $count = 0;
        $skipped = 0;
        $error = 0;
        $now = $this->dateService->newDateImmutable('now', 'Europe/Paris', true);
        $this->logger->info('Next episode to air Command started at ' . $now->format('Y-m-d H:i:s'));

        foreach ($serieViewings as $serieViewing) {

            $serie = $serieViewing->getSerie();
            $io->writeln($serie->getName() . ' (' . $serie->getId() . ') for user ' . $serieViewing->getUser()->getUsername() . ' (' . $serieViewing->getUser()->getId() . ')');
            $this->logs($serie->getName() . ' (' . $serie->getId() . ') for user ' . $serieViewing->getUser()->getUsername() . ' (' . $serieViewing->getUser()->getId() . ')');

            if ($canSkip) {
                // Si la dernière vérification de l'épisode suivant est récente, on ne fait rien
                if ($serieViewing->getNextEpisodeCheckDate()) {
                    $diff = $now->diff($serieViewing->getNextEpisodeCheckDate());
                    if ($diff->days < 2) {
                        $io->writeln([str_repeat("••", 40), '    the last check is recent (< 2 days), skipping', str_repeat("••", 40)]);
                        $this->logs('    the last check is recent (< 2 days), skipping');
                        $skipped++;
                        continue;
                    }
                }
                // Si le dernier épisode de la série a été vu depuis plus de 2 ans, on ne fait rien, puisqu'il est probable que la série soit terminée
                $lastSeasonViewing = $this->serieController->getSeasonViewing($serieViewing, $serieViewing->getNumberOfSeasons());
                if ($lastSeasonViewing === null) {
                    $io->writeln([str_repeat("*+", 40), '    Last season viewing is null, skipping', str_repeat("+*", 40)]);
                    $this->logs('    Last season viewing is null, skipping');
                    $skipped++;
                    continue;
                }
                $lastEpisodeViewing = $lastSeasonViewing->getEpisodeByNumber($lastSeasonViewing->getEpisodeCount());
                if ($lastEpisodeViewing) {
                    $now = $this->dateService->newDateImmutable('now', 'Europe/Paris', true);
                    $lastViewingDate = $lastEpisodeViewing->getViewedAt();
                    if ($lastViewingDate !== null) {
                        $diff = $now->diff($lastViewingDate);
                        if ($diff->y >= 2) {
                            $io->writeln([str_repeat("*•", 40), '    Last episode viewed more than 2 years ago, skipping', str_repeat("*•", 40)]);
                            $this->logs('    Last episode viewed more than 2 years ago, skipping');
                            $skipped++;
                            continue;
                        }
                    }
                    $airDate = $lastEpisodeViewing->getAirDate();
                    if ($airDate !== null) {
                        $diff = $now->diff($airDate);
                        if ($diff->y >= 2) {
                            $io->writeln([str_repeat("•~", 40), '    Last episode aired more than 2 years ago, skipping', str_repeat("~•", 40)]);
                            $this->logs('    Last episode aired more than 2 years ago, skipping');
                            $skipped++;
                            continue;
                        }
                    }
                }
            }

            $tvSeries = json_decode($this->tmdbService->getTv($serie->getSerieId(), "fr_FR"), true);

            if ($tvSeries) {
                $this->serieController->updateSerieViewing($serieViewing, $tvSeries, $serie, true);
                $io->writeln($this->serieController->messages);
                $this->logs($this->serieController->messages);
                $count++;
            } else {
                $io->error('    TV Series not found');
                $this->logs('    TV Series not found', 'error');
                $error++;
            }
        }

        $io->success('Done. ' . $count . ' series updated, '. $skipped . ' skipped, ' . $error . ' error' . ($error > 1 ? 's' : ''));
        $this->logs('Done. ' . $count . ' series updated, '. $skipped . ' skipped, ' . $error . ' error' . ($error > 1 ? 's' : ''));

        $this->logger->info('Next episode to air Command ended at ' . $now->format('Y-m-d H:i:s'));

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
