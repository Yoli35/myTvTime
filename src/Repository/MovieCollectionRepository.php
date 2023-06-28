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
        $sql = "SELECT "
            . "t0.`id` AS `id`, "
            . "t0.`movie_db_id` AS `movie_db_id`, "
            . "t0.`title` AS `title`, "
            . "t0.`original_title` AS `original_title`, "
            . "t0.release_date AS release_date, "
            . "t0.runtime AS runtime, "
            . "t0.`poster_path` AS `poster_path`, "
            . "t0.`overview_fr` AS `overview_fr`, "
            . "t0.`overview_en` AS `overview_en`, "
            . "t0.`overview_de` AS `overview_de`, "
            . "t0.`overview_es` AS `overview_es` "
            . "FROM `movie` t0 "
            . "INNER JOIN `movie_collection_movie` t1 ON t0.`id` = t1.`movie_id` and t1.`movie_collection_id` = " . $id . " "
            . "ORDER BY t0.`release_date` " . $order;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAll();
    }

    public function getSummary($userId)
    {
        // Requête SQL pour récupérer les collections de films de l'utilisateur (champs : id, title, thumbnail, nombre de films)
        $sql = "SELECT "
            . "t0.`id` AS `id`, "
            . "t0.`title` AS `name`, "
            . "t0.`thumbnail` AS `image`, "
            . "COUNT(t1.`movie_id`) AS `count` "
            . "FROM `movie_collection` t0 "
            . "INNER JOIN `movie_collection_movie` t1 ON t0.`id` = t1.`movie_collection_id` "
            . "WHERE t0.`user_id` = " . $userId . " "
            . "GROUP BY t0.`id` "
            . "ORDER BY t0.`title` ASC";

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAll();
    }
}
