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

    public function getSeriesToStartV2(User $user, $perPage, $page): array
    {
        $sql = "SELECT "
            . "sv.`id` as id, sv.`viewed_episodes` as viewed_episodes, sv.`number_of_episodes` as number_of_episodes, "
            . "sv.`number_of_seasons` as number_of_seasons, sv.`serie_completed` as serie_completed, sv.`time_shifted` as time_shifted, "
            . "sv.`modified_at` as modified_at, sv.`created_at` as created_at, sv.`alert_id` as alert_id, "
            . "s.`id` as serie_id, s.`name` as name, s.`poster_path` as poster_path, s.`first_date_air` as first_date_air, "
            . "s.`original_name` as original_name, s.`overview` as overview, s.`backdrop_path` as backdrop_path, s.`serie_id` as tmdb_id, "
            . "s.`status` as serie_status, s.`created_at` as serie_created_at, s.`updated_at` as serie_updated_at, "
            . "s.upcoming_date_year as upcoming_date_year, s.upcoming_date_month as upcoming_date_month, "
            . "f.`id` IS NOT NULL as favorite "
            . "FROM `serie_viewing` sv "
            . "INNER JOIN `serie` s ON s.`id`=sv.`serie_id` "
            . "LEFT JOIN `favorite` f ON f.`user_id`=2 AND f.`type`='serie' AND f.`media_id`=s.`id` "
            . "WHERE sv.`user_id`=" . $user->getId() . " "
            . "AND sv.`viewed_episodes` = 0 "
            . "ORDER BY sv.`modified_at` DESC "
            . "LIMIT " . $perPage . " "
            . "OFFSET " . ($page - 1) * $perPage;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAll();
    }

    public function getSeriesToEndV2($userId, $perPage, $page): array
    {
        $sql = "SELECT "
            . "sv.`id` as id, sv.`viewed_episodes` as viewed_episodes, sv.`number_of_episodes` as number_of_episodes, "
            . "sv.`number_of_seasons` as number_of_seasons, sv.`serie_completed` as serie_completed, sv.`time_shifted` as time_shifted, "
            . "sv.`modified_at` as modified_at, sv.`created_at` as created_at, sv.`alert_id` as alert_id, "
            . "s.`id` as serie_id, s.`name` as name, s.`poster_path` as poster_path, s.`first_date_air` as first_date_air, "
            . "s.`original_name` as original_name, s.`overview` as overview, s.`backdrop_path` as backdrop_path, s.`serie_id` as tmdb_id, "
            . "s.`status` as serie_status, s.`created_at` as serie_created_at, s.`updated_at` as serie_updated_at, "
//            . "n.`name` as network_name, n.`network_id` as network_id, n.`logo_path` as network_logo_path, "
            . "f.`id` IS NOT NULL as favorite "
            . "FROM `serie_viewing` sv "
            . "INNER JOIN `serie` s ON s.`id`=sv.`serie_id` "
//            . "INNER JOIN `serie_networks` sn ON s.`id`=sn.`serie_id` "
//            . "INNER JOIN `networks` n ON sn.`networks_id`=n.`id` "
            . "LEFT JOIN `favorite` f ON f.`user_id`=2 AND f.`type`='serie' AND f.`media_id`=s.`id` "
            . "WHERE sv.`user_id`=" . $userId . " "
            . "AND sv.`viewed_episodes` > 0 "
            . "AND sv.`viewed_episodes` < s.`number_of_episodes` "
            . "ORDER BY sv.`modified_at` DESC "
            . "LIMIT " . $perPage . " "
            . "OFFSET " . ($page - 1) * $perPage;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAll();
    }

    public function getEpisodesOfTheDay($userId, $today, $yesterday, $page, $perPage): array
    {
        $sql = "SELECT `serie_viewing`.`serie_id`, `serie`.`name`, `serie`.`poster_path`, `episode_viewing`.`episode_number`, `season_viewing`.`season_number`, `season_viewing`.`episode_count`, `episode_viewing`.`viewed_at` IS NOT NULL AS viewed "
            . "FROM `serie_viewing` "
            . "INNER JOIN `serie` ON `serie`.`id`=`serie_viewing`.`serie_id` "
            . "INNER JOIN `season_viewing` ON `season_viewing`.`serie_viewing_id` = `serie_viewing`.`id` "
            . "INNER JOIN `episode_viewing` ON `episode_viewing`.`season_id` = `season_viewing`.`id` "
            . "WHERE `user_id`= " . $userId . " "
            . "    AND `season_viewing`.`season_number`>0 "
//            . "    AND `season_viewing`.`season_completed`=0 "
            . "    AND ((`episode_viewing`.`air_date` = '" . $today . "' AND `serie_viewing`.`time_shifted` = 0) OR (`episode_viewing`.`air_date` = '" . $yesterday . "' AND `serie_viewing`.`time_shifted` = 1)) "
            . "ORDER BY `episode_viewing`.`air_date` DESC "
            . "LIMIT " . $perPage . " OFFSET " . ($page - 1) * $perPage;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAll();
    }

    // Épisodes à venir paginés
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

        return $resultSet->fetchAll();
    }

    // Épisodes à venir paginés
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

    public function countUserSeriesToEnd(User $user)
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

        return $resultSet->fetchAll();
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
            . "WHERE sv.`user_id`=" .$userId . " "
            . "AND s.`first_date_air` IS NULL "
            . "ORDER BY s.`created_at` DESC "
            . "LIMIT " . $perPage . " "
            . "OFFSET " . ($page - 1) * $perPage;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAll();
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
            . "WHERE sv.`user_id`=" .$userId . " "
            . "AND s.`first_date_air` IS NULL";

        return $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()
            ->fetchOne();
    }
}
