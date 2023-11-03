<?php

namespace App\Command;

use App\Repository\SerieRepository;
use App\Service\TMDBService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:series:country',
    description: 'Set origin country for series',
)]
class SeriesCountry extends Command
{
    public function __construct(
        private readonly SerieRepository $serieRepository,
        private readonly TMDBService     $tmdbService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Adjusts episode number of view according to viewed_at field');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $index = 0;
        $seriesAll = $this->serieRepository->findAll();
        $progressBar = $io->createProgressBar(count($seriesAll));
        $progressBar->start();

        foreach ($seriesAll as $series) {
            if ($series->getId() < 510) {
                $progressBar->advance();
                continue;
            }
            $tmdbId = $series->getSerieId();
            $tv = json_decode($this->tmdbService->getTv($tmdbId, 'fr'), true);
            if ($tv) {
                $series->setOriginCountry($tv['origin_country'] ?? []);
                $this->serieRepository->save($series);
                if (++$index % 50 === 0) {
                    $this->serieRepository->flush();
                }
            }
            $progressBar->advance();
        }
        $this->serieRepository->flush();
        $progressBar->finish();

        $io->success('Done.');

        return Command::SUCCESS;
    }
}
