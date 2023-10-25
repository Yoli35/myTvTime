<?php

namespace App\Repository;

use App\Entity\ActivityChallenge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityChallenge>
 *
 * @method ActivityChallenge|null find($id, $lockMode = null, $lockVersion = null)
 * @method ActivityChallenge|null findOneBy(array $criteria, array $orderBy = null)
 * @method ActivityChallenge[]    findAll()
 * @method ActivityChallenge[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActivityChallengeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityChallenge::class);
    }

//    /**
//     * @return ActivityChallenge[] Returns an array of ActivityChallenge objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ActivityChallenge
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
