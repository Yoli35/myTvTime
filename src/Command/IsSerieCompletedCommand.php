<?php

namespace App\Command;

use App\Controller\SerieController;
use App\Entity\SeasonViewing;
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
    name: 'app:is-serie-completed',
    description: 'Check if every viewed serie is completed',
)]
class IsSerieCompletedCommand extends Command
{
    public function __construct(private readonly SerieViewingRepository $repository,
        /* private readonly SeasonViewingRepository $seasonViewingRepository,*/
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

        $serieViewingRepository = $this->repository;
        $serieViewings = $this->repository->findAll();
        $count = 0;

        foreach ($serieViewings as $serieViewing) {
            if ($serieViewing->getId() > 118) {
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
                if ($serieViewing->isSpecialEpisodes() != $specialEpisodes) {
                    $io->comment('Setting "specialEpisodes"');
                    $serieViewing->setSpecialEpisodes($specialEpisodes);
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
                        $serieViewingRepository->save($serieViewing, true);
                        $io->success('    => Serie updated');
                    }
                } else {
                    $serieViewing = $this->serieController->createSerieViewingContent($serieViewing, $tmdbSerie);
                    $serieViewingRepository->save($serieViewing, true);
                }
                $count++;
            }
        }

        $io->success('Done. ' . $count . ' serie' . ($count > 1 ? 's' : '') . ' updated');

        return Command::SUCCESS;
    }
}