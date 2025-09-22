<?php

namespace App\Repository;

use App\Entity\PropertyDefinition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PropertyDefinition>
 */
class PropertyDefinitionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyDefinition::class);
    }

    /**
     * Get all active property definitions ordered by sort order
     */
    public function findAllActiveOrdered(): array
    {
        return $this->createQueryBuilder('pd')
            ->where('pd.active = :active')
            ->setParameter('active', true)
            ->orderBy('pd.sortOrder', 'ASC')
            ->addOrderBy('pd.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get property definitions by type
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('pd')
            ->where('pd.type = :type')
            ->andWhere('pd.active = :active')
            ->setParameter('type', $type)
            ->setParameter('active', true)
            ->orderBy('pd.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
