<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Repository;

use App\Entity\Alert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Alert>
 *
 * @method Alert|null find($id, $lockMode = null, $lockVersion = null)
 * @method Alert|null findOneBy(array $criteria, array $orderBy = null)
 * @method Alert[]    findAll()
 * @method Alert[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AlertRepository extends ServiceEntityRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($registry, Alert::class);
    }

    public function save(Alert $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Alert $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function alertOfTheDay($userId): array
    {
        $sql = "SELECT a.`id` as id, a.`message` as message, a.`provider_id` as provider_id, a.`episode_number` as alert_episode_number, a.`season_number` as alert_season_number, 
                    sv.`number_of_episodes` as number_of_episodes, sv.`number_of_seasons` as number_of_seasons, sv.`viewed_episodes` as viewed_episodes, 
                    s.`name` as name, s.`original_name` as original_name, s.`serie_id` as tmdb_id, s.`origin_country` as origin_country_array,
                    s.`direct_link` as direct_link, 
                    sln.`name` as localized_name, 
                    se.`poster_path` as season_poster_path, ep.`still_path` as episode_still_path, 
                    wp.`provider_name` as provider_name, wp.`logo_path` as provider_logo_path 
            FROM `alert` a 
            INNER JOIN `serie_viewing` sv ON sv.id=a.`serie_viewing_id` 
            LEFT JOIN `serie` s ON s.id=sv.`serie_id` 
            LEFT JOIN `serie_localized_name` sln ON sln.`serie_id`=s.`id` 
            LEFT JOIN `season` se ON se.`series_id`=s.`id` AND se.`season_number`=a.`season_number` 
            LEFT JOIN `episode` ep ON ep.`season_id`=se.`id` AND ep.`episode_number`=a.`episode_number` 
            LEFT JOIN `watch_provider` wp ON wp.`provider_id` = a.`provider_id` 
            WHERE a.`user_id`=$userId
              AND a.`activated`=1
              AND DATE(`a`.`date`) = DATE(NOW())";

        return $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    public function getAlerts(int $userID, string $locale): array
    {
        $sql = "SELECT a.date          as date,
                       s.id            as id,
                       s.backdrop_path as banner,
                       a.created_at    as createdAt,
                       sln.name        as localizedName,
                       a.message       as message,   
                       s.name          as name,
                       s.poster_path   as posterPath,
                       a.provider_id   as providerId,
                       s.id            as serieId,
                       sv.id           as serieViewingId,
                       sv.time_shifted as timeShifted
                FROM alert a
                         LEFT JOIN serie_viewing sv ON sv.id = a.serie_viewing_id
                         LEFT JOIN serie s ON s.id = sv.serie_id
                         LEFT JOIN serie_localized_name sln ON s.id = sln.serie_id AND sln.locale = '$locale'
                WHERE a.user_id = $userID";

        return $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
