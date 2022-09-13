<?php

namespace App\Repository;

use App\Entity\YoutubeVideo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
//use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;

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

    public function findAllByDate($userId, $offset = 0): array
    {
        if ($offset < 0) {
            $offset = 0;
        }
        return $this->createQueryBuilder('y')
            ->andWhere('y.userId = :val')
            ->setParameter('val', $userId)
            ->orderBy('y.publishedAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }

    public function countUserYTVideos($userId): array
    {
        $sql = 'SELECT COUNT(*) AS `count` FROM `youtube_video` t0 '
//            .'INNER JOIN `user_tik_tok_video` t1 ON t1.`tik_tok_video_id`=t0.`id` '
            . 'WHERE t0.`user_id` = ' . $userId;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAll();
    }

    public function getUserYTVideosRuntime($userId): array
    {
        $sql = 'SELECT `content_duration` FROM `youtube_video` t0 '
//            .'INNER JOIN `user_tik_tok_video` t1 ON t1.`tik_tok_video_id`=t0.`id` '
            . 'WHERE t0.`user_id` = ' . $userId;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAll();
    }

    public function firstAddedYTVideo($userId): YoutubeVideo
    {
        $result = $this->createQueryBuilder('y')
            ->andWhere('y.userId = :val')
            ->setParameter('val', $userId)
            ->orderBy('y.addedAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
        return $result[0];
    }

    public function videosByTag($userId, $list, $count, $method): array
    {
        if ($method == 'or') {
            $sql = "SELECT t0.id "
                . "FROM `youtube_video` t0 "
                . "INNER JOIN `youtube_video_tag_youtube_video` t2 "
                . "ON t2.`youtube_video_tag_id` IN (" . $list . ") AND t0.`id`=t2.`youtube_video_id` "
                . "WHERE t0.`user_id` = " . $userId . " ";
        } else {
            $sql = "SELECT t0.id "
                . "FROM `youtube_video` t0 "
                . "INNER JOIN `youtube_video_tag_youtube_video` t2 "
                . "ON t2.`youtube_video_tag_id` IN (" . $list . ") AND t0.`id`=t2.`youtube_video_id` "
                . "WHERE t0.`user_id` = " . $userId . " "
                . "GROUP BY t0.id "
                . "HAVING (COUNT(t0.id)=" . $count . ")";
        }

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAll();

//        $result = $this->createQueryBuilder('y')
//            ->select('*')->distinct()
//            ->where('y.userId = :val')
//            ->setParameter('val', $userId)
//            ->innerJoin('y.tags', 't', Expr\Join::WITH, 't.id in (' . $list . ') and y.id=t.ytVideos')
//            ->orderBy('y.publishedAt', 'DESC')
//            ->getQuery()
//            ->getResult();
    }
//    /**
//     * @return YoutubeVideo[] Returns an array of YoutubeVideo objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('y')
//            ->andWhere('y.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('y.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?YoutubeVideo
//    {
//        return $this->createQueryBuilder('y')
//            ->andWhere('y.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
