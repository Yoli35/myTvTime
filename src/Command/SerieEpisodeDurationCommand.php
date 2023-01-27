<?php

namespace App\Command;

use App\Controller\SerieFrontController;
use App\Repository\SerieRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:serie:episode:duration',
    description: 'Add a short description for your command',
)]
class SerieEpisodeDurationCommand extends Command
{
    public function __construct(private readonly SerieRepository      $serieRepository,
                                private readonly SerieFrontController $serieFrontController)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('serieId', InputArgument::OPTIONAL, 'Serie\'s Id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Collecting episode duration for all series');
        $id = $input->getArgument('serieId');
        if ($id) {
            $series = [$this->serieRepository->find($id)];
        }
        else {
            $series = $this->serieRepository->findAll();
        }

        foreach ($series as $serie) {
//            if (count($serie->getEpisodeDurations())) {
//                continue;
//            }
            $io->text('Collecting episode duration for serie: ' . $serie->getName());
            $serie->setEpisodeDurations($this->serieFrontController->collectEpisodeDurations($serie));
            $this->serieRepository->save($serie);
        }
        $this->serieRepository->flush();

        $io->success('Done.');

        return Command::SUCCESS;
    }
}
