<?php

namespace App\Repository;

use App\Entity\Cast;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Cast|null find($id, $lockMode = null, $lockVersion = null)
 * @method Cast|null findOneBy(array $criteria, array $orderBy = null)
 * @method Cast[]    findAll()
 * @method Cast[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CastRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cast::class);
    }

    // /**
    //  * @return Cast[] Returns an array of Cast objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Cast
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
