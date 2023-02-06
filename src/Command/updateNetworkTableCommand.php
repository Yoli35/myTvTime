<?php

namespace App\Command;

use App\Entity\Networks;
use App\Repository\NetworksRepository;
use App\Repository\SerieRepository;
use App\Service\TMDBService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-networks',
    description: 'Update the networks of the series in db',
)]
class updateNetworkTableCommand extends Command
{
    public function __construct(private readonly SerieRepository    $serieRepository,
                                private readonly TMDBService        $TMDBService,
                                private readonly NetworksRepository $networksRepository,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $series = $this->serieRepository->findAll();

        foreach ($series as $serie) {
            $io->writeln('Serie: ' . $serie->getName());
            $serieId = $serie->getSerieId();

            $standing = $this->TMDBService->getTv($serieId, 'fr');
            $tv = json_decode($standing, true);

            $networks = $tv['networks'];
            $dbNetworks = $serie->getNetworks();

            foreach ($networks as $network) {

                $inNetworks = false;
                foreach ($dbNetworks as $dbNetwork) {
                    if ($dbNetwork->getNetworkId() == $network['id']) {
                        $inNetworks = true;
                    }
                }
                if (!$inNetworks) {
                    $networkId = $network['id'];
                    $networkName = $network['name'];
                    $io->writeln('    Network: ' . $networkName . ' (' . $networkId . ')');

                    $dbNetwork = $this->networksRepository->findOneBy(['networkId' => $networkId]);
                    $io->writeln('        Network in db: ' . ($dbNetwork ? 'true (' . $dbNetwork->getNetworkId() . ')' : 'false'));
                    if (!$dbNetwork) {
                        $dbNetwork = new Networks();
                        $dbNetwork->setName($networkName);
                        $dbNetwork->setOriginCountry($network['origin_country']);
                        $dbNetwork->setLogoPath($network['logo_path']);
                        $dbNetwork->setNetworkId($networkId);
                        $this->networksRepository->save($dbNetwork);
                    }
                    $io->writeln('        Adding network');
                    $serie->addNetwork($dbNetwork);
                }
            }
            $this->networksRepository->flush();
        }

        $io->success('Done.');

        return Command::SUCCESS;
    }
}
