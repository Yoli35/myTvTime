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
    public function __construct(private readonly ManagerRegistry $registry)
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

    public function getUserVideoSeries($userId): array
    {
        $sql = "SELECT yvs.id, yvs.title " /*, yvs.format, yvs.regex, yvs.matches*/
            . "FROM user_yvideo uyv "
            . "INNER JOIN youtube_video_series yvs ON yvs.id = uyv.series_id "
            . "WHERE uyv.user_id=" . $userId . " AND uyv.series_id IS NOT NULL"
            . " GROUP BY uyv.series_id";

        return $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
