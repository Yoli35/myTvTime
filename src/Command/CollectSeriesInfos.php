<?php

namespace App\Command;

use App\Controller\SerieFrontController;
use App\Entity\Episode;
use App\Entity\Season;
use App\Repository\EpisodeRepository;
use App\Repository\SeasonRepository;
use App\Repository\SerieRepository;
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
    name: 'app:series:infos',
    description: 'Collect season & episode infos for all series or for a specific serie',
)]
class CollectSeriesInfos extends Command
{
    public function __construct(
        private readonly SerieFrontController $serieFrontController,
        private readonly DateService          $dateService,
        private readonly SerieRepository      $serieRepository,
        private readonly TMDBService          $tmdbService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
//            ->addArgument('userid', InputArgument::OPTIONAL, 'User\'s Id')
//            ->addArgument('id', InputArgument::OPTIONAL, 'Serie\'s Id')
            ->addOption('serie', 's', InputOption::VALUE_REQUIRED, 'Serie\'s Id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->writeln('Collect series infos Command');

        $serieId = $input->getOption('serie');

        if ($serieId) {
            $seriesList = $this->serieRepository->findBy(['id' => $serieId]);
        } else {
            $seriesList = $this->serieRepository->findAll();
        }
        $count = 0;
        $error = 0;
        $report = [];
        $now = $this->dateService->newDateImmutable('now', 'Europe/Paris');
        $io->writeln('Season & episode infos Command started at ' . $now->format('Y-m-d H:i:s'));

        foreach ($seriesList as $series) {

            $line = sprintf("%s (%d)", $series->getName(), $series->getId());
            $io->writeln($line);

            if ($series->getStatus() === 'Ended' || $series->getStatus() === 'Canceled') {
                continue;
            }

            $tvSeries = json_decode($this->tmdbService->getTv($series->getSerieId(), "fr"), true);

            if ($tvSeries) {
                if ($tvSeries['status'] === 'Ended' || $tvSeries['status'] === 'Canceled') {
                    if ($series->getStatus() != $tvSeries['status']) {
                        $series->setStatus($tvSeries['status']);
                        $this->serieRepository->save($series, true);
                    }
                    continue;
                }

                $this->serieFrontController->getLastSeasonAndEpisodes($tvSeries, $series);

                $count++;
            } else {
                $message = 'TV Series not found: ' . $series->getSerieId() . ' - ' . $series->getName();
                $io->error($message);
                $report[] = sprintf("%d: %s - %s", $series->getId(), $series->getName(), $message);
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

        $line = sprintf("Done. %d series updated, %d error%s", $count, $error, $error > 1 ? 's' : '');
        $io->success($line);

        $now = $this->dateService->newDateImmutable('now', 'Europe/Paris');
        $line = 'Season & episode infos Command ended at ' . $now->format('Y-m-d H:i:s');
        $io->writeln($line);

        return Command::SUCCESS;
    }
}
