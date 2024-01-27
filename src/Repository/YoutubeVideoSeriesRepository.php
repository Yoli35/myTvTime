<?php

namespace App\Repository;

use App\Entity\YoutubeVideoSeries;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<YoutubeVideoSeries>
 *
 * @method YoutubeVideoSeries|null find($id, $lockMode = null, $lockVersion = null)
 * @method YoutubeVideoSeries|null findOneBy(array $criteria, array $orderBy = null)
 * @method YoutubeVideoSeries[]    findAll()
 * @method YoutubeVideoSeries[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class YoutubeVideoSeriesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, YoutubeVideoSeries::class);
    }

//    /**
//     * @return YoutubeVideoSeries[] Returns an array of YoutubeVideoSeries objects
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

//    public function findOneBySomeField($value): ?YoutubeVideoSeries
//    {
//        return $this->createQueryBuilder('y')
//            ->andWhere('y.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
