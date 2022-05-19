<?php

namespace App\Repository;

use App\Entity\ImageConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ImageConfig|null find($id, $lockMode = null, $lockVersion = null)
 * @method ImageConfig|null findOneBy(array $criteria, array $orderBy = null)
 * @method ImageConfig[]    findAll()
 * @method ImageConfig[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImageConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImageConfig::class);
    }

    // /**
    //  * @return ImageConfig[] Returns an array of ImageConfig objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ImageConfig
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
