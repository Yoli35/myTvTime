<?php

namespace App\Repository;

use App\Entity\WatchProvider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WatchProvider>
 *
 * @method WatchProvider|null find($id, $lockMode = null, $lockVersion = null)
 * @method WatchProvider|null findOneBy(array $criteria, array $orderBy = null)
 * @method WatchProvider[]    findAll()
 * @method WatchProvider[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WatchProviderRepository extends ServiceEntityRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($registry, WatchProvider::class);
    }

    public function save(WatchProvider $entity, bool $flush = false): void
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

    public function getWatchProviders($country = null): array
    {
        $sql = "SELECT wp.`provider_id` as id, wp.`provider_name` as name "
            . "FROM `watch_provider` wp ";
        if ($country) {
            $sql .= "WHERE wp.`display_priorities` LIKE '%$country%' ";
        }
        $sql .= "ORDER BY name";

        return $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    public function getWatchProviderList($country): array
    {
        $sql = "SELECT wp.`provider_name` as provider_name, wp.`logo_path` as logo_path, wp.`provider_id` as provider_id "
            . "FROM `watch_provider` wp "
            . "WHERE wp.`display_priorities` LIKE '%$country%' "
            . "ORDER BY wp.`provider_name` ";

        return $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
