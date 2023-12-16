<?php

namespace App\Command;

use App\Entity\WatchProvider;
use App\Repository\WatchProviderRepository;
use App\Service\DateService;
use App\Service\TMDBService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:watch-providers:infos',
    description: 'Collect infos for all watch providers or for a specific watch provider',
)]
class CollectWatchProvidersInfos extends Command
{
    public function __construct(
        private readonly WatchProviderRepository $wpRepository,
        private readonly DateService             $dateService,
        private readonly TMDBService             $tmdbService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
//            ->addArgument('userid', InputArgument::OPTIONAL, 'User\'s Id')
//            ->addArgument('id', InputArgument::OPTIONAL, 'Serie\'s Id')
            ->addOption('watch-provider', 'w', InputOption::VALUE_REQUIRED, 'Watch provider\'s Id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->writeln('Collect watch providers infos Command');

        $wpId = $input->getOption('watch-provider');

        if ($wpId) {
            $wpList = $this->wpRepository->findBy(['id' => $wpId]);
        } else {
            $wpList = $this->wpRepository->findAll();
        }
        $watchProviders = json_decode($this->tmdbService->getTvWatchProviderList(), true);
        $count = 0;
        $now = $this->dateService->newDateImmutable('now', 'Europe/Paris');
        $io->writeln('Watch provider Command started at ' . $now->format('Y-m-d H:i:s'));

        if (count($wpList) === 0) {
            $io->writeln('No watch provider found');

            foreach ($watchProviders['results'] as $wp) {
                $wpEntity = new WatchProvider($wp['provider_id'], $wp['provider_name'], $wp['logo_path'], $wp['display_priority'], $wp['display_priorities']);
                $this->wpRepository->save($wpEntity);
                $count++;
                if ($count % 10 === 0) {
                    $this->wpRepository->flush();
                    $io->writeln(sprintf("%d watch provider%s saved", $count, $count > 1 ? 's' : ''));
                }
            }
            $this->wpRepository->flush();
        } else {
            $io->writeln(sprintf("%d watch provider%s found", count($wpList), count($wpList) > 1 ? 's' : ''));
            foreach ($wpList as $wp) {

                $line = sprintf("%s (%d-%d)", $wp->getProviderName(), $wp->getId(), $wp->getProviderId());
                $io->writeln($line);
                $count++;
            }
        }

        $line = sprintf("Done. %d watch provider%s updated", $count, $count > 1 ? 's' : '');
        $io->success($line);

        $now = $this->dateService->newDateImmutable('now', 'Europe/Paris');
        $line = 'Watch provider Command ended at ' . $now->format('Y-m-d H:i:s');
        $io->writeln($line);

        return Command::SUCCESS;
    }
}
