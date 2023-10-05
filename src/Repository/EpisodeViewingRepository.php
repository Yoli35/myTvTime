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
