<?php

namespace App\Repository;

use App\Entity\YoutubePlaylistVideo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<YoutubePlaylistVideo>
 *
 * @method YoutubePlaylistVideo|null find($id, $lockMode = null, $lockVersion = null)
 * @method YoutubePlaylistVideo|null findOneBy(array $criteria, array $orderBy = null)
 * @method YoutubePlaylistVideo[]    findAll()
 * @method YoutubePlaylistVideo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class YoutubePlaylistVideoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry,private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, YoutubePlaylistVideo::class);
    }

    public function save(YouTubePlaylistVideo $youtubePlaylistVideo, bool $flush = false): void
    {
        $this->entityManager->persist($youtubePlaylistVideo);
        if ($flush) {
            $this->entityManager->flush();
        }
    }
}
