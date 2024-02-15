<?php

namespace App\Repository;

use App\Entity\SerieAlternateOverview;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SerieAlternateOverview>
 *
 * @method SerieAlternateOverview|null find($id, $lockMode = null, $lockVersion = null)
 * @method SerieAlternateOverview|null findOneBy(array $criteria, array $orderBy = null)
 * @method SerieAlternateOverview[]    findAll()
 * @method SerieAlternateOverview[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SerieAlternateOverviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SerieAlternateOverview::class);
    }

    public function save(SerieAlternateOverview $serieAlternateOverview, $flush = false): void
    {
        $this->_em->persist($serieAlternateOverview);
        if ($flush) {
            $this->_em->flush();
        }
    }
}
