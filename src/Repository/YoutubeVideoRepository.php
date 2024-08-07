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

    public function findAllWithChannelByDate($userId, $sort = 'publishedAt', $order = 'DESC', $offset = 0, $limit = 20): array
    {
        if ($offset < 0) {
            $offset = 0;
        }
        return $this->createQueryBuilder('y')
            ->select('y.id as id, y.thumbnailHighPath as thumbnailHighPath, y.title as title, y.contentDuration as contentDuration, y.publishedAt as publishedAt, c.title as channelTitle, c.customUrl as channelCustomUrl, c.youtubeId as channelYoutubeId, c.thumbnailDefaultUrl as channelThumbnailDefaultUrl')
            ->innerJoin('y.users', 'u', Expr\Join::WITH, 'u.id=' . $userId)
            ->leftJoin('y.channel', 'c', Expr\Join::WITH, 'c=y.channel')
            ->orderBy('y.' . $sort, $order)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findAllWithChannelByDateSQL($userId, $sort = 'publishedAt', $order = 'DESC', $offset = 0, $limit = 20): array
    {
        $sort = $this->getSort($sort);
        $sql = "SELECT 
                    y.`id` as id, 
                    y.`thumbnail_high_path` as thumbnailHighPath, 
                    y.`title` as title, 
                    y.`content_duration` as contentDuration, 
                    y.`published_at` as publishedAt, 
                    yc.`title` as channelTitle, 
                    yc.`custom_url` as channelCustomUrl, 
                    yc.`youtube_id` as channelYoutubeId, 
                    yc.`thumbnail_default_url` as channelThumbnailDefaultUrl 
                FROM `youtube_video` y 
                    INNER JOIN `user_yvideo` uyv ON uyv.`user_id`=$userId AND uyv.`video_id`=y.`id` 
                    LEFT JOIN `youtube_channel` yc ON yc.`id`=y.`channel_id` 
                WHERE uyv.`hidden`=0 
                ORDER BY y.`$sort` $order 
                LIMIT $limit OFFSET $offset";

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
    }

    public function getPreviousVideo($userId, $id, $sort = 'publishedAt', $order = 'DESC'): array
    {
        $sort = $this->getSort($sort);
        $sql = "SELECT y.`id` as id, y.`thumbnail_medium_path` as thumbnailUrl, y.`title` as title, y.`content_duration` as contentDuration "
            . "FROM `youtube_video` y "
            . "INNER JOIN `user_yvideo` uyv ON uyv.`user_id`=$userId AND uyv.`video_id`=y.`id` "
            . "WHERE y.id < $id AND uyv.`hidden`=0 "
            . "ORDER BY y.`$sort` $order LIMIT 1";

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
    }

    public function getNextVideo($userId, $id, $sort = 'publishedAt', $order = 'DESC'): array
    {
        $sort = $this->getSort($sort);
        $order = $order == 'DESC' ? 'ASC' : 'DESC';
        $sql = "SELECT y.`id` as id, y.`thumbnail_medium_path` as thumbnailUrl, y.`title` as title, y.`content_duration` as contentDuration "
            . "FROM `youtube_video` y "
            . "INNER JOIN `user_yvideo` uyv ON uyv.`user_id`=$userId AND uyv.`video_id`=y.`id` "
            . "WHERE y.id > $id AND uyv.`hidden`=0 "
            . "ORDER BY y.`$sort` $order LIMIT 1";

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
    }

    public function getSort($sort): string
    {
        switch ($sort) {
            case 'publishedAt':
                $sort = 'published_at';
                break;
            case 'addedAt':
                $sort = 'added_at';
                break;
            case 'contentDuration':
                $sort = 'content_duration';
                break;
        }
        return $sort;
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

        return $resultSet->fetchAllAssociative();
    }

    public function getUserYTVideosCount($userId): int
    {
        $sql = "SELECT COUNT(*) as count "
            . "FROM `user_youtube_video` "
            . "WHERE `user_id`=" . $userId;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAssociative()['count'];
    }

    public function getUserYTVideosDuration($userId): int
    {
        $sql = "SELECT SUM(yv.`content_duration`) as duration "
            . "FROM `youtube_video` yv "
            . "INNER JOIN `user_youtube_video` uyv ON uyv.`youtube_video_id`=yv.`id` "
            . "INNER JOIN `user` u ON u.id=uyv.`user_id` "
            . "WHERE `user_id`=$userId";

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAssociative()['duration'];
    }

    public function userYoutubeVideos(): array
    {
        $sql = "SELECT user_id, youtube_video_id "
            . "FROM user_youtube_video";

        return $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    public function isViewed(int $userId, string $link): array|null
    {
        $sql = "SELECT v.`id` as id, v.`added_at` as viewed_at "
            . "FROM `youtube_video` v "
            . "INNER JOIN `user_youtube_video` uv ON uv.`youtube_video_id`=v.`id` AND uv.`user_id`=$userId "
            . "WHERE v.`link`='$link'";

        $result = $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()
            ->fetchAssociative();

        return !$result ? null : $result;
    }
}
