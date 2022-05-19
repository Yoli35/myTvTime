<?php

namespace App\Repository;

use App\Entity\SpokenLanguage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SpokenLanguage|null find($id, $lockMode = null, $lockVersion = null)
 * @method SpokenLanguage|null findOneBy(array $criteria, array $orderBy = null)
 * @method SpokenLanguage[]    findAll()
 * @method SpokenLanguage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SpokenLanguageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SpokenLanguage::class);
    }

    // /**
    //  * @return SpokenLanguage[] Returns an array of SpokenLanguage objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SpokenLanguage
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
