<?php

namespace App\Repository;

use App\Entity\MovieCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MovieCollection>
 *
 * @method MovieCollection|null find($id, $lockMode = null, $lockVersion = null)
 * @method MovieCollection|null findOneBy(array $criteria, array $orderBy = null)
 * @method MovieCollection[]    findAll()
 * @method MovieCollection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovieCollectionRepository extends ServiceEntityRepository
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MovieCollection::class);
        $this->registry = $registry;
    }

    public function add(MovieCollection $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MovieCollection $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getMoviesByReleaseDate($id, $order)
    {
        $sql = "SELECT * FROM `user_movie` t0 "
            . "INNER JOIN `movie_collection_user_movie` t1 ON t0.`id` = t1.`user_movie_id` and t1.`movie_collection_id` = ".$id ." "
            . "ORDER BY t0.`release_date` ".$order;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAll();
    }
    /**
     * @return MovieCollection[] Returns an array of MovieCollection objects
     */
    public function findByExampleField($value): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

//    public function findOneBySomeField($value): ?MovieCollection
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
