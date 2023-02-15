<?php

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Article;
use Doctrine\ORM\QueryBuilder;

class FilterPublishedArticleQueryExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        if (Article::class === $resourceClass) {
            $queryBuilder->andWhere(sprintf("%s.isPublished = true", $queryBuilder->getRootAliases()[0]));
        }
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, Operation $operation = null, array $context = []): void
    {
        if (Article::class === $resourceClass) {
            $queryBuilder->andWhere(sprintf("%s.isPublished = true", $queryBuilder->getRootAliases()[0]));
        }
    }
}