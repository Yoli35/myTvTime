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
            . "INNER JOIN `serie_user` su ON s.`id`=su.`serie_id` AND su.`user_id`=".$userId." "
            . "INNER JOIN `serie_viewing` sv ON s.`id`=sv.`serie_id` AND sv.`viewed_episodes`>0 "
            . "ORDER BY sv.`modified_at` DESC LIMIT ".$count;

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
            ->select('s.id id, s.name name, s.originalName original, s.firstDateAir firstDateAir')
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

//    public function getViewing(): array
//    {
//
//    }

//    public function findOneBySomeField($value): ?Serie
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
