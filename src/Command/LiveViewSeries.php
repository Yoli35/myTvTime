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
    name: 'app:live-view-series',
    description: 'Adjusts series viewing date to episode air date',
)]
class LiveViewSeries extends Command
{
    public function __construct(
        private readonly EpisodeViewingRepository $episodeViewingRepository,
        private readonly SerieRepository         $serieRepository,
        private readonly SerieViewingRepository  $serieViewingRepository,
        private readonly UserRepository          $userRepository,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('userid', InputArgument::REQUIRED, 'User\'s Id')
            ->addArgument('id', InputArgument::REQUIRED, 'Serie\'s Id')
            ->setHelp('Adjusts series viewing date to episode air date. Arguments: userid id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $serieId = $input->getArgument('id');
        $userId = $input->getArgument('userid');

        $user = $this->userRepository->find($userId);
        $serie = $this->serieRepository->find($serieId);

        $serieViewing = $this->serieViewingRepository->findOneBy(['serie' => $serie, 'user' => $user]);

        if (!$serieViewing) {
            $io->error('No serie viewing found for this user and serie.');
            return Command::FAILURE;
        }
        foreach ($serieViewing->getSeasons() as $seasonViewing) {
            foreach ($seasonViewing->getEpisodes() as $episodeViewing) {
                $episodeViewing->setViewedAt($episodeViewing->getAirDate());
                $this->episodeViewingRepository->save($episodeViewing);
                $io->writeln('Episode ' . $episodeViewing->getEpisodeNumber() . ' of season ' . $episodeViewing->getSeason()->getSeasonNumber() . ' of serie ' . $serie->getName() . ' has been updated.');
            }
            $this->episodeViewingRepository->flush();

        }

        $io->success('Done.');

        return Command::SUCCESS;
    }
}
