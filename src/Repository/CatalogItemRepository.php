<?php

namespace App\Repository;

use App\Entity\CatalogItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CatalogItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method CatalogItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method CatalogItem[]    findAll()
 * @method CatalogItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CatalogItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CatalogItem::class);
    }

    // /**
    //  * @return CatalogItem[] Returns an array of CatalogItem objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CatalogItem
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
