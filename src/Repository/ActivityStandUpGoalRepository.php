<?php

namespace App\Repository;

use App\Entity\ActivityStandUpGoal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityStandUpGoal>
 *
 * @method ActivityStandUpGoal|null find($id, $lockMode = null, $lockVersion = null)
 * @method ActivityStandUpGoal|null findOneBy(array $criteria, array $orderBy = null)
 * @method ActivityStandUpGoal[]    findAll()
 * @method ActivityStandUpGoal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActivityStandUpGoalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityStandUpGoal::class);
    }

    public function save(ActivityStandUpGoal $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ActivityStandUpGoal $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
