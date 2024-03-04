<?php

namespace App\Command;

use App\Entity\Keyword;
use App\Entity\Serie;
use App\Entity\Settings;
use App\Repository\KeywordRepository;
use App\Repository\SerieRepository;
use App\Repository\SettingsRepository;
use App\Repository\UserRepository;
use App\Service\DateService;
use App\Service\TMDBService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:series:keywords',
    description: 'Collect keywords from added series and store them in the database.',
)]
class SeriesKeywordsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DateService            $dateService,
        private readonly KeywordRepository      $keywordRepository,
        private readonly SerieRepository        $serieRepository,
        private readonly SettingsRepository     $settingsRepository,
        private readonly TMDBService            $tmdbService,
        private readonly UserRepository         $userRepository,
    )
    {
        parent::__construct(
        );
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $kIndex = 0;

        $now = $this->dateService->newDateImmutable('now', 'Europe/Paris');
        $io->title('Collecting keywords from added series started at ' . $now->format('Y-m-d H:i:s'));
        $settings = $this->settingsRepository->findOneBy(['name' => 'series_keywords_last_update']);
        if (!$settings) {
            $settings = new Settings();
            $settings->setName('series_keywords_last_update');
            $settings->setUser($this->userRepository->find(2)); // me
            $settings->setData(['last_update' => $now, 'last_series_id' => 0]);
            $this->entityManager->persist($settings);
            $this->entityManager->flush();
            $lastId = 0;
        } else {
            $lastId = $settings->getData()['last_series_id'];
        }

        $series = $this->serieRepository->seriesByIdGreaterThan($lastId);

        foreach ($series as $s) {
            $tmdbId = $s['tmdb_id'];
            $tv = json_decode($this->tmdbService->getTv($tmdbId, 'fr', ['keywords']), true);

            if ($tv && key_exists('keywords', $tv)) {
                $keywords = $tv['keywords']['results'];
                foreach ($keywords as $keyword) {
                    $keywordEntity = $this->keywordRepository->findOneBy(['keywordId' => $keyword['id']]);
                    if (!$keywordEntity) {
                        $keywordEntity = new Keyword();
                        $keywordEntity->setKeywordId($keyword['id']);
                        $keywordEntity->setName($keyword['name']);
                        $this->entityManager->persist($keywordEntity);
                        $io->writeln('"' . $keyword['name'] . '" added to the database.');
                        $kIndex++;
                        if ($kIndex % 50 === 0) {
                            $io->writeln('Flushing...');
                            $this->entityManager->flush();
                            $settings->setData(['last_update' => $now, 'last_series_id' => $s->getId()]);
                            $this->entityManager->persist($settings);
                            $this->entityManager->flush();
                        }
                    }
                }
            }
        }
        $io->writeln('Flushing...');
        $this->entityManager->flush();
        $settings->setData(['last_update' => $now, 'last_series_id' => $s->getId()]);
        $this->entityManager->persist($settings);
        $this->entityManager->flush();

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');
        $now = $this->dateService->newDateImmutable('now', 'Europe/Paris');
        $io->title('Collecting keywords from added series ended at ' . $now->format('Y-m-d H:i:s'));

        return Command::SUCCESS;
    }
}
