<?php

namespace App\Repository;

use App\Entity\YoutubeVideoComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<YoutubeVideoComment>
 *
 * @method YoutubeVideoComment|null find($id, $lockMode = null, $lockVersion = null)
 * @method YoutubeVideoComment|null findOneBy(array $criteria, array $orderBy = null)
 * @method YoutubeVideoComment[]    findAll()
 * @method YoutubeVideoComment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class YoutubeVideoCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, YoutubeVideoComment::class);
    }

//    /**
//     * @return YoutubeVideoComment[] Returns an array of YoutubeVideoComment objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('y')
//            ->andWhere('y.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('y.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?YoutubeVideoComment
//    {
//        return $this->createQueryBuilder('y')
//            ->andWhere('y.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
