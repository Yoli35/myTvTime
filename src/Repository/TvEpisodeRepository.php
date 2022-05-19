<?php

namespace App\Repository;

use App\Entity\TvEpisode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TvEpisode|null find($id, $lockMode = null, $lockVersion = null)
 * @method TvEpisode|null findOneBy(array $criteria, array $orderBy = null)
 * @method TvEpisode[]    findAll()
 * @method TvEpisode[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TvEpisodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TvEpisode::class);
    }

    // /**
    //  * @return TvEpisode[] Returns an array of TvEpisode objects
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
    public function findOneBySomeField($value): ?TvEpisode
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
