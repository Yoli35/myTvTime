<?php

namespace App\Repository;

use App\Entity\UserTvPreference;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserTvPreference>
 *
 * @method UserTvPreference|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserTvPreference|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserTvPreference[]    findAll()
 * @method UserTvPreference[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserTvPreferenceRepository extends ServiceEntityRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($registry, UserTvPreference::class);
    }

    public function findOrCreate($user, $tvGenre): UserTvPreference
    {
        $preference = $this->findOneBy(['user' => $user, 'tvGenre' => $tvGenre]);
        if (!$preference) {
            $preference = new UserTvPreference();
            $preference->setUser($user);
            $preference->setTvGenre($tvGenre);
            $preference->setVitality(0);
            $this->_em->persist($preference);
            $this->_em->flush();
        }

        return $preference;
    }

    public function save(UserTvPreference $preference): void
    {
        $this->_em->persist($preference);
    }

    public function flush(): void
    {
        $this->_em->flush();
    }

    public function getUserTvPreferences($user): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.id, u.vitality, t.id as tvGenreId, t.name as tvGenreName')
            ->andWhere('u.user = :user')
            ->setParameter('user', $user)
            ->innerJoin('u.tvGenre', 't', Expr\Join::WITH, 't = u.tvGenre')
            ->orderBy('u.vitality', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getUserTvPreferencesSQL($userId): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT tg.`id`, tg.`name`, utp.`vitality` '
            . 'FROM `user_tv_preference` utp '
            . 'LEFT JOIN `tv_genre` tg ON tg.`id`=utp.`tv_genre_id` '
            . 'WHERE utp.`user_id`='.$userId.' '
            . 'ORDER BY utp.`vitality` DESC ';
        $em = $this->registry->getManager();
        $statement = $em->getConnection()->prepare($sql);
        $resultSet = $statement->executeQuery();

        return $resultSet->fetchAllAssociative();
    }

//    /**
//     * @return UserTvPreference[] Returns an array of UserTvPreference objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?UserTvPreference
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
