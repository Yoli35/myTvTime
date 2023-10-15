<?php

namespace App\Repository;

use App\Entity\SerieLocalizedName;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SerieLocalizedName>
 *
 * @method SerieLocalizedName|null find($id, $lockMode = null, $lockVersion = null)
 * @method SerieLocalizedName|null findOneBy(array $criteria, array $orderBy = null)
 * @method SerieLocalizedName[]    findAll()
 * @method SerieLocalizedName[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SerieLocalizedNameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SerieLocalizedName::class);
    }

    public function save(SerieLocalizedName $serieLocalizedName, $flush = false): void
    {
        $this->getEntityManager()->persist($serieLocalizedName);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return SerieLocalizedName[] Returns an array of SerieLocalizedName objects
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

//    public function findOneBySomeField($value): ?SerieLocalizedName
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
