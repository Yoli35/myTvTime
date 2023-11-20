<?php

namespace App\Command;

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
        private readonly EpisodeRepository $episodeRepository,
        private readonly DateService       $dateService,
        private readonly SeasonRepository  $seasonRepository,
        private readonly SerieRepository   $serieRepository,
        private readonly TMDBService       $tmdbService,
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
        $io->writeln('Next episode to air Command started at ' . $now->format('Y-m-d H:i:s'));

        foreach ($seriesList as $series) {

            if ($series->getId() >= 50) {
                continue;
            }

            $line = sprintf("%s (%d)", $series->getName(), $series->getId());
            $io->writeln($line);

            $tvSeries = json_decode($this->tmdbService->getTv($series->getSerieId(), "fr"), true);

            if ($tvSeries) {
                $numberOfSeasons = $tvSeries['number_of_seasons'];

                for ($seasonNumber = 1; $seasonNumber <= $numberOfSeasons; $seasonNumber++) {
                    $tvSeason = json_decode($this->tmdbService->getTvSeason($series->getSerieId(), $seasonNumber, "fr"), true);

                    $season = $this->seasonRepository->findOneBy(['series' => $series, 'seasonNumber' => $seasonNumber]);

                    if (!$season) {
                        $season = new Season();
                    }
                    $season->set_Id($tvSeason['_id']);
                    $season->setAirDate($this->dateService->newDateImmutable($tvSeason['air_date'], 'Europe/Paris'));
                    $season->setName($tvSeason['name']);
                    $season->setOverview($tvSeason['overview']);
                    $season->setPosterPath($tvSeason['poster_path']);
                    $season->setSeasonNumber($seasonNumber);
                    $season->setSeries($series);
                    $season->setTmdbId($tvSeason['id']);
                    $this->seasonRepository->save($season, true);

                    $tvEpisodes = $tvSeason['episodes'];
                    $episodeCount = count($tvEpisodes);
                    for ($i = 1; $i <= $episodeCount; $i++) {
                        $tvEpisode = $tvEpisodes[$i - 1];
                        $episode = $this->episodeRepository->findOneBy(['series' => $series, 'season' => $season, 'episodeNumber' => $i]);
                        if (!$episode) {
                            $episode = new Episode();
                        }
                        $episode->setAirDate($this->dateService->newDateImmutable($tvEpisode['air_date'], 'Europe/Paris'));
                        $episode->setEpisodeNumber($tvEpisode['episode_number']);
                        $episode->setName($tvEpisode['name']);
                        $episode->setOverview($tvEpisode['overview']);
                        $episode->setRuntime($tvEpisode['runtime']);
                        $episode->setSeason($season);
                        $episode->setSeasonNumber($tvEpisode['season_number']);
                        $episode->setSeries($series);
                        $episode->setStillPath($tvEpisode['still_path']);
                        $episode->setTmdbId($tvEpisode['id']);
                        $this->episodeRepository->save($episode, $i === $episodeCount);
                    }
                }

                $count++;
            } else {
                $message = 'TV Series not found';
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
        $line = 'Next episode to air Command ended at ' . $now->format('Y-m-d H:i:s');
        $io->writeln($line);

        return Command::SUCCESS;
    }
}
