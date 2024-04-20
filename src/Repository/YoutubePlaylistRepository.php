<?php

namespace App\Repository;

use App\Entity\YoutubePlaylist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<YoutubePlaylist>
 *
 * @method YoutubePlaylist|null find($id, $lockMode = null, $lockVersion = null)
 * @method YoutubePlaylist|null findOneBy(array $criteria, array $orderBy = null)
 * @method YoutubePlaylist[]    findAll()
 * @method YoutubePlaylist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class YoutubePlaylistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, YoutubePlaylist::class);
    }

    public function save($playlist, $flush = false): void
    {
        $this->entityManager->persist($playlist);
        if ($flush) {
            $this->entityManager->flush();
        }
    }
}
