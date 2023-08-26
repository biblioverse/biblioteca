<?php

namespace App\Repository;

use App\Entity\BookInteraction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BookInteraction>
 *
 * @method BookInteraction|null find($id, $lockMode = null, $lockVersion = null)
 * @method BookInteraction|null findOneBy(array $criteria, array $orderBy = null)
 * @method BookInteraction[]    findAll()
 * @method BookInteraction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookInteractionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BookInteraction::class);
    }

//    /**
//     * @return BookInteraction[] Returns an array of BookInteraction objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?BookInteraction
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
