<?php

namespace App\Command;

use App\Entity\UserYVideo;
use App\Repository\UserRepository;
use App\Repository\UserYVideoRepository;
use App\Repository\YoutubeVideoRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:yvideo',
    description: 'Remplir la table `user_yvideo` depuis la table `user_youtube_video`',
)]
class YvideoCommand extends Command
{
    public function __construct(
        private readonly UserRepository         $userRepository,
        private readonly UserYVideoRepository   $userYVideoRepository,
        private readonly YoutubeVideoRepository $youtubeVideoRepository,
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

        $userYoutubeVideos = $this->youtubeVideoRepository->userYoutubeVideos();
        $count = 0;
        $t0 = microtime(true);

        foreach ($userYoutubeVideos as $userYoutubeVideo) {
            $user = $this->userRepository->find($userYoutubeVideo['user_id']);
            $video = $this->youtubeVideoRepository->find($userYoutubeVideo['youtube_video_id']);
            $line = $user->getUsername() . ' - ' . $video->getTitle();
            $io->writeln($line);

            $userYVideo = new UserYVideo();
            $userYVideo->setUser($user);
            $userYVideo->setVideo($video);
            $userYVideo->setHidden(false);

            $this->userYVideoRepository->save($userYVideo);
            $count++;
            if ($count % 10 == 0) {
                $io->writeln($count . ' user_yvideo created.');
                $this->userYVideoRepository->flush();
            }
        }
        $io->writeln($count . ' user_yvideo created.');
        $this->userYVideoRepository->flush();
        $t1 = microtime(true);

        $line = $count . ' user_yvideo created in ' . ($t1 - $t0) . 'sec.';
        $io->writeln($line);
        $io->success('Done.');

        return Command::SUCCESS;
    }
}
