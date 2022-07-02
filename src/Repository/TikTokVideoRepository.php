<?php

namespace App\Repository;

use App\Entity\TikTokVideo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TikTokVideo>
 *
 * @method TikTokVideo|null find($id, $lockMode = null, $lockVersion = null)
 * @method TikTokVideo|null findOneBy(array $criteria, array $orderBy = null)
 * @method TikTokVideo[]    findAll()
 * @method TikTokVideo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TikTokVideoRepository extends ServiceEntityRepository
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TikTokVideo::class);
        $this->registry = $registry;
    }

    public function add(TikTokVideo $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TikTokVideo $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findUserTikToksByDate($userId): array
    {
        $sql = 'SELECT * FROM `tik_tok_video` t0 '
            .'INNER JOIN `user_tik_tok_video` t1 ON t1.`tik_tok_video_id`=t0.`id` '
            .'WHERE t1.`user_id` = '.$userId.' '
            .'ORDER BY t0.`added_at` DESC';

        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAll();
    }

//    /**
//     * @return TikTokVideo[] Returns an array of TikTokVideo objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?TikTokVideo
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
