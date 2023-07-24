<?php

namespace App\Command;

use App\Controller\SerieController;
use App\Repository\SerieViewingRepository;
use App\Service\TMDBService;
use DateTime;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:is-series-completed',
    description: 'Check if every viewed series is completed',
)]
class IsSerieCompletedCommand extends Command
{
    public function __construct(private readonly SerieViewingRepository $serieViewingRepository,
                                private readonly TMDBService            $TMDBService,
                                private readonly SerieController        $serieController)
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
            if ($serieViewing->getId() >= 518) {
                $user = $serieViewing->getUser();
                $serie = $serieViewing->getSerie();
                $seasons = $serieViewing->getSeasons();

                $standing = $this->TMDBService->getTv($serie->getSerieId(), 'fr');
                $tmdbSerie = json_decode($standing, true);
                $specialEpisodes = count($tmdbSerie['seasons']) > $tmdbSerie['number_of_seasons'];

                if ($serieViewing->getCreatedAt() == null) {
                    $io->comment('Setting "createdAt"');
                    $serieViewing->setCreatedAt(new DateTimeImmutable());
                }
                if ($serieViewing->getModifiedAt() == null) {
                    $io->comment('Setting "modifiedAt"');
                    $serieViewing->setModifiedAt(new DateTime());
                }
                $io->comment('Updating "' . $serie->getName() . '" (' . $serie->getId() . ',' . $serieViewing->getId() . ') for user ' . $user->getUsername() ?: $user->getEmail());

                $seasonCompleted = [];
                if ($seasons->count()) {
                    foreach ($seasons as $season) {
                        if ($season->getSeasonNumber()) {
                            $io->comment('    Season number: ' . $season->getSeasonNumber() . ' - Episode Count: ' . $season->getEpisodeCount() . ' - Season completed: ' . ($season->isSeasonCompleted() ? 'Yes' : 'No'));
                            $seasonCompleted[] = $season->isSeasonCompleted();
                        }
                    }
                    $serieCompleted = !in_array(false, $seasonCompleted, true);
                    $io->comment('    => Serie completed: ' . ($serieCompleted ? 'Yes' : 'No'));
                    if ($serieViewing->isSerieCompleted() != $serieCompleted) {
                        $serieViewing->setSerieCompleted($serieCompleted);
                        $this->serieViewingRepository->save($serieViewing, true);
                        $io->success('    => Serie updated');
                    }
                } else {
                    $serieViewing = $this->serieController->createSerieViewingContent($serieViewing, $tmdbSerie);
                    $this->serieViewingRepository->save($serieViewing, true);
                }
                $count++;
            }
        }

        $io->success('Done. ' . $count . ' series updated');

        return Command::SUCCESS;
    }
}
