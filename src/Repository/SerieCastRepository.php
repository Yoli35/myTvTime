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
    public function __construct(private readonly ManagerRegistry $registry)
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

    public function countSerieCast()
    {
        $sql = "SELECT "
            . "COUNT(sc.`id`) "
            . "FROM `serie_cast`sc";
        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchOne();
    }

    public function getSerieCast($serieId): array
    {
        $sql = "SELECT "
            . "     c.`tmdb_id` as id, c.`profile_path` as profile_path, c.`name` as name, "
            . "     sc.`known_for_department` as known_for_department, sc.`character_name` as character_name, "
            . "     sc.`recurring_character` as recurring_character, sc.`guest_star` as guest_star, "
            . "     sc.`episodes` as episodes "
            . "FROM `serie_cast` sc "
            . "LEFT JOIN `cast` c ON sc.`cast_id` = c.`id` "
            . "WHERE sc.`serie_id`=".$serieId;
        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
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
