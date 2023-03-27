<?php

namespace App\Repository;

use App\Entity\ActivityMoveGoal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityMoveGoal>
 *
 * @method ActivityMoveGoal|null find($id, $lockMode = null, $lockVersion = null)
 * @method ActivityMoveGoal|null findOneBy(array $criteria, array $orderBy = null)
 * @method ActivityMoveGoal[]    findAll()
 * @method ActivityMoveGoal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActivityMoveGoalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityMoveGoal::class);
    }

    public function save(ActivityMoveGoal $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ActivityMoveGoal $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
