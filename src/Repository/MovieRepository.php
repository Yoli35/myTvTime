<?php

namespace App\Repository;

use App\Entity\Movie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Movie>
 *
 * @method Movie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Movie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Movie[]    findAll()
 * @method Movie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovieRepository extends ServiceEntityRepository
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Movie::class);
        $this->registry = $registry;
    }

    public function save(Movie $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Movie $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Movie[] Returns an array of Movie objects
     */
    public function lastAddedMovies($userId, $count = 10): array
    {
        return $this->createQueryBuilder('m')
            ->innerJoin('m.users', 'u', Expr\Join::WITH, 'u.id=' . $userId)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($count)
            ->getQuery()
            ->getResult();
    }

    public function moviesCount(): int
    {
        $sql = 'SELECT COUNT(*) as `count` FROM `movie`';

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();
        $result = $resultSet->fetchAssociative();

        return $result['count'];
    }

    public function moviesByTitle($query, $limit, $offset): array
    {
        $sql = "SELECT t0.title as title, t0.poster_path as poster_path, t0.release_date as release_date, t0.original_title as original_title, t0.movie_db_id as id, 'movie' as media_type "
            . "FROM `movie` t0 "
            . "WHERE t0.`title` LIKE '%" . $query . "%' OR  t0.`original_title` LIKE '%" . $query . "%' "
            . "ORDER BY t0.`release_date` DESC "
            . "LIMIT " . $limit . " OFFSET " . $offset;
        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();
        return $resultSet->fetchAllAssociative();
    }

//    /**
//     * @return Movie[] Returns an array of Movie objects
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

//    public function findOneBySomeField($value): ?Movie
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
        $sql = 'SELECT * FROM `movie` t0 '
            . 'INNER JOIN `user_movie` t1 ON t1.`movie_id`=t0.`id` '
            . 'WHERE t1.`user_id` = ' . $userId;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
    }

    public function isInUserMovies($userId, $movieId): array
    {
        $sql = 'SELECT * FROM `user_movie` t1 WHERE t1.`movie_id`=' . $movieId . ' AND t1.`user_id`=' . $userId;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
    }

    public function findUserMovies($userId, $offset = 0): array
    {
        $sql = 'SELECT '
            . '  id, title, original_title, poster_path, release_date, movie_db_id, runtime, created_at '
            . 'FROM '
            . '  `movie` t0 '
            . 'INNER JOIN '
            . '  `user_movie` t1 ON t1.`movie_id`=t0.`id` '
            . 'WHERE '
            . '  t1.`user_id` = ' . $userId . ' '
            . 'ORDER BY '
            . '  t0.`release_date` DESC '
            . 'LIMIT 20 '
            . 'OFFSET ' . $offset;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
    }

    public function userMovieGetMovieLists($movie_id, $user_id, $short = true): array
    {
        if ($short) {
            $sql = 'SELECT '
                . '  id,title,thumbnail,color ';
        } else {
            $sql = 'SELECT '
                . '  * ';
        }
        $sql .= 'FROM '
            . '  movie_list t0 '
            . 'INNER JOIN '
            . '  movie_list_movie '
            . '  ON t0.id = movie_list_movie.movie_list_id '
            . 'WHERE '
            . '  movie_list_movie.movie_id = ' . $movie_id . ' '
            . 'AND '
            . '  t0.`user_id` = ' . $user_id;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
    }

    public function findUserMoviesByAdd($userId, $offset = 0): array
    {
        $sql = 'SELECT * FROM `movie` t0 '
            . 'INNER JOIN `user_movie` t1 ON t1.`user_movie_id`=t0.`id` '
            . 'WHERE t1.`user_id` = ' . $userId . ' '
            . 'ORDER BY t0.`id` DESC '
            . 'LIMIT 20 '
            . 'OFFSET ' . $offset;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
    }

    public function countUserMovies($userId): array
    {
        $sql = 'SELECT COUNT(*) AS `count` FROM `movie` t0 '
            . 'INNER JOIN `user_movie` t1 ON t1.`movie_id`=t0.`id` '
            . 'WHERE t1.`user_id` = ' . $userId;

        return $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()->fetchAllAssociative();
    }

    public function getUserMoviesRuntime($userId): array
    {
        $sql = 'SELECT `runtime` FROM `movie` t0 '
            . 'INNER JOIN `user_movie` t1 ON t1.`movie_id`=t0.`id` '
            . 'WHERE t1.`user_id` = ' . $userId;

        return $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()->fetchAllAssociative();
    }

    public function findUserMovieIds($userId): array
    {
        $sql = 'SELECT `movie_db_id` FROM `movie` t0 '
            . 'INNER JOIN `user_movie` t1 ON t1.`movie_id`=t0.`id` '
            . 'WHERE t1.`user_id` = ' . $userId;

        return $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()->fetchAllAssociative();
    }

    public function getUserMovieFromIdList($userId, $list): array
    {
        if (!count($list)) {
            return $this->findAllUserMovies($userId);
        }
        $sql = 'SELECT * FROM `movie` t0 '
            . 'INNER JOIN `user_movie` t1 ON t1.`movie_id`=t0.`id` '
            . 'WHERE t1.`user_id` = ' . $userId . ' AND t0.`movie_db_id` IN (';
        foreach ($list as $id) {
            $sql .= $id . ', ';
        }
        $sql = substr($sql, 0, strlen($sql) - 2) . ')';

        return $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()->fetchAllAssociative();
    }

    public function searchUserMovies($userId, $query): array
    {
        $sql = 'SELECT * FROM `movie` t0 '
            . 'INNER JOIN `user_movie` t1 ON t1.`movie_id`=t0.`id` '
            . 'WHERE t1.`user_id` = ' . $userId . ' '
            . 'AND (t0.title LIKE "%' . $query . '%" OR t0.original_title LIKE "%' . $query . '%")'
            . 'ORDER BY t0.`release_date` DESC';

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
    }
}
