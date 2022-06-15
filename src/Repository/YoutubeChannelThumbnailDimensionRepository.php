<?php

namespace App\Repository;

use App\Entity\YoutubeChannelThumbnailDimension;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<YoutubeChannelThumbnailDimension>
 *
 * @method YoutubeChannelThumbnailDimension|null find($id, $lockMode = null, $lockVersion = null)
 * @method YoutubeChannelThumbnailDimension|null findOneBy(array $criteria, array $orderBy = null)
 * @method YoutubeChannelThumbnailDimension[]    findAll()
 * @method YoutubeChannelThumbnailDimension[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class YoutubeChannelThumbnailDimensionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, YoutubeChannelThumbnailDimension::class);
    }

    public function add(YoutubeChannelThumbnailDimension $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(YoutubeChannelThumbnailDimension $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return YoutubeChannelThumbnailDimension[] Returns an array of YoutubeChannelThumbnailDimension objects
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

//    public function findOneBySomeField($value): ?YoutubeChannelThumbnailDimension
//    {
//        return $this->createQueryBuilder('y')
//            ->andWhere('y.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
