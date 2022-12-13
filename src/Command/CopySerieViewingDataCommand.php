<?php

namespace App\Command;

use App\Repository\EpisodeViewingRepository;
use App\Repository\SeasonViewingRepository;
use App\Repository\SerieViewingRepository;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:copy-serie-viewing-data',
    description: 'Copy the viewing array to the viewing episodes (db)',
)]
class CopySerieViewingDataCommand extends Command
{
    public function __construct(private readonly SerieViewingRepository   $serieViewingRepository,
                                private readonly EpisodeViewingRepository $episodeViewingRepository)
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
            if ($serieViewing->getId() > 74) {
                $user = $serieViewing->getUser();
                $serie = $serieViewing->getSerie();
                $data = $serieViewing->getViewing();
                $output->writeln('Serie: ' . $serie->getName() . '(' . $serieViewing->getId() . ') - for user: ' . $user->getUsername() ?: $user->getEmail());

                foreach ($data as $seasonData) {
                    $seasonViewing = $serieViewing->getSeasonByNumber($seasonData['season_number']);
                    if ($seasonViewing) {
                        $episodeData = $seasonData['episodes'];
                        $episodeStr = '';
                        for ($i = 0; $i < $seasonData['episode_count']; $i++) {
                            $episodeStr .= '[' . ($episodeData[$i] ? '*' : '-') . ']';
                            if ($episodeData[$i]) {
                                $episodeViewing = $seasonViewing->getEpisodeByNumber($i + 1);
                                $episodeViewing->setViewedAt(new DateTimeImmutable());
                                $this->episodeViewingRepository->save($episodeViewing, true);
                            }
                        }
                        $output->writeln([
                            'Season number: ' . ($seasonData['season_number'] ?: 'Special episodes'),
                            '    Air date: ' . ($seasonData['air_date'] ?: 'None'),
                            '    Episode count: ' . $seasonData['episode_count'],
                            '    Season completed: ' . ($seasonData['season_completed'] ? 'Yes' : 'No'),
                            '    Episodes: ' . $episodeStr
                        ]);
                    }
                }
                $output->writeln('');
                $count++;
            }
        }

        $io->success('Done. ' . $count . ' serie' . ($count > 1 ? 's' : '') . ' updated');

        return Command::SUCCESS;
    }
}
