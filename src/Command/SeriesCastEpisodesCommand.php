<?php

namespace App\Command;

use App\Controller\SerieController;
use App\Repository\SerieCastRepository;
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
    name: 'app:series:cast:episodes',
    description: 'Updating the serie_cast table with the episodes in which the actors appear',
)]
class SeriesCastEpisodesCommand extends Command
{
    public function __construct(
        private readonly DateService         $dateService,
        private readonly LoggerInterface     $logger,
        private readonly SerieCastRepository $serieCastRepository,
        private readonly SerieController     $serieController,
        private readonly SerieRepository     $serieRepository,
        private readonly TMDBService         $tmdbService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'number of serie casts to process')
            ->addOption('offset', 'o', InputOption::VALUE_REQUIRED, 'offset of serie casts to process')
            ->addOption('serie-id', 's', InputOption::VALUE_REQUIRED, 'Serie id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);


        $now = $this->dateService->newDateImmutable('now', 'Europe/Paris');
        $this->logger->info('SeriesCastEpisodes Command started at ' . $now->format('Y-m-d H:i:s'));
        $io->writeln('SeriesCastEpisodes Command started at ' . $now->format('Y-m-d H:i:s'));

        if ($input->getOption('serie-id')) {
            $serieId = $input->getOption('serie-id');
            $seriesArr = $this->serieRepository->findBy(['id' => $serieId]);
        } elseif ($input->getOption('limit') && $input->getOption('offset')) {
            $limit = $input->getOption('limit');
            $offset = $input->getOption('offset');
            $seriesArr = $this->serieRepository->findBy([], null, $limit, $offset);
        } else {
            $seriesArr = $this->serieRepository->findAll();
            $serieCastCount = $this->serieCastRepository->countSerieCast();
            $confirm = $io->ask('Update all the serie casts (' . count($seriesArr) . ' series, ' . $serieCastCount . ' roles/characters)?', 'yes');
            if ($confirm !== 'yes') {
                $io->warning('Confirmation not given. Exiting.');
                return Command::SUCCESS;
            }
        }

        $progressBar = $io->createProgressBar(count($seriesArr));
        $progressBar->start();

        foreach ($seriesArr as $series) {
            $status = $series->getStatus();
            if ($status === 'Ended' || $status === 'Canceled') {
                $progressBar->advance();
                continue;
            }
            $io->text('Collecting episodes for series: ' . $series->getName());
            $tv = json_decode($this->tmdbService->getTv($series->getSerieId(), 'fr-FR', ['credits']), true);
            if (!$tv) {
                $io->error('Error while retrieving TV data for serie: ' . $series->getName());
                continue;
            }

            $this->serieController->updateTvCast($tv, $series);
            $io->text($this->serieController->messages);

            $progressBar->advance();
        }
        $progressBar->finish();
        $io->newLine(2);

        $now = $this->dateService->newDateImmutable('now', 'Europe/Paris');
        $this->logger->info('SeriesCastEpisodes Command ended at ' . $now->format('Y-m-d H:i:s'));
        $io->writeln('SeriesCastEpisodes Command ended at ' . $now->format('Y-m-d H:i:s'));

        return Command::SUCCESS;
    }
}
