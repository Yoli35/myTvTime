<?php

namespace App\Command;

use App\Repository\EpisodeViewingRepository;
use App\Repository\SerieRepository;
use App\Repository\SerieViewingRepository;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:episode:number-of-view',
    description: 'Adjusts episode number of view according to viewed_at field',
)]
class EpisodeNumberOfView extends Command
{
    public function __construct(
        private readonly EpisodeViewingRepository $episodeViewingRepository,
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
        $episodesViewing = $this->episodeViewingRepository->findAll();
        $progressBar = $io->createProgressBar(count($episodesViewing));
        $progressBar->start();

        foreach ($episodesViewing as $episodeViewing) {
            if ($episodeViewing->getViewedAt()) {
                $episodeViewing->setNumberOfView(1);
            } else {
                $episodeViewing->setNumberOfView(0);
            }
            $this->episodeViewingRepository->save($episodeViewing);

            if ($index++ % 100 === 0) {
                $this->episodeViewingRepository->flush();
            }
            $progressBar->advance();
        }
        $progressBar->finish();

        $io->success('Done.');

        return Command::SUCCESS;
    }
}
