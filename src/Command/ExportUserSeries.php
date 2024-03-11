<?php

namespace App\Command;

use App\Repository\FavoriteRepository;
use App\Repository\SerieRepository;
use App\Repository\SerieViewingRepository;
use App\Repository\UserRepository;
use App\Service\DateService;
use App\Service\TMDBService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[AsCommand(
    name: 'app:export:user:series',
    description: 'Export user series',
)]
class ExportUserSeries extends Command
{
    public function __construct(
        private readonly DateService            $dateService,
        private readonly FavoriteRepository     $favoriteRepository,
        private readonly SerieRepository        $serieRepository,
        private readonly SerieViewingRepository $serieViewingRepository,
        private readonly TMDBService            $tmdbApi,
        private readonly UserRepository         $userRepository,
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

        if (!$userId) {
            $userId = $io->ask('User\'s Id');
            if (!$userId) {
                $io->error('User\'s Id is required');
                return Command::FAILURE;
            }
        }
        $user = $this->userRepository->find($userId);
        $slugger = new AsciiSlugger();

        if ($userId && $serieId) {
            $serie = $this->serieRepository->find($serieId);
            $serieViewings = $this->serieViewingRepository->findBy(['user' => $user, 'serie' => $serie]);
        } else {
            $serieViewings = $this->serieViewingRepository->findBy(['user' => $user]);
        }
        $count = 0;
        $episodeCount = 0;
        $now = $this->dateService->newDateImmutable('now', 'Europe/Paris');
        $io->writeln('Export Command started at ' . $now->format('Y-m-d H:i:s'));
        $pwd = getcwd();
        $io->writeln('Current working directory: ' . $pwd);
        $allExportedSeries = [];
        $allExportedUserSeries = [];

        $deviceType = [
            'tv' => 1,
            'phone' => 2,
            'tablet' => 3,
            'laptop' => 4,
            'desktop' => 5,
        ];

        foreach ($serieViewings as $serieViewing) {

            $serie = $serieViewing->getSerie();
            $line = sprintf("%d - %s", $serie->getId(), $serie->getName());
            $io->writeln($line);

            $exportedSeries = [];

            // Exported series fields
            //    string $name
            //    string $posterPath
            //    int $tmdbId
            //    string $originalName
            //    string $slug
            //    string $overview
            //    string $backdropPath
            //    DateTimeImmutable $firstDateAir
            //    DateTimeImmutable $createdAt
            //    DateTimeImmutable $updatedAt
            //    int $visitNumber
            //    Collection $seriesLocalizedNames;
            $exportedSeries['tmdbId'] = $serie->getSerieId();
            $exportedSeries['name'] = $serie->getName();
            $exportedSeries['posterPath'] = $serie->getPosterPath();
            $exportedSeries['originalName'] = $serie->getOriginalName();
            $exportedSeries['slug'] = $slugger->slug($serie->getName());
            $exportedSeries['overview'] = $serie->getOverview();
            $exportedSeries['backdropPath'] = $serie->getBackdropPath();
            $exportedSeries['firstDateAir'] = $serie->getFirstDateAir();
            $exportedSeries['createdAt'] = $serie->getCreatedAt();
            $exportedSeries['updatedAt'] = $serie->getUpdatedAt();
            $exportedSeries['visitNumber'] = 0;
            $localizedName = $serie->getSerieLocalizedName();
            $exportedSeries['seriesLocalizedNames'] = $localizedName ? ['name' => $localizedName->getName(), 'locale' => $localizedName->getLocale()] : [];
            $allExportedSeries[] = $exportedSeries;

            // Exported user series fields
            //    ?User $user                           => user'id
            //    ?Series $series                       => TMDB serie'id
            //    ?\DateTimeImmutable $addedAt          => serieViewing's createdAt
            //    ?\DateTimeImmutable $lastWatchAt      => last episode viewing's viewedAt ($viewedAt not null)
            //    ?int $lastSeason                      => last episode viewing's season number
            //    ?int $lastEpisode                     => last episode viewing's episode number
            //    ?int $viewedEpisodes                  => count of viewed episodes
            //    ?float $progress                      => percentage of viewed episodes
            //    ?bool $favorite                       => f.type = 'serie' and f.user_id = user.id and f.media_id = series.id ? true : false
            //    ?int $rating                          => 0
            //    Collection $userEpisodes              => episodeViewing's id, season number, episode number, viewedAt (not null), providerId, deviceId, vote, quickWatchDay, quickWatchWeek ordered by viewedAt

            $favorite = $this->favoriteRepository->findOneBy(['type' => 'serie', 'userId' => $userId, 'mediaId' => $serie->getId()]);
            // Dernière saison vue
            $lastSeason = null;
            // Dernier épisode vu
            $lastEpisode = null;
            // Episodes vus
            $userEpisodes = [];
            $viewedAt = null;
            foreach ($serieViewing->getSeasons() as $seasonViewing) {
                $io->writeln(sprintf("Season %d", $seasonViewing->getSeasonNumber()));
                $tmdbSeason = json_decode($this->tmdbApi->getTvSeason($serie->getSerieId(), $seasonViewing->getSeasonNumber(), 'en'), true);
                if (!$tmdbSeason) {
                    $io->writeln(sprintf("[ERROR] Season %d not found", $seasonViewing->getSeasonNumber()));
                    continue; // Popcorn, la série a été retirée de TMDB
                }
                foreach ($seasonViewing->getEpisodes() as $episodeViewing) {
                    if (!$episodeViewing->getViewedAt()) {
                        continue; // on scanne tous les épisodes de la saison au cas où il y ait des épisodes non vus
                    }
                    $lastSeason = $seasonViewing->getSeasonNumber();
                    $lastEpisode = $episodeViewing->getEpisodeNumber();

                    $tmdbEpisode = $this->getEpisode($tmdbSeason, $lastEpisode);
                    if (!$tmdbEpisode) {
                        $io->writeln(sprintf("[ERROR] Episode S%02dE%02d not found", $lastSeason, $lastEpisode));
                        continue; // Comment, pourquoi le not found ?!?
                    }
                    $episodeId = $tmdbEpisode['id'];

                    $airDate = $episodeViewing->getAirDate();
                    $viewedAt = $episodeViewing->getViewedAt();
                    if ($airDate) {
                        $diff = $viewedAt->diff($airDate);
                        $quickWatchDay = $diff->days < 1;
                        $quickWatchWeek = $diff->days < 7;
                    } else {
                        $quickWatchDay = null;
                        $quickWatchWeek = null;
                    }
                    $userEpisodes[] = [
                        'episodeId' => $episodeId,
                        'seasonNumber' => $lastSeason,
                        'episodeNumber' => $lastEpisode,
                        'watchAt' => $viewedAt,
                        'providerId' => $episodeViewing->getNetworkId(),
                        'deviceId' => $episodeViewing->getDeviceType() ? $deviceType[$episodeViewing->getDeviceType()] : null,
                        'vote' => $episodeViewing->getVote(),
                        'quickWatchDay' => $quickWatchDay,
                        'quickWatchWeek' => $quickWatchWeek,
                    ];
                    $episodeCount++;
                }
            }
            $allExportedUserSeries[] = [
                'user' => $user->getId(),
                'seriesName' => $localizedName ? $localizedName->getName() : $serie->getName(),
                'tmdbId' => $serie->getSerieId(),
                'addedAt' => $serieViewing->getCreatedAt(),
                'lastWatchAt' => $viewedAt,
                'lastSeason' => $lastSeason,
                'lastEpisode' => $lastEpisode,
                'viewedEpisodes' => $serieViewing->getViewedEpisodes(),
                'progress' => 100 * $serieViewing->getViewedEpisodes() / $serie->getNumberOfEpisodes(),
                'favorite' => (bool)$favorite,
                'rating' => 0,
                'userEpisodes' => $userEpisodes,
            ];
            $count++;
        }


        $jsonFile = fopen('exportedSeries.json', 'w');
        fwrite($jsonFile, json_encode([
            'exportedAt' => $now->format('Y-m-d H:i:s'),
            'seriesCount' => $count,
            'episodeCount' => $episodeCount,
            'series' => $allExportedSeries,
            'userSeries' => $allExportedUserSeries,
        ], JSON_PRETTY_PRINT));
        fclose($jsonFile);

        $line = sprintf("Done. %d series exported", $count);
        $io->success($line);

        $now = $this->dateService->newDateImmutable('now', 'Europe/Paris');
        $line = 'Export Command ended at ' . $now->format('Y-m-d H:i:s');
        $io->writeln($line);

        return Command::SUCCESS;
    }

    private function getEpisode($tmdbSeason, $episodeNumber): ?array
    {
        foreach ($tmdbSeason['episodes'] as $tmdbEpisode) {
            if ($tmdbEpisode['episode_number'] == $episodeNumber) {
                return $tmdbEpisode;
            }
        }
        return null;
    }
}
