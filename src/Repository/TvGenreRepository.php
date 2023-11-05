<?php

namespace App\Repository;

use App\Entity\TvGenre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TvGenre>
 *
 * @method TvGenre|null find($id, $lockMode = null, $lockVersion = null)
 * @method TvGenre|null findOneBy(array $criteria, array $orderBy = null)
 * @method TvGenre[]    findAll()
 * @method TvGenre[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TvGenreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TvGenre::class);
    }

    public function findOrCreate($name, $tmdbId): TvGenre
    {
        $genre = $this->findOneBy(['tmdbId' => $tmdbId]);
        if (!$genre) {
            $genre = new TvGenre($name, $tmdbId);
            $this->_em->persist($genre);
            $this->_em->flush();
        }

        return $genre;
    }

//    /**
//     * @return TvGenre[] Returns an array of TvGenre objects
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

//    public function findOneBySomeField($value): ?TvGenre
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
