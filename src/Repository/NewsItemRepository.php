<?php

namespace App\Repository;

use App\Entity\NewsItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NewsItem>
 */
class NewsItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsItem::class);
    }

    /**
     * Get all news items with their property values
     */
    public function findAllWithProperties(): array
    {
        return $this->createQueryBuilder('ni')
            ->leftJoin('ni.propertyValues', 'pv')
            ->leftJoin('pv.propertyDefinition', 'pd')
            ->orderBy('ni.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get news items by status
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('ni')
            ->where('ni.status = :status')
            ->setParameter('status', $status)
            ->orderBy('ni.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get active news items
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('ni')
            ->where('ni.status = :status')
            ->andWhere('ni.activeAt <= :now OR ni.activeAt IS NULL')
            ->setParameter('status', 'published')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('ni.activeAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search news items by property value
     */
    public function searchByProperty(string $propertyName, $value): array
    {
        return $this->createQueryBuilder('ni')
            ->join('ni.propertyValues', 'pv')
            ->join('pv.propertyDefinition', 'pd')
            ->where('pd.name = :propertyName')
            ->andWhere('pd.active = :active')
            ->setParameter('propertyName', $propertyName)
            ->setParameter('active', true)
            ->andWhere('JSON_CONTAINS(pv.value, :value) = 1')
            ->setParameter('value', json_encode($value))
            ->orderBy('ni.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
