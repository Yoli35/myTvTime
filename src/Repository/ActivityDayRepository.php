<?php

namespace App\Repository;

use App\Entity\ActivityDay;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityDay>
 *
 * @method ActivityDay|null find($id, $lockMode = null, $lockVersion = null)
 * @method ActivityDay|null findOneBy(array $criteria, array $orderBy = null)
 * @method ActivityDay[]    findAll()
 * @method ActivityDay[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActivityDayRepository extends ServiceEntityRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityDay::class);
    }

    public function save(ActivityDay $entity, bool $flush = false): void
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

    public function remove(ActivityDay $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getActivityDays(int $activityId, int $offset = 0, int $limit = 999999): array
    {
        $qb = $this->createQueryBuilder('ad');
        $qb->select('ad')
            ->where('ad.activity = :activityId')
            ->setParameter('activityId', $activityId)
            ->orderBy('ad.day', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function checkChallenge($activityId, $discipline, $value, $month, $start, $end): array
    {
        if ($month) {
            $sql = "SELECT * "
                . "FROM `activity_day` "
                . "WHERE `activity_id`=" . $activityId . " "
                . "AND `" . $discipline . "` > " . $value . " "
                . "AND MONTH(`day`) = " . $month . " "
                . "ORDER BY day";
        } else {
            $sql = "SELECT * "
                . "FROM `activity_day` "
                . "WHERE `activity_id`=" . $activityId . " "
                . "AND `" . $discipline . "` > " . $value . " "
                . "AND `day` >= '" . $start . "' "
                . "AND `day` <= '" . $end . "' "
                . "ORDER BY day";
        }
//        dump($sql);
        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
    }

    public function getMonthRingCount($activityId, $month): array
    {
        $sql = "SELECT "
            . "COUNT(CASE `move_ring_completed`>0 WHEN 1 THEN 1 ELSE NULL END) as move_count, "
            . "COUNT(CASE `exercise_ring_completed`>0 WHEN 1 THEN 1 ELSE NULL END) as exercise_count, "
            . "COUNT(CASE `stand_up_ring_completed`>0 WHEN 1 THEN 1 ELSE NULL END) as stand_up_count "
            . "FROM "
            . "    activity_day "
            . "WHERE "
            . "   `activity_id`=" . $activityId . " "
            . "AND "
            . "    MONTH(`day`) = " . $month;
//        dump($sql);
        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
    }

    public function getWeekCount($activityId, $discipline, $week): array
    {
        $sql = "SELECT COUNT(*) AS count "
            . "FROM `activity_day` "
            . "WHERE `activity_id`=" . $activityId . " "
            . "AND `" . $discipline . "` > 0 "
            . "AND WEEK(`day`) = " . $week . " "
            . "ORDER BY day";
//        dump($sql);
        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
    }

    public function getCurrentMonthStats(User $user, int $month): array
    {
        $sql = 'SELECT COUNT(*) as number_of_days, '
            . 'SUM(ad.`distance`) as total_distance, AVG(ad.`distance`) as average_distance, '
            . 'SUM(ad.`steps`) as total_steps, AVG(ad.`steps`) as average_steps, '
            . 'SUM(ad.`exercise_result`) as total_exercise, AVG(ad.`exercise_result`) as average_exercise, '
            . 'SUM(ad.`move_result`) as total_move, AVG(ad.`move_result`) as average_move, '
            . 'MIN(ad.`stand_up_result`) as min_stand_up, MAX(ad.`stand_up_result`) as max_stand_up, AVG(ad.`stand_up_result`) as average_stand_up '
            . 'FROM `activity_day` ad '
            . 'INNER JOIN `activity` a ON a.id=ad.`activity_id` '
            . 'WHERE MONTH(ad.`day`)=' . $month . ' '
            . '	AND DATE(ad.`day`)<DATE(NOW())'
            . '	AND a.`user_id`=' . $user->getId();
        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
    }

    public function getLast30DaysStats(User $user): array
    {
        $sql = 'SELECT COUNT(*) as number_of_days, '
            . 'SUM(ad.`distance`) as total_distance, AVG(ad.`distance`) as average_distance, '
            . 'SUM(ad.`steps`) as total_steps, AVG(ad.`steps`) as average_steps, '
            . 'SUM(ad.`exercise_result`) as total_exercise, AVG(ad.`exercise_result`) as average_exercise, '
            . 'SUM(ad.`move_result`) as total_move, AVG(ad.`move_result`) as average_move, '
            . 'MIN(ad.`stand_up_result`) as min_stand_up, MAX(ad.`stand_up_result`) as max_stand_up, AVG(ad.`stand_up_result`) as average_stand_up, '
		    . 'DATE_SUB(DATE(NOW()), INTERVAL 1 DAY) as end, '
		    . 'DATE_SUB(DATE(NOW()), INTERVAL 1 MONTH) as start '
            . 'FROM `activity_day` ad '
            . 'INNER JOIN `activity` a ON a.id=ad.`activity_id` '
            . 'WHERE DATE(ad.`day`)>=DATE_SUB(DATE(NOW()), INTERVAL 1 MONTH) '
            . '	AND DATE(ad.`day`)<DATE(NOW())'
            . '	AND a.`user_id`=' . $user->getId();
        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
    }

//    /**
//     * @return Activity[] Returns an array of Activity objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Activity
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
