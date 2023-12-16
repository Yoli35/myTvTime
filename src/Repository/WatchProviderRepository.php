<?php

namespace App\Repository;

use App\Entity\WatchProvider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WatchProvider>
 *
 * @method WatchProvider|null find($id, $lockMode = null, $lockVersion = null)
 * @method WatchProvider|null findOneBy(array $criteria, array $orderBy = null)
 * @method WatchProvider[]    findAll()
 * @method WatchProvider[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WatchProviderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WatchProvider::class);
    }

    public function save(WatchProvider $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

//    /**
//     * @return WatchProvider[] Returns an array of WatchProvider objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('w.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?WatchProvider
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
