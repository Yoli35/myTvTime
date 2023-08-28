<?php

namespace App\Repository;

use App\Entity\SerieBackdrop;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SerieBackdrop>
 *
 * @method SerieBackdrop|null find($id, $lockMode = null, $lockVersion = null)
 * @method SerieBackdrop|null findOneBy(array $criteria, array $orderBy = null)
 * @method SerieBackdrop[]    findAll()
 * @method SerieBackdrop[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SerieBackdropRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SerieBackdrop::class);
    }

    public function save(SerieBackdrop $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return SerieBackdrop[] Returns an array of SerieBackdrop objects
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

//    public function findOneBySomeField($value): ?SerieBackdrop
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
