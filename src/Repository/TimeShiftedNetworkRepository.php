<?php

namespace App\Repository;

use App\Entity\TimeShiftedNetwork;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TimeShiftedNetwork>
 *
 * @method TimeShiftedNetwork|null find($id, $lockMode = null, $lockVersion = null)
 * @method TimeShiftedNetwork|null findOneBy(array $criteria, array $orderBy = null)
 * @method TimeShiftedNetwork[]    findAll()
 * @method TimeShiftedNetwork[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TimeShiftedNetworkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TimeShiftedNetwork::class);
    }

    public function save(TimeShiftedNetwork $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TimeShiftedNetwork $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return TimeShiftedNetwork[] Returns an array of TimeShiftedNetwork objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?TimeShiftedNetwork
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
