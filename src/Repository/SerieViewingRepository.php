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
}
