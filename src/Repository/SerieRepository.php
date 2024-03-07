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

    public function userSeries($userId, $locale, $sort, $order, $offset, $limit): array
    {
        $order = match ($sort) {
            'name' => 's.`name` ' . $order,
            'firstDateAir' => 's.`first_date_air` ' . $order,
            'modifiedAt' => 'sv.`modified_at` ' . $order,
            'updatedAt' => 's.`updated_at` ' . $order,
            default => 's.`id` ' . $order,
        };
        $sql = "SELECT s.`id` as id, s.`serie_id` as tmdbId, sv.`id` as svId, "
            . "s.`name` as name, sln.`name` as localizedName, "
            . "sev.`season_number` as seasonNumber, epv.`episode_number` as episodeNumber, "
            . "s.`first_date_air` as firstDateAir, s.`created_at` as createdAt, s.`updated_at` as updatedAt, "
            . "s.`status` as status, s.`overview` as overview, sao.`overview` as alternateOverview, sao.`overviews` as alternateOverviews, "
            . "s.`number_of_episodes` as numberOfEpisodes, s.`number_of_seasons` as numberOfSeasons, "
            . "sv.`viewed_episodes` as viewedEpisodes, (sv.`viewed_episodes` / s.`number_of_episodes`) as progress, "
            . "sv.`serie_completed` as serieCompleted, "
            . "sv.time_shifted as isTimeShifted, "
            . "epv.air_date as airDate, "
            . "s.`original_name` as originalName, s.`origin_country` as originCountry, s.`episode_durations` as episodeDurations, "
            . "s.`upcoming_date_year` as upcomingDateYear, s.`upcoming_date_month` as upcomingDateMonth, "
            . "s.`direct_link` as directLink, "
            . "f.`id` IS NOT NULL as favorite, "
            . "s.`poster_path` as posterPath, s.`backdrop_path` as backdropPath "
            . "FROM `serie` s "
            . "INNER JOIN `serie_user` su ON su.`user_id`=" . $userId . " AND su.`serie_id`=s.`id` "
            . "INNER JOIN `serie_viewing` sv ON sv.`user_id`=2 AND sv.`serie_id`=s.`id` "
            . "LEFT JOIN `episode_viewing` epv ON epv.`id`=sv.`next_episode_to_watch_id` " /*" OR sv.`next_episode_to_watch_id`=NULL "*/
            . "LEFT JOIN `season_viewing` sev ON sev.`id`=epv.`season_id` " /*"OR sv.`next_episode_to_watch_id`=NULL "*/
            . "LEFT JOIN `serie_localized_name` sln ON sln.`serie_id`=s.`id` AND sln.`locale`='" . $locale . "' "
            . "LEFT JOIN `serie_alternate_overview` sao ON sao.`series_id`=s.`id` AND sao.`locale`='" . $locale . "' "
            . "LEFT JOIN `favorite` f ON f.`user_id`=2 AND f.`type`='serie' AND f.`media_id`=s.`id` "
            . "ORDER BY " . $order . " "
            . "LIMIT " . $limit . " OFFSET " . $offset;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
    }

    public function userSeriesNetworks($seriesIds): array
    {
        $sql = "SELECT s.`id` as serie_id, n.`name` as name, n.`logo_path` as logoPath, n.`origin_country` as originCountry "
            . "FROM `serie` s "
            . "INNER JOIN `serie_networks` sn ON sn.`serie_id`=s.`id` "
            . "INNER JOIN `networks` n ON n.`id`=sn.`networks_id` "
            . "WHERE s.`id` IN (" . implode(',', $seriesIds) . ")";

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        $arr = $resultSet->fetchAllAssociative();
        $networks = [];
        foreach ($arr as $network) {
            $serieId = $network['serie_id'];
            if (!isset($networks[$serieId])) {
                $networks[$serieId] = [];
            }
            $networks[$serieId][] = $network;
        }

        return $networks;
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

        return $resultSet->fetchAllAssociative();
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
//     * @return int Returns number of series owned by a user
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
     * @return array [id, name] Returns the list of series owned by a user
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
        $sql = "SELECT count(*) as count "
            . "FROM `serie`";
        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();
        $result = $resultSet->fetchAllAssociative();
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
        return $resultSet->fetchAllAssociative();
    }

    public function getCountries($userId): array
    {
        $sql = "SELECT s.`origin_country` AS `origin_country` "
            . "FROM `serie` s "
            . "INNER JOIN `serie_viewing` sv ON sv.`serie_id`=s.`id` AND sv.`user_id`=" . $userId . " "
            . "GROUP BY s.`origin_country` "
            . "ORDER BY s.`origin_country` ASC";

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();
        return $resultSet->fetchAllAssociative();
    }

    public function getSeriesFromCountry($userId, $countryCode, $offset, $limit): array
    {
        if ($countryCode == 'all') $countryCode = '';
        $sql = 'SELECT s.id as id, s.name as name, s.poster_path as poster_path, s.serie_id as serie_id, '
            . 's.first_date_air as first_date_air, s.number_of_episodes as number_of_episodes, '
            . 's.original_name as original_name, s.status as status, s.origin_country as origin_country, '
            . 'sv.viewed_episodes as viewed_episodes, sv.serie_completed as serie_completed, '
            . 'sn.name as localized_name, '
            . 'epv.viewed_at as started_at '
            . 'FROM `serie` s '
            . 'INNER JOIN `serie_viewing` sv ON sv.`serie_id`=s.`id` AND sv.`user_id`=' . $userId . ' '
            . 'LEFT JOIN `serie_localized_name` sn ON sn.`serie_id`=s.`id` '
            . 'LEFT JOIN `season_viewing` sev ON sev.`serie_viewing_id`=sv.id AND sev.`season_number`=1 '
            . 'LEFT JOIN `episode_viewing` epv ON epv.`season_id`=sev.id AND epv.`episode_number`=1 '
            . 'WHERE s.`origin_country` LIKE "%' . $countryCode . '%" '
            . 'ORDER BY s.`first_date_air` DESC '
            . 'LIMIT ' . $limit . ' OFFSET ' . $offset;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();
        return $resultSet->fetchAllAssociative();
    }

    public function seriesFromCountryCount($userId, $countryCode): int
    {
        if ($countryCode == 'all') $countryCode = '';
        $sql = "SELECT count(*) as count "
            . "FROM `serie` s "
            . 'INNER JOIN `serie_viewing` sv ON sv.`serie_id`=s.`id` AND sv.`user_id`=' . $userId . ' '
            . 'WHERE s.`origin_country` LIKE "%' . $countryCode . '%" ';

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();
        $result = $resultSet->fetchAllAssociative();
        return $result[0]['count'];
    }

    public function seriesByIdGreaterThan($minId): array
    {
        $sql = "SELECT s.`serie_id` as tmdb_id "
            . "FROM `serie` s "
            . "WHERE s.`id` > " . $minId . " "
            . "ORDER BY s.`id` ASC";

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();
        return $resultSet->fetchAllAssociative();
    }
}
