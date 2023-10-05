<?php

namespace App\Repository;

use App\Entity\SeasonViewing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SeasonViewing>
 *
 * @method SeasonViewing|null find($id, $lockMode = null, $lockVersion = null)
 * @method SeasonViewing|null findOneBy(array $criteria, array $orderBy = null)
 * @method SeasonViewing[]    findAll()
 * @method SeasonViewing[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SeasonViewingRepository extends ServiceEntityRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($registry, SeasonViewing::class);
    }

    public function save(SeasonViewing $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SeasonViewing $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getSeasonViewings($serieViewing): array
    {
        $sql = "SELECT "
            . "sv.`id`, "
            . "sv.`air_at` AS airAt, "
            . "sv.`season_number` AS seasonNumber, "
            . "sv.`episode_count` AS episodeCount, "
            . "sv.`season_completed` AS seasonCompleted "
            . "FROM `season_viewing`sv "
            . "WHERE sv.`serie_viewing_id` = " . $serieViewing->getId() . " AND sv.`season_number` > 0 "
            . "ORDER BY sv.`season_number` ASC";

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();
        return $resultSet->fetchAllAssociative();
    }

//    /**
//     * @return SeasonViewing[] Returns an array of SeasonViewing objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?SeasonViewing
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
