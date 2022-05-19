<?php

namespace App\Repository;

use App\Entity\ProductionCountry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductionCountry|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductionCountry|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductionCountry[]    findAll()
 * @method ProductionCountry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductionCountryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductionCountry::class);
    }

    // /**
    //  * @return ProductionCountry[] Returns an array of ProductionCountry objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ProductionCountry
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
