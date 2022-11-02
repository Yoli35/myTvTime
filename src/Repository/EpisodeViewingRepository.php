<?php

namespace App\Repository;

use App\Entity\EpisodeViewing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EpisodeViewing>
 *
 * @method EpisodeViewing|null find($id, $lockMode = null, $lockVersion = null)
 * @method EpisodeViewing|null findOneBy(array $criteria, array $orderBy = null)
 * @method EpisodeViewing[]    findAll()
 * @method EpisodeViewing[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EpisodeViewingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EpisodeViewing::class);
    }

    public function save(EpisodeViewing $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EpisodeViewing $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return EpisodeViewing[] Returns an array of EpisodeViewing objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?EpisodeViewing
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
