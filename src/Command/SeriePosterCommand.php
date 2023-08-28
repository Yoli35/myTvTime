<?php

namespace App\Command;

use App\Controller\SerieController;
use App\Entity\SerieBackdrop;
use App\Entity\SeriePoster;
use App\Repository\SerieBackdropRepository;
use App\Repository\SeriePosterRepository;
use App\Repository\SerieRepository;
use App\Service\DateService;
use App\Service\ImageConfiguration;
use App\Service\TMDBService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:serie-poster',
    description: 'Collect serie poster from serie and add it to serie_poster table',
)]
class SeriePosterCommand extends Command
{
    public function __construct(
        private readonly DateService             $dateService,
        private readonly ImageConfiguration      $imageConfiguration,
        private readonly LoggerInterface         $logger,
        private readonly SerieBackdropRepository $serieBackdropRepository,
        private readonly SerieController         $serieController,
        private readonly SeriePosterRepository   $seriePosterRepository,
        private readonly SerieRepository         $serieRepository,
        private readonly TMDBService             $tmdbService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::OPTIONAL, 'Serie ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $id = $input->getArgument('id');

        if ($id) {
            $series = $this->serieRepository->findBy(['id' => $id]);
        } else {
            $series = $this->serieRepository->findAll();
        }
        $imgConfig = $this->imageConfiguration->getConfig();

        $now = $this->dateService->newDateImmutable('now', 'Europe/Paris');
        $this->logger->info('Command started at ' . $now->format('Y-m-d H:i:s'));
        $io->writeln('Command started at ' . $now->format('Y-m-d H:i:s'));

        foreach ($series as $serie) {
            $io->writeln([
                'Serie ' . $serie->getId() . ' ' . $serie->getName(),
                '    Poster ' . $serie->getPosterPath(),
                '    Number of posters: ' . count($serie->getSeriePosters()->toArray()),
            ]);

            $poster = $serie->getPosterPath();
            $seriePosters = array_map(fn($poster) => $poster->getPosterPath(), $serie->getSeriePosters()->toArray());

            if ($poster && !in_array($poster, $seriePosters)) {
                $seriePoster = new SeriePoster($serie, $poster);
                $this->seriePosterRepository->save($seriePoster);
                $serie->addSeriePoster($seriePoster);
                $this->serieRepository->save($serie);
                $io->writeln('    Added (poster)');
                $seriePosters = array_map(fn($poster) => $poster->getPosterPath(), $serie->getSeriePosters()->toArray());
            } else {
                $io->writeln('    Skipped (poster)');
            }

            $serieBackdrop = $serie->getBackdropPath();
            $serieBackdrops = array_map(fn($backdrop) => $backdrop->getBackdropPath(), $serie->getSerieBackdrops()->toArray());

            if ($serieBackdrop && !in_array($serieBackdrop, $serieBackdrops)) {
                $serieBackdrop = new SerieBackdrop($serie, $serieBackdrop);
                $this->serieBackdropRepository->save($serieBackdrop);
                $serie->addSerieBackdrop($serieBackdrop);
                $this->serieRepository->save($serie);
                $io->writeln('    Added (backdrop)');
                $serieBackdrops = array_map(fn($backdrop) => $backdrop->getBackdropPath(), $serie->getSerieBackdrops()->toArray());
            } else {
                $io->writeln('    Skipped (backdrop)');
            }

            $tv = json_decode($this->tmdbService->getTv($serie->getSerieId(), "fr"), true);

            if ($tv) {
                if ($serie->getPosterPath() !== $tv['poster_path']) {
                    $io->writeln('    New poster: ' . $tv['poster_path']);

                    if ($tv['poster_path'] && !in_array($tv['poster_path'], $seriePosters)) {
                        $seriePoster = new SeriePoster($serie, $tv['poster_path']);
                        $this->seriePosterRepository->save($seriePoster, true);
                        $serie->addSeriePoster($seriePoster);
                        $io->writeln('     -> Added to poster list 😎 😎 😎');
                        $this->serieController->savePoster($tv['poster_path'], $imgConfig['url'] . $imgConfig['poster_sizes'][3]);
                    } else {
                        $io->writeln('     -> Already in poster list');
                    }

                    $serie->setPosterPath($tv['poster_path']);
                    $this->serieRepository->save($serie, true);
                } else {
                    $io->writeln('    Same poster');
                }
                if ($serie->getBackdropPath() !== $tv['backdrop_path']) {
                    $io->writeln('    New backdrop: ' . $tv['backdrop_path']);

                    if ($tv['backdrop_path'] && !in_array($tv['backdrop_path'], $serieBackdrops)) {
                        $serieBackdrop = new SerieBackdrop($serie, $tv['backdrop_path']);
                        $this->serieBackdropRepository->save($serieBackdrop, true);
                        $serie->addSerieBackdrop($serieBackdrop);
                        $io->writeln('     -> Added to backdrop list 😎 😎 😎');
                    } else {
                        $io->writeln('     -> Already in backdrop list');
                    }

                    $serie->setBackdropPath($tv['backdrop_path']);
                    $this->serieRepository->save($serie, true);
                } else {
                    $io->writeln('    Same backdrop');
                }

                $io->writeln('');
            } else {
                $io->error('    TV Series not found 😡 😡 😡');
            }
        }

        $this->serieRepository->flush();

        $now = $this->dateService->newDateImmutable('now', 'Europe/Paris');
        $this->logger->info('Command ended at ' . $now->format('Y-m-d H:i:s'));
        $io->writeln('Command ended at ' . $now->format('Y-m-d H:i:s'));

        return Command::SUCCESS;
    }
}
