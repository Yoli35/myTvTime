<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\Repository\YoutubeVideoRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:link-yt-videos',
    description: 'Add a short description for your command',
)]
class LinkYtVideosCommand extends Command
{
    private UserRepository $userRepository;
    private YoutubeVideoRepository $youtubeVideoRepository;

    public function __construct(UserRepository $userRepository, YoutubeVideoRepository $youtubeVideoRepository)
    {
        $this->userRepository = $userRepository;
        $this->youtubeVideoRepository = $youtubeVideoRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('userid', InputArgument::REQUIRED, 'User\'s Id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = $input->getArgument('userid');

        $n = 0;
        $user = $this->userRepository->find($userId);
        $videos = $this->youtubeVideoRepository->findAll();

        foreach ($videos as $video) {
            if ($video->getUserId() == $userId) {
                $video->addUser($user);
                $this->youtubeVideoRepository->add($video, true);
                $n++;
                $io->success($n . ' - ' . $video->getTitle());
            }
        }

        $io->success($n . ' video' . ($n > 1 ? 's':''));

        return Command::SUCCESS;
    }
}
