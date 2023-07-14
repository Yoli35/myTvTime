<?php

namespace App\Command;

use App\Controller\SerieController;
use App\Repository\SerieRepository;
use App\Repository\SerieViewingRepository;
use App\Repository\UserRepository;
use App\Service\DateService;
use App\Service\TMDBService;
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
        private readonly DateService             $dateService,
        private readonly SerieController         $serieController,
        private readonly SerieRepository         $serieRepository,
        private readonly SerieViewingRepository  $serieViewingRepository,
        private readonly UserRepository          $userRepository,
        private readonly TMDBService             $tmdbService,
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

        if ($userId && $serieId) {
            $user = $this->userRepository->find($userId);
            $serie = $this->serieRepository->find($serieId);
            $serieViewings = $this->serieViewingRepository->findBy(['user' => $user, 'serie' => $serie]);
        } else {
            $serieViewings = $this->serieViewingRepository->findAll();
        }
        $count = 0;

        foreach ($serieViewings as $serieViewing) {
            $serie = $serieViewing->getSerie();
            $io->writeln($serie->getName() . ' (' . $serie->getId() . ') for user ' . $serieViewing->getUser()->getUsername() . ' (' . $serieViewing->getUser()->getId() . ')');

            // Si le dernier épisode de la série a été vu depuis plus de 2 ans, on ne fait rien, puisqu'il est probable que la série soit terminée
            $lastSeasonViewing = $this->serieController->getSeasonViewing($serieViewing, $serieViewing->getNumberOfSeasons());
            if ($lastSeasonViewing === null) {
                $io->writeln([str_repeat("*•", 40), '    Last season viewing is null, skipping', str_repeat("*•", 40)]);
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
                        continue;
                    }
                }
                $airDate = $lastEpisodeViewing->getAirDate();
                if ($airDate !== null) {
                    $diff = $now->diff($airDate);
                    if ($diff->y >= 2) {
                        $io->writeln([str_repeat("*•", 40), '    Last episode aired more than 2 years ago, skipping', str_repeat("*•", 40)]);
                        continue;
                    }
                }
            }

            $tvSeries = json_decode($this->tmdbService->getTv($serie->getSerieId(), "fr_FR"), true);

            if ($tvSeries) {
//                if ($tvSeries['next_episode_to_air'] === null) {
//                    $serieViewing->setNextEpisodeToAir(null);
//                    $io->writeln('    Next episode to air: none');
//                } else {
//                    $nextEpisode = $tvSeries['next_episode_to_air'];
//                    $nextEpisodeNumber = $nextEpisode['episode_number'];
//                    $nextSeasonNumber = $nextEpisode['season_number'];
//                    $episodeViewing = $this->serieController->getSeasonViewing($serieViewing, $nextSeasonNumber)?->getEpisodeByNumber($nextEpisodeNumber);
//                    $serieViewing->setNextEpisodeToAir($episodeViewing);
//                    $io->writeln('    Next episode to air: ' . $nextSeasonNumber . 'x' . $nextEpisodeNumber);
//                }
//
//                $serieViewing->setNextEpisodeToWatch(null);
//                foreach ($serieViewing->getSeasons() as $seasonViewing) {
//                    $serieViewing->setNextEpisodeToWatch(null);
//                    if ($seasonViewing->getSeasonNumber() > 0 && !$seasonViewing->isSeasonCompleted()) {
//                        $episodeCount = $seasonViewing->getEpisodeCount();
//                        foreach ($seasonViewing->getEpisodes() as $episodeViewing) {
//                            if ($episodeViewing->getViewedAt() === null) {
//                                $serieViewing->setNextEpisodeToWatch($episodeViewing);
//                                $io->writeln('    Next episode to watch: ' . $seasonViewing->getSeasonNumber() . 'x' . $episodeViewing->getEpisodeNumber());
//                                break 2;
//                            } else {
//                                if ($episodeViewing->getEpisodeNumber() === $episodeCount) {
//                                    $seasonViewing->setSeasonCompleted(true);
//                                    $io->writeln('    Season ' . $seasonViewing->getSeasonNumber() . ' completed');
//                                }
//                            }
//                        }
//                    }
//                }
//                $this->serieViewingRepository->save($serieViewing, true);
//                $this->serieController->setNextEpisode($tvSeries, $serieViewing, true);
                $this->serieController->updateSerieViewing($serieViewing, $tvSeries, $serie, true);
                $io->writeln($this->serieController->messages);
                $count++;
            } else {
                $io->error('    TV Series not found');
            }
        }

        $io->success('Done. ' . $count . ' series updated');

        return Command::SUCCESS;
    }
}
