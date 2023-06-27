<?php

namespace App\Repository;

use App\Entity\Serie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Serie>
 *
 * @method Serie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Serie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Serie[]    findAll()
 * @method Serie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SerieRepository extends ServiceEntityRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($registry, Serie::class);
    }

    public function save(Serie $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function remove(Serie $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Serie[] Returns an array of Serie objects
     */
    public function findAllSeries($userId, $page = 1, $perPage = 20, $orderBy = 'firstDateAir', $order = 'desc'): array
    {
        if ($page < 1) {
            $page = 1;
        }
        return $this->createQueryBuilder('s')
            ->innerJoin('s.users', 'u', Expr\Join::WITH, 'u.id=' . $userId)
            ->orderBy('s.' . $orderBy, $order)
            ->setMaxResults($perPage)
            ->setFirstResult(($page - 1) * $perPage)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Serie[] Returns an array of Serie objects
     */
    public function findAllUserSeries($userId): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.users', 'u', Expr\Join::WITH, 'u.id=' . $userId)
            ->orderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Serie[] Returns an array of Serie objects
     */
    public function lastUpdatedSeries($userId, $count = 10): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.users', 'u', Expr\Join::WITH, 'u.id=' . $userId)
            ->orderBy('s.updatedAt', 'DESC')
            ->setMaxResults($count)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Serie[] Returns an array of Serie objects
     */
    public function lastAddedSeries($userId, $count = 10): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.users', 'u', Expr\Join::WITH, 'u.id=' . $userId)
            ->orderBy('s.id', 'DESC')
            ->setMaxResults($count)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Serie[] Returns an array of Serie objects
     */
    public function lastWatchedSeries($userId, $count = 10): array
    {
        $sql = "SELECT s.`id` AS `id`, s.`name` as `name`, s.`poster_path` AS `poster_path` "
            . "FROM `serie` s "
//            . "INNER JOIN `serie_user` su ON s.`id`=su.`serie_id` AND su.`user_id`=".$userId." "
            . "INNER JOIN `serie_viewing` sv ON s.`id`=sv.`serie_id` AND sv.`user_id`=" . $userId . " AND sv.`viewed_episodes`>0 "
            . "ORDER BY sv.`modified_at` DESC LIMIT " . $count;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAll();
//        return $this->createQueryBuilder('s')
//            ->innerJoin('s.users', 'u', Expr\Join::WITH, 'u.id=' . $userId)
//            ->join('App\Entity\SerieViewing', 'sv', Expr\Join::WITH, 's=sv and sv.viewedEpisodes>0')
//            ->orderBy('sv.modifiedAt', 'DESC')
//            ->setMaxResults($count)
//            ->getQuery()
//            ->getResult();
    }

//    /**
//     * @param $userId
//     * @return int Returns number of serie owned by a user
//     */
//    public function countUserSeries($userId): int
//    {
//        $series = $this->createQueryBuilder('s')
//            ->select('s.name name')
//            ->innerJoin('s.users', 'u', Expr\Join::WITH, 'u.id=' . $userId)
//            ->getQuery()
//            ->getResult();
//        return count($series);
//    }

    /**
     * @param $userId
     * @return array [id, name] Returns the list of serie owned by a user
     */
    public function listUserSeries($userId): array
    {
        return $this->createQueryBuilder('s')
            ->select('s.id id, s.name name, s.originalName original, s.firstDateAir first_date_air')
            ->innerJoin('s.users', 'u', Expr\Join::WITH, 'u.id=' . $userId)
            ->orderBy('s.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $userId
     * @return array [] Returns an array of Serie partial objects (fields: id, serieId)
     */
    public function findMySerieIds($userId): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.users', 'u', Expr\Join::WITH, 'u.id=' . $userId)
            ->select("s.id", "s.serieId")
            ->getQuery()
            ->getResult();
    }

    public function numbers($userId): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.users', 'u', Expr\Join::WITH, 'u.id=' . $userId)
            ->select("sum(s.numberOfEpisodes) episodes, sum(s.numberOfSeasons) seasons")
            ->getQuery()
            ->getResult();
    }

    public function networks($serieIds): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.networks', 'n')
            ->select("n.id id, n.name name, n.logoPath logoPath, n.originCountry originCountry, n.networkId networkId, s.id serieId")
            ->where('s.id IN (:serieIds)')
            ->setParameter('serieIds', $serieIds)
            ->getQuery()
            ->getResult();
    }

    public function seriesCount(): int
    {
        $sql = "SELECT count(*) as count FROM `serie`";
        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();
        $result = $resultSet->fetchAssociative();
        return $result['count'];
    }

    public function seriesByTitle($query, $limit, $offset): array
    {
        $sql = "SELECT t0.name as name, t0.poster_path as poster_path, t0.first_date_air as first_date_air, t0.original_name as original_name, t0.serie_id as id, 'tv' as media_type "
            . "FROM `serie` t0 "
            . "WHERE t0.`name` LIKE '%" . $query . "%' OR  t0.`original_name` LIKE '%" . $query . "%' "
            . "ORDER BY t0.`first_date_air` DESC "
            . "LIMIT " . $limit . " OFFSET " . $offset;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();
        return $resultSet->fetchAll();
    }

}
