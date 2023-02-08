<?php

namespace App\Repository;

use App\Entity\UserMovie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserMovie>
 *
 * @method UserMovie|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserMovie|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserMovie[]    findAll()
 * @method UserMovie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserMovieRepository extends ServiceEntityRepository
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserMovie::class);
        $this->registry = $registry;
    }

    public function add(UserMovie $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserMovie $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return UserMovie[] Returns an array of UserMovie objects
     */
    public function lastAddedMovies($userId, $count = 10): array
    {
        return $this->createQueryBuilder('um')
            ->innerJoin('um.users', 'u', Expr\Join::WITH, 'u.id='.$userId)
            ->orderBy('um.createdAt', 'DESC')
            ->setMaxResults($count)
            ->getQuery()
            ->getResult()
        ;
    }

    public function userMoviesCount(): int
    {
        $sql = 'SELECT COUNT(*) as `count` FROM `user_movie`';

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();
        $result = $resultSet->fetchAssociative();

        return $result['count'];
    }

//    /**
//     * @return UserMovie[] Returns an array of UserMovie objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?UserMovie
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function findAllUserMovies($userId): array
    {
        $sql = 'SELECT * FROM `user_movie` t0 '
            . 'INNER JOIN `user_user_movie` t1 ON t1.`user_movie_id`=t0.`id` '
            . 'WHERE t1.`user_id` = ' . $userId;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAll();
    }

    public function isInUserMovies($userId, $movieId): array
    {
        $sql = 'SELECT * FROM `user_user_movie` t1 WHERE t1.`user_movie_id`=' . $movieId . ' AND t1.`user_id`=' . $userId;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAll();
    }

    public function findUserMovies($userId, $offset = 0): array
    {
        $sql = 'SELECT '
            . '  id, title, original_title, poster_path, release_date, movie_db_id, runtime, created_at '
            . 'FROM '
            . '  `user_movie` t0 '
            . 'INNER JOIN '
            . '  `user_user_movie` t1 ON t1.`user_movie_id`=t0.`id` '
            . 'WHERE '
            . '  t1.`user_id` = ' . $userId . ' '
            . 'ORDER BY '
            . '  t0.`release_date` DESC '
            . 'LIMIT 20 '
            . 'OFFSET ' . $offset;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAll();
    }

    public function userMovieGetCollections($movie_id, $user_id, $short = true): array
    {
        if ($short) {
            $sql = 'SELECT '
                . '  id,title,thumbnail,color ';
        } else {
            $sql = 'SELECT '
                . '  * ';
        }
        $sql .= 'FROM '
            . '  movie_collection t0 '
            . 'INNER JOIN '
            . '  movie_collection_user_movie '
            . '  ON t0.id = movie_collection_user_movie.movie_collection_id '
            . 'WHERE '
            . '  movie_collection_user_movie.user_movie_id = ' . $movie_id
            . ' AND t0.`user_id` = ' . $user_id;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAll();
    }

    public function findUserMoviesByAdd($userId, $offset = 0): array
    {
        $sql = 'SELECT * FROM `user_movie` t0 '
            . 'INNER JOIN `user_user_movie` t1 ON t1.`user_movie_id`=t0.`id` '
            . 'WHERE t1.`user_id` = ' . $userId . ' '
            . 'ORDER BY t0.`id` DESC '
            . 'LIMIT 20 '
            . 'OFFSET ' . $offset;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAll();
    }

    public function countUserMovies($userId): array
    {
        $sql = 'SELECT COUNT(*) AS `count` FROM `user_movie` t0 '
            . 'INNER JOIN `user_user_movie` t1 ON t1.`user_movie_id`=t0.`id` '
            . 'WHERE t1.`user_id` = ' . $userId;

        return $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()->fetchAll();
    }

    public function getUserMoviesRuntime($userId): array
    {
        $sql = 'SELECT `runtime` FROM `user_movie` t0 '
            . 'INNER JOIN `user_user_movie` t1 ON t1.`user_movie_id`=t0.`id` '
            . 'WHERE t1.`user_id` = ' . $userId;

        return $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()->fetchAll();
    }

    public function findUserMovieIds($userId): array
    {
        $sql = 'SELECT `movie_db_id` FROM `user_movie` t0 '
            . 'INNER JOIN `user_user_movie` t1 ON t1.`user_movie_id`=t0.`id` '
            . 'WHERE t1.`user_id` = ' . $userId;

        return $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()->fetchAll();
    }

    public function getUserMovieFromIdList($userId, $list): array
    {
        if (!count($list)) {
            return $this->findAllUserMovies($userId);
        }
        $sql = 'SELECT * FROM `user_movie` t0 '
            . 'INNER JOIN `user_user_movie` t1 ON t1.`user_movie_id`=t0.`id` '
            . 'WHERE t1.`user_id` = ' . $userId . ' AND t0.`movie_db_id` IN (';
        foreach ($list as $id) {
            $sql .= $id . ', ';
        }
        $sql = substr($sql, 0, strlen($sql) - 2) . ')';

        return $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()->fetchAll();
    }

    public function searchUserMovies($userId, $query): array
    {
        $sql = 'SELECT * FROM `user_movie` t0 '
            . 'INNER JOIN `user_user_movie` t1 ON t1.`user_movie_id`=t0.`id` '
            . 'WHERE t1.`user_id` = ' . $userId . ' '
            . 'AND (t0.title LIKE "%' . $query . '%" OR t0.original_title LIKE "%' . $query . '%")'
            . 'ORDER BY t0.`release_date` DESC';

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAll();
    }
}
