<?php

namespace App\Repository;

use App\Entity\TvSeason;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TvSeason|null find($id, $lockMode = null, $lockVersion = null)
 * @method TvSeason|null findOneBy(array $criteria, array $orderBy = null)
 * @method TvSeason[]    findAll()
 * @method TvSeason[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TvSeasonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TvSeason::class);
    }

    // /**
    //  * @return TvSeason[] Returns an array of TvSeason objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TvSeason
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
