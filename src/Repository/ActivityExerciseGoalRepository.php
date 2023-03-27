<?php

namespace App\Repository;

use App\Entity\ActivityExerciseGoal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityExerciseGoal>
 *
 * @method ActivityExerciseGoal|null find($id, $lockMode = null, $lockVersion = null)
 * @method ActivityExerciseGoal|null findOneBy(array $criteria, array $orderBy = null)
 * @method ActivityExerciseGoal[]    findAll()
 * @method ActivityExerciseGoal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActivityExerciseGoalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityExerciseGoal::class);
    }

    public function save(ActivityExerciseGoal $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ActivityExerciseGoal $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
