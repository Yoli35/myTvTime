<?php

namespace App\Repository;

use App\Entity\SerieCast;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SerieCast>
 *
 * @method SerieCast|null find($id, $lockMode = null, $lockVersion = null)
 * @method SerieCast|null findOneBy(array $criteria, array $orderBy = null)
 * @method SerieCast[]    findAll()
 * @method SerieCast[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SerieCastRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SerieCast::class);
    }

    public function save(SerieCast $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function saveAll(): void
    {
        $this->getEntityManager()->flush();
    }

    public function remove(SerieCast $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return SerieCast[] Returns an array of SerieCast objects
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

//    public function findOneBySomeField($value): ?SerieCast
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
