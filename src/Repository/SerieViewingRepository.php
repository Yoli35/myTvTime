<?php

namespace App\Repository;

use App\Entity\SerieViewing;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SerieViewing>
 *
 * @method SerieViewing|null find($id, $lockMode = null, $lockVersion = null)
 * @method SerieViewing|null findOneBy(array $criteria, array $orderBy = null)
 * @method SerieViewing[]    findAll()
 * @method SerieViewing[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SerieViewingRepository extends ServiceEntityRepository
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SerieViewing::class);
        $this->registry = $registry;
    }

    public function save(SerieViewing $entity, bool $flush = false): void
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

    public function remove(SerieViewing $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    public function getSeriesToEnd(User $user, $perPage, $page): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.user = :user')
//            ->setParameter('user', $user)
//            ->andWhere('s.viewedEpisodes > 0')
//            ->andWhere('s.viewedEpisodes < s.numberOfEpisodes')
//            ->orderBy('s.modifiedAt', 'DESC')
//            ->setMaxResults($perPage)
//            ->setFirstResult(($page - 1) * $perPage)
//            ->getQuery()
//            ->getResult();
//    }

    public function userSeriesCount($userId): int
    {
        $sql = "SELECT COUNT(*) "
            . "FROM `serie_viewing` sv "
            . "WHERE sv.`user_id`=" . $userId;

        return $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()
            ->fetchOne();
    }

    public function getSeriesToStartV2(User $user, $locale, $perPage, $page): array
    {
        $sql = "SELECT "
            . "sv.`id` as id, sv.`viewed_episodes` as viewed_episodes, sv.`number_of_episodes` as number_of_episodes, "
            . "sev.`season_number` as seasonNumber, epv.`episode_number` as episodeNumber, "
            . "sv.`number_of_seasons` as number_of_seasons, sv.`serie_completed` as serie_completed, sv.`time_shifted` as time_shifted, "
            . "sv.`modified_at` as modified_at, sv.`created_at` as created_at, sv.`alert_id` as alert_id, "
            . "s.`id` as serie_id, s.`name` as name, s.`poster_path` as poster_path, s.`first_date_air` as first_date_air, "
            . "s.`original_name` as original_name, s.`overview` as overview, s.`backdrop_path` as backdrop_path, s.`serie_id` as tmdb_id, "
            . "s.`status` as serie_status, s.`created_at` as serie_created_at, s.`updated_at` as serie_updated_at, "
            . "s.upcoming_date_year as upcoming_date_year, s.upcoming_date_month as upcoming_date_month, "
            . "sv.`viewed_episodes` as viewedEpisodes, (sv.`viewed_episodes` / s.`number_of_episodes`) as progress, "
            . "epv.air_date as airDate, "
            . "f.`id` IS NOT NULL as favorite, "
            . "sln.`name` as localized_name "
            . "FROM `serie_viewing` sv "
            . "INNER JOIN `serie` s ON s.`id`=sv.`serie_id` "
            . "LEFT JOIN `episode_viewing` epv ON epv.`id`=sv.`next_episode_to_watch_id` " /*" OR sv.`next_episode_to_watch_id`=NULL "*/
            . "LEFT JOIN `season_viewing` sev ON sev.`id`=epv.`season_id` " /*"OR sv.`next_episode_to_watch_id`=NULL "*/
            . "LEFT JOIN `favorite` f ON f.`user_id`=2 AND f.`type`='serie' AND f.`media_id`=s.`id` "
            . "LEFT JOIN `serie_localized_name` sln ON sln.`serie_id`=s.`id` AND sln.`locale`='" . $locale . "' "
            . "WHERE sv.`user_id`=" . $user->getId() . " "
            . "AND sv.`viewed_episodes` = 0 "
            . "ORDER BY sv.`modified_at` DESC "
            . "LIMIT " . $perPage . " "
            . "OFFSET " . ($page - 1) * $perPage;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
    }

    public function getSeriesToEndV2($userId, $locale, $perPage, $page, $includeUpcomingEpisodes, $sort='modified_at', $order='DESC'): array
    {
        $offset = ($page - 1) * $perPage;
        $sort = in_array($sort, ['created_at', 'first_date_air', 'modified_at', 'name', 'progress']) ? $sort : 'modified_at';
        $sort = match ($sort) {
            'created_at' => 'sv.`created_at`',
            'first_date_air' => 's.`first_date_air`',
            'modified_at' => 'sv.`modified_at`',
            'name' => 's.`name`',
            'progress' => 'progress',
        };
        $sql = "SELECT "
            . "sv.`id` as id, sv.`viewed_episodes` as viewed_episodes, sv.`number_of_episodes` as number_of_episodes, "
            . "sev.`season_number` as seasonNumber, epv.`episode_number` as episodeNumber, "
            . "sv.`number_of_seasons` as number_of_seasons, sv.`serie_completed` as serie_completed, sv.`time_shifted` as time_shifted, "
            . "sv.`modified_at` as modified_at, sv.`created_at` as created_at, sv.`alert_id` as alert_id, "
            . "s.`id` as serie_id, s.`name` as name, s.`poster_path` as poster_path, s.`first_date_air` as first_date_air, "
            . "s.`original_name` as original_name, s.`overview` as overview, s.`backdrop_path` as backdrop_path, s.`serie_id` as tmdb_id, "
            . "s.`status` as serie_status, s.`created_at` as serie_created_at, s.`updated_at` as serie_updated_at, "
            . "s.upcoming_date_year as upcoming_date_year, s.upcoming_date_month as upcoming_date_month, "
            . "sv.`viewed_episodes` as viewedEpisodes, (sv.`viewed_episodes` / s.`number_of_episodes`) as progress, "
            . "epv.air_date as airDate, "
            . "f.`id` IS NOT NULL as favorite, "
            . "sln.`name` as localized_name "
            . "FROM `serie_viewing` sv "
            . "INNER JOIN `serie` s ON s.`id`=sv.`serie_id` "
            . "LEFT JOIN `episode_viewing` epv ON epv.`id`=sv.`next_episode_to_watch_id` " /*" OR sv.`next_episode_to_watch_id`=NULL "*/
            . "LEFT JOIN `season_viewing` sev ON sev.`id`=epv.`season_id` " /*"OR sv.`next_episode_to_watch_id`=NULL "*/
            . "LEFT JOIN `favorite` f ON f.`user_id`=2 AND f.`type`='serie' AND f.`media_id`=s.`id` "
            . "LEFT JOIN `serie_localized_name` sln ON sln.`serie_id`=s.`id` AND sln.`locale`='$locale' "
            . "WHERE sv.`user_id`=$userId "
            . "AND sv.`viewed_episodes` > 0 "
            . "AND sv.`viewed_episodes` < s.`number_of_episodes` ";
        if (!$includeUpcomingEpisodes)
            $sql .= "AND epv.`air_date` <= NOW() ";
        $sql .= "ORDER BY $sort $order "
            . "LIMIT $perPage "
            . "OFFSET $offset";

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
    }

    public function countSeriesToEndV2($userId, $includeUpcomingEpisodes): int
    {
        $sql = "SELECT "
            . "COUNT(sv.`id`) as number "
            . "FROM `serie_viewing` sv "
            . "INNER JOIN `serie` s ON s.`id`=sv.`serie_id` "
            . "LEFT JOIN `episode_viewing` epv ON epv.`id`=sv.`next_episode_to_watch_id` " /*" OR sv.`next_episode_to_watch_id`=NULL "*/
            . "LEFT JOIN `season_viewing` sev ON sev.`id`=epv.`season_id` " /*"OR sv.`next_episode_to_watch_id`=NULL "*/
            . "LEFT JOIN `favorite` f ON f.`user_id`=2 AND f.`type`='serie' AND f.`media_id`=s.`id` "
            . "WHERE sv.`user_id`=" . $userId . " "
            . "AND sv.`viewed_episodes` > 0 "
            . "AND sv.`viewed_episodes` < s.`number_of_episodes` ";
        if (!$includeUpcomingEpisodes)
            $sql .= "AND epv.`air_date` <= NOW() ";

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchOne();
    }

    public function getEpisodesOfTheDay($userId, $date, $locale): array
    {
        $sql = "SELECT `serie_viewing`.`serie_id`                as serie_id,
                       `serie`.`name`                            as name,
                       `serie`.`poster_path`                     as poster_path,
                       `episode_viewing`.`episode_number`        as episode_number,
                       `season_viewing`.`season_number`          as season_number,
                       `season_viewing`.`episode_count`          as episode_count,
                       `episode_viewing`.`viewed_at` IS NOT NULL as viewed,
                       `favorite`.`id` IS NOT NULL               as favorite,
                       `serie_localized_name`.`name`             as localized_name
                FROM `serie_viewing`
                         INNER JOIN `serie` ON `serie`.`id` = `serie_viewing`.`serie_id`
                         INNER JOIN `season_viewing` ON `season_viewing`.`serie_viewing_id` = `serie_viewing`.`id`
                         INNER JOIN `episode_viewing` ON `episode_viewing`.`season_id` = `season_viewing`.`id`
                         LEFT JOIN `favorite` ON `favorite`.`user_id`=$userId AND `favorite`.`type` = 'serie' AND `favorite`.`media_id` = `serie`.`id`
                         LEFT JOIN `serie_localized_name` ON `serie_localized_name`.`serie_id` = `serie`.`id` AND `serie_localized_name`.`locale` = '$locale'
                WHERE `serie_viewing`.`user_id`=$userId
                  AND `season_viewing`.`season_number` > 0
                  AND (
                    (`serie_viewing`.`time_shifted` = 0 AND `episode_viewing`.`air_date` = DATE('$date'))
                        OR (`serie_viewing`.`time_shifted` > 0 AND `episode_viewing`.`air_date` = DATE_SUB(DATE('$date'), INTERVAL `serie_viewing`.`time_shifted` DAY))
                        OR (`serie_viewing`.`time_shifted` < 0 AND `episode_viewing`.`air_date` = DATE_ADD(DATE('$date'), INTERVAL ABS(`serie_viewing`.`time_shifted`) DAY))
                    )
                ORDER BY `episode_viewing`.`air_date` DESC";

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
    }

    // Épisodes à venir
    public function getUpcomingEpisodes($userId, $perPage, $page): array
    {
        $sql = "SELECT "
            . "sv.`id` as id, sv.`viewed_episodes` as viewed_episodes, sv.`number_of_episodes` as number_of_episodes, "
            . "sv.`number_of_seasons` as season_count, sv.`time_shifted` as time_shifted, "
            . "sv.`modified_at` as modified_at, sv.`created_at` as created_at, sv.`alert_id` as alert_id, "
            . "s.`id` as serie_id, s.`name` as name, s.`backdrop_path` as backdrop_path, s.`poster_path` as poster_path, s.`first_date_air` as first_date_air, "
            . "s.`original_name` as original_name, s.`overview` as overview, s.`backdrop_path` as backdrop_path, s.`serie_id` as tmdb_id, "
            . "s.`status` as serie_status, s.`created_at` as serie_created_at, s.`updated_at` as serie_updated_at, "
            . "s.`upcoming_date_month` as upcoming_date_month, s.`upcoming_date_year` as upcoming_date_year, "
            . "f.`id` IS NOT NULL as favorite, "
            . "netw.`air_date` as air_date, netw.`episode_number` as episode_number, nstw.`season_number` as season_number, "
//            . "neta.`air_date` as air_date, neta.`episode_number` as episode_number, nsta.`season_number` as season_number, "
            . "nstw.`episode_count` as episode_count "
//            . "nsta.`episode_count` as episode_count "
            . "FROM `serie_viewing` sv "
            . "INNER JOIN `serie` s ON s.`id`=sv.`serie_id` "
            . "LEFT JOIN `favorite` f ON f.`user_id`=" . $userId . " AND f.`type`='serie' AND f.`media_id`=s.`id` "
            . "LEFT JOIN `episode_viewing` neta ON neta.`id`=sv.`next_episode_to_air_id` "
            . "LEFT JOIN `season_viewing` nsta ON nsta.`id`=neta.`season_id` "
            . "LEFT JOIN `episode_viewing` netw ON netw.`id`=sv.`next_episode_to_watch_id` "
            . "LEFT JOIN `season_viewing` nstw ON nstw.`id`=netw.`season_id` "
            . "WHERE sv.`user_id`=" . $userId . " "
            . "AND netw.`air_date` IS NOT NULL "
            . "AND neta.`air_date` IS NOT NULL "
            . "AND neta.`viewed_at` IS NULL "
            . "AND netw.`viewed_at` IS NULL "
            . "AND netw.`episode_number` <= neta.`episode_number` "
            . "AND nstw.`season_number` = nsta.`season_number` "
            . "ORDER BY netw.`air_date` ASC "
            . "LIMIT " . $perPage . " "
            . "OFFSET " . ($page - 1) * $perPage;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
    }

    // Épisodes à venir
    public function countUpcomingEpisodes($userId): int
    {
        $sql = "SELECT "
            . "COUNT(sv.`id`) as number "
            . "FROM `serie_viewing` sv "
            . "INNER JOIN `serie` s ON s.`id`=sv.`serie_id` "
            . "LEFT JOIN `favorite` f ON f.`user_id`=" . $userId . " AND f.`type`='serie' AND f.`media_id`=s.`id` "
            . "LEFT JOIN `episode_viewing` neta ON neta.`id`=sv.`next_episode_to_air_id` "
            . "Left JOIN `season_viewing` nsta ON nsta.`id`=neta.`season_id` "
            . "LEFT JOIN `episode_viewing` netw ON netw.`id`=sv.`next_episode_to_watch_id` "
            . "Left JOIN `season_viewing` nstw ON nstw.`id`=netw.`season_id` "
            . "WHERE sv.`user_id`=" . $userId . " "
            . "AND neta.`air_date` IS NOT NULL "
            . "AND netw.`air_date` IS NOT NULL "
            . "AND netw.`episode_number` = neta.`episode_number` "
            . "AND nstw.`season_number` = nsta.`season_number` ";

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchOne();
    }

    public function countUserSeriesToEnd(User $user): float|bool|int|string|null
    {
        try {
            $count = $this->createQueryBuilder('s')
                ->select('COUNT(s.id)')
                ->andWhere('s.user = :user')
                ->setParameter('user', $user)
                ->andWhere('s.viewedEpisodes > 0')
                ->andWhere('s.viewedEpisodes < s.numberOfEpisodes')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException) {
            $count = 0;
        }

        return $count;
//        $sql = "SELECT COUNT(*) FROM `serie_viewing` s "
//            . "WHERE s.`user_id`=" . $user->getId() . " "
//            . "AND s.`viewed_episodes` > 0 "
//            . "AND s.`viewed_episodes` < s.`number_of_episodes`";
//
//        $em = $this->registry->getManager();
//        $statement = $em->getConnection()->prepare($sql);
//        $resultSet = $statement->executeQuery();
//
//        return $resultSet->fetchOne();
    }

    public function getSerieIds($serieViewingIds): array
    {
//        return $this->createQueryBuilder('s')
//            ->select('s.serieId')
//            ->andWhere('s.id in (:id)')
//            ->setParameter('id', $serieViewingIds)
//            ->getQuery()
//            ->getResult();
        $array = "(" . implode(',', $serieViewingIds) . ")";
        $sql = "SELECT sv.`serie_id` as id "
            . "FROM `serie_viewing` sv "
            . "WHERE sv.id IN " . $array . " "
            . "ORDER BY sv.`modified_at` DESC";

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
    }

    // Séries à venir (avec pagination)
    public function upcomingSeries($userId, $perPage, $page): array
    {
        $sql = "SELECT "
            . "sv.`id` as id, sv.`viewed_episodes` as viewed_episodes, sv.`number_of_episodes` as number_of_episodes, "
            . "sv.`number_of_seasons` as number_of_seasons, "
            . "sv.`modified_at` as modified_at, sv.`created_at` as created_at, sv.`alert_id` as alert_id, "
            . "s.`id` as serie_id, s.`name` as name, s.`poster_path` as poster_path, s.`first_date_air` as first_date_air, s.`status` as status, "
            . "s.`original_name` as original_name, s.`overview` as overview, s.`backdrop_path` as backdrop_path, s.`serie_id` as tmdb_id, "
            . "s.`upcoming_date_month` as upcoming_date_month, s.`upcoming_date_year` as upcoming_date_year, "
            . "n.`name` as network_name, n.`network_id` as network_id, n.`logo_path` as network_logo_path,"
            . "f.`id` IS NOT NULL as favorite "
            . "FROM `serie_viewing` sv "
            . "INNER JOIN `serie` s ON s.`id`=sv.`serie_id` "
            . "INNER JOIN `serie_networks` sn ON s.`id`=sn.`serie_id` "
            . "INNER JOIN `networks` n ON sn.`networks_id`=n.`id` "
            . "LEFT JOIN `favorite` f ON f.`user_id`=2 AND f.`type`='serie' AND f.`media_id`=s.`id` "
            . "WHERE sv.`user_id`=" . $userId . " "
            . "AND s.`first_date_air` IS NULL "
            . "ORDER BY s.`created_at` DESC "
            . "LIMIT " . $perPage . " "
            . "OFFSET " . ($page - 1) * $perPage;

        return $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    // Séries à venir (avec pagination)
    public function countUpcomingSeries($userId): int
    {
        $sql = "SELECT "
            . "COUNT(sv.`id`) as number "
            . "FROM `serie_viewing` sv "
            . "INNER JOIN `serie` s ON s.`id`=sv.`serie_id` "
            . "INNER JOIN `serie_networks` sn ON s.`id`=sn.`serie_id` "
            . "INNER JOIN `networks` n ON sn.`networks_id`=n.`id` "
            . "LEFT JOIN `favorite` f ON f.`user_id`=2 AND f.`type`='serie' AND f.`media_id`=s.`id` "
            . "WHERE sv.`user_id`=" . $userId . " "
            . "AND s.`first_date_air` IS NULL";

        return $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()
            ->fetchOne();
    }

    public function getSeriesToWatch($userId, $locale, $perPage, $page): array
    {
        $sql = "SELECT sv.`id` as id, s.`id` as serie_id, s.`name` as name, s.`original_name` as original_name, s.`poster_path`, "
            . "sln.`name` as localized_name, sev.`season_number` as season_number, ev.`episode_number` as episode_number, ev.`air_date` as air_date, sv.`time_shifted` as time_shifted "
            . "FROM `serie_viewing` sv "
            . "INNER JOIN `user` u ON u.`id`=sv.`user_id` "
            . "INNER JOIN `episode_viewing` ev ON ev.`id`=sv.`next_episode_to_watch_id` AND ((ev.`air_date`<=NOW() AND sv.`time_shifted`=0) OR (ev.`air_date`<=DATE_SUB(NOW(), INTERVAL 1 DAY) AND sv.`time_shifted`=1)) "
            . "INNER JOIN `season_viewing` sev ON sev.`id`=ev.`season_id` "
            . "LEFT JOIN `serie` s ON s.`id`=sv.`serie_id` "
            . "LEFT JOIN `serie_localized_name` sln ON sln.`serie_id`=s.`id` AND sln.`locale`='" . $locale . "' "
            . "WHERE u.id=" . $userId . " "
            . "ORDER BY ev.`air_date` DESC "
            . "LIMIT " . $perPage . " "
            . "OFFSET " . ($page - 1) * $perPage;

        return $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    public function getUserSeriesProgressAndLocalizedName($userId, $serieIds, $locale): array
    {
        $serieIds = "(" . implode(',', $serieIds) . ")";

        $sql = "SELECT s.`serie_id` as id, sv.`viewed_episodes` / sv.`number_of_episodes` as progress, sln.`name` as localized_name "
            . "FROM `serie_viewing` sv "
            . "INNER JOIN `serie` s ON s.`id`=sv.`serie_id` "
            . "LEFT JOIN `serie_localized_name` sln ON sln.`serie_id`=s.`id` AND sln.`locale`='" . $locale . "' "
            . "WHERE sv.`user_id`=" . $userId . " AND s.`serie_id` IN " . $serieIds;

        return $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    public function userSeries($userId, $locale): array
    {
        $sql = "SELECT s.`id`, s.`name` as name "
            . "    FROM `serie_viewing` sv "
            . "    LEFT JOIN `serie` s ON sv.`serie_id`=s.`id` "
            . "    LEFT JOIN `serie_localized_name` sln ON sln.`serie_id`=s.`id` AND sln.`locale`='fr' "
            . "    WHERE sv.`user_id`=2 AND sln.`name` IS NULL "
            . "UNION "
            . "    SELECT s.`id`, CONCAT(CONCAT(sln.name, \" - \"), s.`name`) as name "
            . "    FROM `serie_viewing` sv "
            . "    LEFT JOIN `serie` s ON sv.`serie_id`=s.`id` "
            . "    LEFT JOIN `serie_localized_name` sln ON sln.`serie_id`=s.`id` AND sln.`locale`='fr' "
            . "    WHERE sv.`user_id`=2 AND sln.`name` IS NOT NULL "
            . "ORDER BY name";
        return $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    public function viewedSeries($userId, $serieId): array
    {
        $sql = "SELECT * "
            . "FROM `serie_viewing` sv "
            . "INNER JOIN `serie` s ON s.`serie_id`=" . $serieId . " "
            . "WHERE sv.`user_id`=" . $userId . " AND sv.`serie_id`=s.id";
        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();
        return $resultSet->fetchAllAssociative();
    }

    public function userSeriesIds($userId): array
    {
        $sql = "SELECT id "
            . "FROM `serie_viewing` sv "
            . "WHERE sv.`user_id`=" . $userId;

        return $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
