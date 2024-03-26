<?php

namespace App\Command;

use App\Repository\FavoriteRepository;
use App\Repository\SerieRepository;
use App\Repository\SerieViewingRepository;
use App\Repository\UserRepository;
use App\Service\DateService;
use App\Service\TMDBService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[AsCommand(
    name: 'app:export:episode-names',
    description: 'Export substitute episode names',
)]
class ExportEpisodeName extends Command
{
    public function __construct(
        private readonly DateService            $dateService,
        private readonly ManagerRegistry        $registry,
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
            $series = $this->getOneSeries($userId, $serieId, $user->getPreferredLanguage() ??'fr');
        } else {
            $serieViewings = $this->serieViewingRepository->findBy(['user' => $user]);
            $series = $this->getSeries($userId, $user->getPreferredLanguage() ??'fr');
        }
        //dd($series);
        $count = 0;
        $episodeCount = 0;
        $seasonCount = 0;
        $substituteNames = [];

        foreach ($series as $s) {

            if ($s['localized_name'])
                $line = sprintf("%d - %s", $s['id'], $s['localized_name']);
            else
                $line = sprintf("%d - %s", $s['id'], $s['name']);
            $io->writeln($line);

            for ($i=1; $i<=$s['number_of_seasons']; $i++) {
                $io->writeln(sprintf("Season %d", $i));
                $seasonEpisodesWithSubstituteNames = array_values(array_filter($this->getSeasonEpisodes($userId, $s['id'], $i), fn($episode) => $episode['substitute_name']));

                 if (count($seasonEpisodesWithSubstituteNames) > 0) {
                     $seasonCount++;
                     $season = json_decode($this->tmdbApi->getTvSeason($s['tmdb_id'], $i, $user->getPreferredLanguage() ??'fr'), true);
                     $episodes = $season['episodes'];
                     foreach ($seasonEpisodesWithSubstituteNames as $episode) {
                         $episodeCount++;
                         $tmdbEpisodeId = $this->getEpisodeId($episode['episode_number'], $episodes);
                         $line = sprintf("S%02dE%02d - %d - %s", $i, $episode['episode_number'], $tmdbEpisodeId, $episode['substitute_name']);
                         $io->writeln($line);
                         $substituteNames[] = [
                             'serie_id' => $s['id'],
                             'season_number' => $i,
                             'episode_number' => $episode['episode_number'],
                             'tmdb_episode_id' => $tmdbEpisodeId,
                             'substitute_name' => $episode['substitute_name'],
                         ];
                     }
                } else {
                    $line = sprintf("No substitute names found for season %d", $i);
                    $io->writeln($line);
                }
            }

            $count++;
            $io->newLine();
        }

        $now = $this->dateService->newDateImmutable('now', 'Europe/Paris');

        $jsonFile = fopen('exportedSubstituteNames.json', 'w');
        fwrite($jsonFile, json_encode([
            'exportedAt' => $now->format('Y-m-d H:i:s'),
            'seriesCount' => $count,
            'episodeCount' => $episodeCount,
            'substituteNames' => $substituteNames,
        ], JSON_PRETTY_PRINT));
        fclose($jsonFile);

        $line = sprintf("Done. %d series processed", $count);
        $line .= sprintf(" and %d episodes", $episodeCount);
        $line .= sprintf(" in %d seasons", $seasonCount);
        $io->success($line);

        $now = $this->dateService->newDateImmutable('now', 'Europe/Paris');
        $line = 'Export Command ended at ' . $now->format('Y-m-d H:i:s');
        $io->writeln($line);

        return Command::SUCCESS;
    }

    public function getEpisodeId($episodeNumber, $season): int
    {
        foreach ($season as $episode) {
            if ($episode['episode_number'] == $episodeNumber) {
                return $episode['id'];
            }
        }
        return 0;
    }

    private function getSeries($userId, $locale): ?array
    {
        $sql = "SELECT s.`id` as id, s.`name` as name, sln.`name` as localized_name, s.`serie_id` as tmdb_id, s.`number_of_seasons` as `number_of_seasons` "
         .   "FROM `serie_viewing` sv "
         .   "LEFT JOIN `serie` s ON s.`id`=sv.`serie_id` "
         .   "LEFT JOIN `serie_localized_name` sln ON sln.`serie_id`=s.`id` AND sln.`locale`='$locale' "
         .   "WHERE sv.`user_id`=$userId";

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
    }

    private function getOneSeries($userId, $serieId, $locale): ?array
    {
        $sql = "SELECT s.`id` as id, s.`name` as name, sln.`name` as localized_name, s.`serie_id` as tmdb_id, s.`number_of_seasons` as `number_of_seasons` "
         .   "FROM `serie_viewing` sv "
         .   "LEFT JOIN `serie` s ON s.`id`=sv.`serie_id` "
         .   "LEFT JOIN `serie_localized_name` sln ON sln.`serie_id`=s.`id` AND sln.`locale`='$locale' "
         .   "WHERE sv.`user_id`=$userId AND sv.`serie_id`=$serieId";

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
    }

    private function getSeasonEpisodes($userId, $serieId, $season_number): ?array
    {
        $sql = "SELECT ep.`id` as id, ep.`episode_number` as episode_number, ep.`substitute_name` as substitute_name "
         .   "FROM `episode_viewing` ep "
         .   "INNER JOIN `serie_viewing` sv ON sv.`serie_id`=$serieId "
         .   "INNER JOIN `season_viewing` sev ON sev.`serie_viewing_id`=sv.`id` "
         .   "WHERE sv.`user_id`=$userId AND ep.`season_id`=sev.`id` AND sev.`season_number`=$season_number";

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
    }
}
