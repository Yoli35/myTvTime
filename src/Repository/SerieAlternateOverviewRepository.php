<?php

namespace App\Repository;

use App\Entity\SerieAlternateOverview;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SerieAlternateOverview>
 *
 * @method SerieAlternateOverview|null find($id, $lockMode = null, $lockVersion = null)
 * @method SerieAlternateOverview|null findOneBy(array $criteria, array $orderBy = null)
 * @method SerieAlternateOverview[]    findAll()
 * @method SerieAlternateOverview[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SerieAlternateOverviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SerieAlternateOverview::class);
    }

//    /**
//     * @return SerieAlternateOverview[] Returns an array of SerieAlternateOverview objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?SerieAlternateOverview
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
