<?php

namespace App\Repository;

use App\Entity\MyMovieCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MyMovieCollection>
 *
 * @method MyMovieCollection|null find($id, $lockMode = null, $lockVersion = null)
 * @method MyMovieCollection|null findOneBy(array $criteria, array $orderBy = null)
 * @method MyMovieCollection[]    findAll()
 * @method MyMovieCollection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MyMovieCollectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MyMovieCollection::class);
    }

    public function add(MyMovieCollection $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MyMovieCollection $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return MyMovieCollection[] Returns an array of MyMovieCollection objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?MyMovieCollection
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
