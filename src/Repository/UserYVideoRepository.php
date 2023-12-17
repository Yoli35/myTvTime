<?php

namespace App\Repository;

use App\Entity\UserYVideo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserYVideo>
 *
 * @method UserYVideo|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserYVideo|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserYVideo[]    findAll()
 * @method UserYVideo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserYVideoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserYVideo::class);
    }

    public function save(UserYVideo $entity, bool $flush = false): void
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

    public function remove(?UserYVideo $yvideo, $flush = false): void
    {
        if ($yvideo) {
            $this->getEntityManager()->remove($yvideo);
            if ($flush) {
                $this->getEntityManager()->flush();
            }
        }
    }

    public function getVisibilityFromList($userId, $videoIds): mixed
    {
        return $this->createQueryBuilder('y')
            ->innerJoin('y.user', 'u')
            ->innerJoin('y.video', 'v')
            ->where('u.id = :userId')
            ->andWhere('v.id in (:videoIds)')
            ->setParameter('userId', $userId)
            ->setParameter('videoIds', $videoIds)
            ->getQuery()
            ->getResult();
    }

    public function findByUserAndVideo($userId, $videoId): ?UserYVideo
    {
        return $this->createQueryBuilder('y')
            ->innerJoin('y.user', 'u')
            ->innerJoin('y.video', 'v')
            ->where('u.id = :userId')
            ->andWhere('v.id = :videoId')
            ->setParameter('userId', $userId)
            ->setParameter('videoId', $videoId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
