<?php

namespace App\Repository;

use App\Entity\YoutubeVideo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;
use Throwable;

/**
 * @extends ServiceEntityRepository<YoutubeVideo>
 *
 * @method YoutubeVideo|null find($id, $lockMode = null, $lockVersion = null)
 * @method YoutubeVideo|null findOneBy(array $criteria, array $orderBy = null)
 * @method YoutubeVideo[]    findAll()
 * @method YoutubeVideo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class YoutubeVideoRepository extends ServiceEntityRepository
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, YoutubeVideo::class);
        $this->registry = $registry;
    }

    public function add(YoutubeVideo $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(YoutubeVideo $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllByDate($userId, $sort = 'publishedAt', $order = 'DESC', $offset = 0): array
    {
        if ($offset < 0) {
            $offset = 0;
        }
        return $this->createQueryBuilder('y')
            ->innerJoin('y.users', 'u', Expr\Join::WITH, 'u.id=' . $userId)
            ->orderBy('y.' . $sort, $order)
            ->setFirstResult($offset)
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }

    public function findAllWithChannelByDate($userId, $sort = 'publishedAt', $order = 'DESC', $offset = 0): array
    {
        if ($offset < 0) {
            $offset = 0;
        }
        return $this->createQueryBuilder('y')
            ->select('y.id as id, y.thumbnailMediumPath as thumbnailMediumPath, y.title as title, y.contentDuration as contentDuration, y.publishedAt as publishedAt,c.title as channelTitle, c.customUrl as channelCustomUrl, c.youtubeId as channelYoutubeId,c.thumbnailDefaultUrl as channelThumbnailDefaultUrl')
            ->innerJoin('y.users', 'u', Expr\Join::WITH, 'u.id=' . $userId)
            ->leftJoin('y.channel', 'c', Expr\Join::WITH, 'c=y.channel')
            ->orderBy('y.' . $sort, $order)
            ->setFirstResult($offset)
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }

    public function getUserYTVideosRuntime($userId): int|null
    {
        $duration = -1;
        try {
            $duration = $this->createQueryBuilder('y')
                ->innerJoin('y.users', 'u', Expr\Join::WITH, 'u.id=' . $userId)
                ->select('sum(y.contentDuration)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (Throwable) {
        }
        return $duration;
    }

    public function firstAddedYTVideo($userId): YoutubeVideo|null
    {
        $result = $this->createQueryBuilder('y')
            ->innerJoin('y.users', 'u', Expr\Join::WITH, 'u.id=' . $userId)
            ->orderBy('y.addedAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
        if (count($result) > 0) {
            return $result[0];
        }
        return null;
    }

    public function videosByTag($userId, $list, $count, $method): array
    {
        if ($method == 'or') {
            $sql = "SELECT t0.id "
                . "FROM `youtube_video` t0 "
                . "INNER JOIN `youtube_video_tag_youtube_video` t2 "
                . "ON t2.`youtube_video_tag_id` IN (" . $list . ") AND t0.`id`=t2.`youtube_video_id` "
                . "INNER JOIN user_youtube_video u2 ON t0.id = u2.youtube_video_id "
                . "INNER JOIN user u1 ON u1.id = u2.user_id AND u1.id = " . $userId;
        } else {
            $sql = "SELECT t0.id "
                . "FROM `youtube_video` t0 "
                . "INNER JOIN `youtube_video_tag_youtube_video` t2 "
                . "ON t2.`youtube_video_tag_id` IN (" . $list . ") AND t0.`id`=t2.`youtube_video_id` "
                . "INNER JOIN user_youtube_video u2 ON t0.id = u2.youtube_video_id "
                . "INNER JOIN user u1 ON u1.id = u2.user_id AND u1.id = " . $userId . " "
                . "GROUP BY t0.id "
                . "HAVING (COUNT(t0.id)=" . $count . ")";
        }

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAll();
    }
}
