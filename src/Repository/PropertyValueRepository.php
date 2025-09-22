<?php

namespace App\Repository;

use App\Entity\PropertyValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PropertyValue>
 */
class PropertyValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyValue::class);
    }

    /**
     * Get property values for a specific news item
     */
    public function findByNewsItem(int $newsItemId): array
    {
        return $this->createQueryBuilder('pv')
            ->join('pv.propertyDefinition', 'pd')
            ->where('pv.newsItem = :newsItemId')
            ->andWhere('pd.active = :active')
            ->setParameter('newsItemId', $newsItemId)
            ->setParameter('active', true)
            ->orderBy('pv.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
