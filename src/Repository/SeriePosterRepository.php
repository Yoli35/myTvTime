<?php

namespace App\Repository;

use App\Entity\SeriePoster;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SeriePoster>
 *
 * @method SeriePoster|null find($id, $lockMode = null, $lockVersion = null)
 * @method SeriePoster|null findOneBy(array $criteria, array $orderBy = null)
 * @method SeriePoster[]    findAll()
 * @method SeriePoster[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SeriePosterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SeriePoster::class);
    }

    public function save(SeriePoster $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return SeriePoster[] Returns an array of SeriePoster objects
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

//    public function findOneBySomeField($value): ?SeriePoster
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
