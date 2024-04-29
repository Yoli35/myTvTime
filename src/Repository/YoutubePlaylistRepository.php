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
    public function __construct(private readonly ManagerRegistry $registry, private EntityManagerInterface $entityManager)
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

    public function getPlaylist(int $userId, int $videoId): array
    {
        $sql = "SELECT yp.`id` as id, yp.`title` as title, yp.`thumbnail_url` as thumbnail_url "
            ."FROM `youtube_video` v "
            ."INNER JOIN `user_youtube_video` uv ON uv.`youtube_video_id`=v.`id` AND uv.`user_id`=$userId "
            ."INNER JOIN `youtube_playlist` yp ON yp.`user_id`=$userId "
            ."INNER JOIN `youtube_playlist_video` ypv ON ypv.`playlist_id`=yp.`id` AND ypv.`youtube_video_id`=v.`id` "
            ."WHERE v.`id`=$videoId";

        return $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()
            ->fetchAllAssociative();

    }
}
