<?php

namespace App\Repository;

use App\Entity\SerieViewing;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    public function getSeriesToEnd(User $user, $perPage, $page): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->setParameter('user', $user)
            ->andWhere('s.viewedEpisodes > 0')
            ->andWhere('s.viewedEpisodes < s.numberOfEpisodes')
            ->orderBy('s.modifiedAt', 'DESC')
            ->setMaxResults($perPage)
            ->setFirstResult(($page - 1) * $perPage)
            ->getQuery()
            ->getResult();
//        $sql = "SELECT * FROM `serie_viewing` s "
//            . "WHERE s.`user_id`=" . $user->getId() . " "
//            . "AND s.`viewed_episodes` > 0 "
//            . "AND s.`viewed_episodes` < s.`number_of_episodes` "
//            . "ORDER BY s.`modified_at` DESC "
//            . "LIMIT " . $perPage . " "
//            . "OFFSET " . ($page - 1) * $perPage;
//
//        $em = $this->registry->getManager();
//        $statement = $em->getConnection()->prepare($sql);
//        $resultSet = $statement->executeQuery();
//
//        return $resultSet->fetchAll();
    }

    public function getEpisodesOfTheDay($userId, $today, $yesterday, $page, $perPage): array
    {
        $sql = "SELECT `serie_viewing`.`serie_id`, `serie`.`name`, `serie`.`poster_path`, `episode_viewing`.`episode_number`, `season_viewing`.`season_number`, `season_viewing`.`episode_count` "
            . "FROM `serie_viewing` "
            . "INNER JOIN `serie` ON `serie`.`id`=`serie_viewing`.`serie_id` "
            . "INNER JOIN `season_viewing` ON `season_viewing`.`serie_viewing_id` = `serie_viewing`.`id` "
            . "INNER JOIN `episode_viewing` ON `episode_viewing`.`season_id` = `season_viewing`.`id` "
            . "WHERE `user_id`= " . $userId . " "
            . "    AND `season_viewing`.`season_number`>0 "
            . "    AND `season_viewing`.`season_completed`=0 "
            . "    AND ((`episode_viewing`.`air_date` = '" . $today . "' AND `serie_viewing`.`time_shifted` = 0) OR (`episode_viewing`.`air_date` = '" . $yesterday . "' AND `serie_viewing`.`time_shifted` = 1)) "
            . "ORDER BY `episode_viewing`.`air_date` DESC "
            . "LIMIT " . $perPage . " OFFSET " . ($page - 1) * $perPage;

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAll();
    }

    public function countUserSeriesToEnd(User $user)
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.user = :user')
            ->setParameter('user', $user)
            ->andWhere('s.viewedEpisodes > 0')
            ->andWhere('s.viewedEpisodes < s.numberOfEpisodes')
            ->getQuery()
            ->getSingleScalarResult();
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
        $sql = "SELECT t0.`serie_id` as 'id' FROM `serie_viewing` t0 "
            . "WHERE t0.`id` IN (" . implode(',', $serieViewingIds) . ") "
            . "ORDER BY t0.`modified_at` DESC";

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAll();
    }
}
