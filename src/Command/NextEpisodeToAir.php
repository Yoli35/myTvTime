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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
//            ->addArgument('userid', InputArgument::OPTIONAL, 'User\'s Id')
//            ->addArgument('id', InputArgument::OPTIONAL, 'Serie\'s Id')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'User\'s Id')
            ->addOption('serie', 's', InputOption::VALUE_REQUIRED, 'Serie\'s Id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userId = $input->getOption('user');
        $serieId = $input->getOption('serie');
        $canSkip = true;

        if ($userId && $serieId) {
            $user = $this->userRepository->find($userId);
            $serie = $this->serieRepository->find($serieId);
            $serieViewings = $this->serieViewingRepository->findBy(['user' => $user, 'serie' => $serie]);
            $canSkip = false;
        } else {
            $serieViewings = $this->serieViewingRepository->findAll();
            $confirm = $io->ask('Do you want to skip series with recent check and last episode viewed more than 2 years ago?', 'yes');
            if ($confirm !== 'yes' && $confirm !== 'y') {
                $canSkip = false;
            }
            $confirm = $io->ask('Check all the series (' . count($serieViewings) . ' series)?', 'yes');
            if ($confirm !== 'yes' && $confirm !== 'y') {
                $io->warning('Confirmation not given. Exiting.');
                return Command::SUCCESS;
            }
        }
        $count = 0;
        $skipped = 0;
        $error = 0;
        $report = [];
        $now = $this->dateService->newDateImmutable('now', 'Europe/Paris');
        $this->logger->info('Next episode to air Command started at ' . $now->format('Y-m-d H:i:s'));
        $io->writeln('Next episode to air Command started at ' . $now->format('Y-m-d H:i:s'));

        foreach ($serieViewings as $serieViewing) {

            $serie = $serieViewing->getSerie();
            $line = sprintf("%s (%d) for user %s (%d)", $serie->getName(), $serie->getId(), $serieViewing->getUser()->getUsername(), $serieViewing->getUser()->getId());
            $io->writeln($line);
            $this->logs($line);

            if ($canSkip) {
                // Si la dernière vérification de l'épisode suivant est récente, on ne fait rien
                if ($serieViewing->getNextEpisodeCheckDate()) {
                    $diff = $now->diff($serieViewing->getNextEpisodeCheckDate());
                    if ($diff->days < 2) {
                        $line = "    the last check is recent (< 2 days), skipping";
                        $io->writeln([$line, str_repeat("•", strlen($line))]);
                        $this->logs($line);
                        $skipped++;
                        continue;
                    }
                }
                // Si le dernier épisode de la série a été vu depuis plus de 2 ans, on ne fait rien, puisqu'il est probable que la série soit terminée
                $lastSeasonViewing = $this->serieController->getSeasonViewing($serieViewing, $serieViewing->getNumberOfSeasons());
                if ($lastSeasonViewing === null) {
                    $line = "    Last season viewing is null, skipping";
                    $io->writeln([$line, str_repeat("+", strlen($line))]);
                    $this->logs($line);
                    $skipped++;
                    continue;
                }
                $lastEpisodeViewing = $lastSeasonViewing->getEpisodeByNumber($lastSeasonViewing->getEpisodeCount());
                if ($lastEpisodeViewing) {
                    $now = $this->dateService->newDateImmutable('now', 'Europe/Paris');
                    $lastViewingDate = $lastEpisodeViewing->getViewedAt();
                    if ($lastViewingDate !== null) {
                        $diff = $now->diff($lastViewingDate);
                        if ($diff->y >= 2) {
                            $line = "    Last episode viewed more than 2 years ago, skipping";
                            $io->writeln([$line, str_repeat("*", strlen($line))]);
                            $this->logs($line);
                            $skipped++;
                            continue;
                        }
                    }
                    $airDate = $lastEpisodeViewing->getAirDate();
                    if ($airDate !== null) {
                        $diff = $now->diff($airDate);
                        if ($diff->y >= 2) {
                            $line = "    Last episode aired more than 2 years ago, skipping";
                            $io->writeln([$line, str_repeat("~", strlen($line))]);
                            $this->logs($line);
                            $skipped++;
                            continue;
                        }
                    }
                }
            }

            $tvSeries = json_decode($this->tmdbService->getTv($serie->getSerieId(), "fr"), true);

            if ($tvSeries) {
                $whatsNew = $this->serieController->whatsNew($tvSeries, $serie, $serieViewing, true);
                if ($whatsNew) {
                    foreach ($whatsNew as $new) {
                        if ($new) {
                            $io->writeln('new ' . $new);
                            $this->logs('new ' . $new, 'warning');
                            $report[] = sprintf("%d: %s - %s", $serie->getId(), $serie->getName(), $new);
                        }
                    }
                }
                $this->serieController->updateSerieViewing($serieViewing, $tvSeries, true);
                $io->writeln($this->serieController->messages);
                $this->logs($this->serieController->messages);
                $count++;
            } else {
                $message = '    TV Series not found';
                $io->error($message);
                $this->logs($message);
                $report[] = sprintf("%d: %s - %s", $serie->getId(), $serie->getName(), $message);
                $error++;
            }
        }

        if (count($report)) {
            $io->writeln('Report:');
            foreach ($report as $message) {
                $io->writeln($message);
            }
            $io->writeln('End of report');
        }

        $line = sprintf("Done. %d series updated, %d skipped, %d error%s", $count, $skipped, $error, $error > 1 ? 's' : '');
        $io->success($line);
        $this->logs($line);

        $now = $this->dateService->newDateImmutable('now', 'Europe/Paris');
        $line = 'Next episode to air Command ended at ' . $now->format('Y-m-d H:i:s');
        $this->logger->info($line);
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
