<?php

namespace App\Repository;

use App\Entity\EpisodeViewing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EpisodeViewing>
 *
 * @method EpisodeViewing|null find($id, $lockMode = null, $lockVersion = null)
 * @method EpisodeViewing|null findOneBy(array $criteria, array $orderBy = null)
 * @method EpisodeViewing[]    findAll()
 * @method EpisodeViewing[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EpisodeViewingRepository extends ServiceEntityRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($registry, EpisodeViewing::class);
    }

    public function save(EpisodeViewing $entity, bool $flush = false): void
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

    public function remove(EpisodeViewing $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getEpisodeViewings($serieViewing): array
    {
        $sql = "SELECT "
            . "ev.`id`, "
            . "ev.`viewed_at` AS viewedAt, "
            . "sv.`season_number` AS seasonNumber,"
            . "ev.`episode_number` AS episodeNumber, "
            . "ev.`network_id` AS networkId, "
            . "ev.`network_type` AS networkType, "
            . "ev.`device_type` AS deviceType, "
            . "ev.`air_date` AS airDate, "
            . "ev.`substitute_name` AS substituteName, "
            . "ev.`vote` AS vote, "
            . "ev.`number_of_view` AS numberOfView "
            . "FROM `season_viewing` sv "
            . "INNER JOIN `episode_viewing` ev ON ev.`season_id` = sv.id "
            . "WHERE sv.`serie_viewing_id` = " . $serieViewing->getId() . " "
            . "AND sv.`season_number` > 0 "
            . "ORDER BY sv.`season_number` ASC, ev.`episode_number` ASC";

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();
        return $resultSet->fetchAllAssociative();
    }

    public function episodeUserHistory($userId, $locale, $page = 1, $limit = 40): array
    {
        $sql = "SELECT s.`id`, s.`name` as name, sln.`name` as localized_name, "
            . "     seav.`season_number` as season_number, epiv.`episode_number` as episode_number, "
            . "     epiv.`viewed_at` as viewed_at, epiv.`vote` as vote, epiv.`substitute_name` as substitute_name, "
            . "     s.`poster_path` as serie_poster_path, "
            . "     ROW_NUMBER() OVER (ORDER BY epiv.`viewed_at` DESC) as offset "
            . "FROM `episode_viewing` epiv "
            . "LEFT JOIN `season_viewing` seav ON seav.`id`=epiv.`season_id` "
            . "LEFT JOIN `serie_viewing` serv ON serv.`id`=seav.`serie_viewing_id` "
            . "LEFT JOIN `serie` s ON s.`id`=serv.`serie_id` "
            . "LEFT JOIN `serie_localized_name` sln ON sln.`serie_id`=s.`id` AND sln.`locale`='" . $locale . "' "
            . "WHERE serv.`user_id`=" . $userId . " "
            . "ORDER BY epiv.`viewed_at` DESC "
            . "LIMIT " . (($page - 1) * $limit) . "," . $limit;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();
        return $resultSet->fetchAllAssociative();
    }

    public function sevenDaysEpisodeUserHistory($userId, $date): array
    {
        $sql = "SELECT COUNT(*) as count "
            . "FROM `episode_viewing` epiv "
            . "LEFT JOIN `season_viewing` seav ON seav.`id`=epiv.`season_id` "
            . "LEFT JOIN `serie_viewing` serv ON serv.`id`=seav.`serie_viewing_id` "
            . "WHERE serv.`user_id`=" . $userId . " AND epiv.`viewed_at` >= '" . $date . "'";

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();
        return $resultSet->fetchAllAssociative();
    }

//    /**
//     * @return EpisodeViewing[] Returns an array of EpisodeViewing objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?EpisodeViewing
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
