<?php

namespace App\Command;

use App\Repository\SeasonViewingRepository;
use App\Repository\SerieViewingRepository;
use App\Service\TMDBService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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
        private readonly SeasonViewingRepository $seasonViewingRepository,
        private readonly SerieViewingRepository  $serieViewingRepository,
        private readonly TMDBService             $tmdbService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $serieViewings = $this->serieViewingRepository->findAll();
        $count = 0;

        foreach ($serieViewings as $serieViewing) {
            $serie = $serieViewing->getSerie();
            $tvSeries = json_decode($this->tmdbService->getTv($serie->getSerieId(), "fr_FR"), true);

            if ($tvSeries) {
                $io->writeln($serie->getName() . ' (' . $serie->getId() . ')');
                if ($tvSeries['next_episode_to_air'] === null) {
                    $serieViewing->setNextEpisodeToAir(null);
                    $this->serieViewingRepository->save($serieViewing);
                    $io->writeln('    Next episode to air: none');
                } else {
                    $nextEpisode = $tvSeries['next_episode_to_air'];
                    $nextEpisodeNumber = $nextEpisode['episode_number'];
                    $nextSeasonNumber = $nextEpisode['season_number'];
                    $serieViewing->setNextEpisodeToAir(null);
                    foreach ($serieViewing->getSeasons() as $seasonViewing) {
                        if ($seasonViewing->getSeasonNumber() === $nextSeasonNumber) {
                            foreach ($seasonViewing->getEpisodes() as $episodeViewing) {
                                if ($episodeViewing->getEpisodeNumber() === $nextEpisodeNumber) {
                                    $serieViewing->setNextEpisodeToAir($episodeViewing);
                                    $io->writeln('    Next episode to air: ' . $nextSeasonNumber . 'x' . $nextEpisodeNumber);
                                }
                            }
                        }
                    }
                    $this->serieViewingRepository->save($serieViewing);
                }

                $serieViewing->setNextEpisodeToWatch(null);
                foreach ($serieViewing->getSeasons() as $seasonViewing) {
                    $serieViewing->setNextEpisodeToWatch(null);
                    if ($seasonViewing->getSeasonNumber() > 0 && !$seasonViewing->isSeasonCompleted()) {
                        $episodeCount = $seasonViewing->getEpisodeCount();
                        foreach ($seasonViewing->getEpisodes() as $episodeViewing) {
                            if ($episodeViewing->getViewedAt() === null) {
                                $serieViewing->setNextEpisodeToWatch($episodeViewing);
                                $this->serieViewingRepository->save($serieViewing);
                                $io->writeln('    Next episode to watch: ' . $seasonViewing->getSeasonNumber() . 'x' . $episodeViewing->getEpisodeNumber());
                                break 2;
                            } else {
                                if ($episodeViewing->getEpisodeNumber() === $episodeCount) {
                                    $seasonViewing->setSeasonCompleted(true);
                                    $this->seasonViewingRepository->save($seasonViewing);
                                    $io->writeln('    Season ' . $seasonViewing->getSeasonNumber() . ' completed');
                                }
                            }
                        }
                    }
                }
                $this->serieViewingRepository->flush();
                $count++;
            }
        }

        $io->success('Done. ' . $count . ' series updated');

        return Command::SUCCESS;
    }
}
