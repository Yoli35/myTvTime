<?php

namespace App\Repository;

use App\Entity\Cast;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cast>
 *
 * @method Cast|null find($id, $lockMode = null, $lockVersion = null)
 * @method Cast|null findOneBy(array $criteria, array $orderBy = null)
 * @method Cast[]    findAll()
 * @method Cast[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CastRepository extends ServiceEntityRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($registry, Cast::class);
    }

    public function save(Cast $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Cast $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function castByName($query, $limit, $offset): array
    {
        $sql = "SELECT t0.name as name, t0.profile_path as profile_path, t0.tmdb_id as id, 'person' as media_type "
            . "FROM `cast` t0 "
            . "WHERE t0.`name` LIKE '%" . $query . "%' "
            . "LIMIT " . $limit . " OFFSET " . $offset;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();
        return $resultSet->fetchAllAssociative();
    }

    public function searchByName($query, $limit = 20, $offset = 0): array
    {
        $sql = "SELECT t0.name as name, '' as original_name, t0.profile_path as path, t0.tmdb_id as id, '' as date, 'person' as media_type FROM `cast` t0 WHERE t0.`name` LIKE '%" . $query . "%' "
            . "UNION "
            . "SELECT t1.name as name, t1.original_name as original_name, t1.poster_path as path, t1.serie_id as id, t1.first_date_air as date, 'tv' as media_type FROM `serie` t1 WHERE t1.`name` LIKE '%" . $query . "%' OR  t1.`original_name` LIKE '%" . $query . "%' "
            . "UNION "
            . "SELECT t2.title as name, t2.original_title as original_name, t2.poster_path as path, t2.movie_db_id as id, t2.release_date as date, 'movie' as media_type FROM `movie` t2 WHERE t2.`title` LIKE '%" . $query . "%' OR  t2.`original_title` LIKE '%" . $query . "%' "
            . "ORDER BY date DESC "
            . "LIMIT " . $limit . " OFFSET " . $offset;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();
        return $resultSet->fetchAllAssociative();
    }

    public function searchByNameCount($query): array
    {
        $sql = "SELECT t0.name as name FROM `cast` t0 WHERE t0.`name` LIKE '%" . $query . "%' "
            . "UNION "
            . "SELECT t1.name as name FROM `serie` t1 WHERE t1.`name` LIKE '%" . $query . "%' OR  t1.`original_name` LIKE '%" . $query . "%' "
            . "UNION "
            . "SELECT t2.title as name FROM `movie` t2 WHERE t2.`title` LIKE '%" . $query . "%' OR  t2.`original_title` LIKE '%" . $query . "%'";

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();
        return $resultSet->fetchAllAssociative();
    }
//    /**
//     * @return Cast[] Returns an array of Cast objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Cast
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
