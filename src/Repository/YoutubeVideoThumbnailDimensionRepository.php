<?php

namespace App\Repository;

use App\Entity\YoutubeVideoThumbnailDimension;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<YoutubeVideoThumbnailDimension>
 *
 * @method YoutubeVideoThumbnailDimension|null find($id, $lockMode = null, $lockVersion = null)
 * @method YoutubeVideoThumbnailDimension|null findOneBy(array $criteria, array $orderBy = null)
 * @method YoutubeVideoThumbnailDimension[]    findAll()
 * @method YoutubeVideoThumbnailDimension[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class YoutubeVideoThumbnailDimensionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, YoutubeVideoThumbnailDimension::class);
    }

    public function add(YoutubeVideoThumbnailDimension $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(YoutubeVideoThumbnailDimension $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return YoutubeVideoThumbnailDimension[] Returns an array of YoutubeVideoThumbnailDimension objects
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

//    public function findOneBySomeField($value): ?YoutubeVideoThumbnailDimension
//    {
//        return $this->createQueryBuilder('y')
//            ->andWhere('y.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
