<?php

namespace App\Repository;

use App\Entity\YoutubeVideoTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<YoutubeVideoTag>
 *
 * @method YoutubeVideoTag|null find($id, $lockMode = null, $lockVersion = null)
 * @method YoutubeVideoTag|null findOneBy(array $criteria, array $orderBy = null)
 * @method YoutubeVideoTag[]    findAll()
 * @method YoutubeVideoTag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class YoutubeVideoTagRepository extends ServiceEntityRepository
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, YoutubeVideoTag::class);
        $this->registry = $registry;
    }

    public function add(YoutubeVideoTag $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(YoutubeVideoTag $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return YoutubeVideoTag[] Returns an array of YoutubeVideoTag objects
     */
    public function findByLabel($query): array
    {
        return $this->createQueryBuilder('y')
            ->andWhere('y.label like :val')
            ->setParameter('val', '%' . $query . '%')
            ->orderBy('y.label', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return YoutubeVideoTag[] Returns an array of YoutubeVideoTag objects
     */
    public function findAllByLabel(): array
    {
        return $this->createQueryBuilder('y')
            ->orderBy('y.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllByVideoId($videoId): array
    {
        return $this->createQueryBuilder('yt')
            ->innerJoin('yt.ytVideos', 'yv', Expr\Join::WITH, 'yv.id=' . $videoId)
            ->getQuery()
            ->getResult();
    }

    public function findVideosTags($videoIds): array
    {
        $sql = "SELECT yt.id, yt.label, yv.id as videoId FROM youtube_video_tag yt "
            . "INNER JOIN youtube_video_tag_youtube_video ytyv ON ytyv.youtube_video_tag_id = yt.id "
            . "INNER JOIN youtube_video yv ON yv.id = ytyv.youtube_video_id  "
            . "WHERE yv.id IN (" . implode(',', $videoIds) . ")";

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
    }
//    public function findOneBySomeField($value): ?YoutubeVideoTag
//    {
//        return $this->createQueryBuilder('y')
//            ->andWhere('y.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
