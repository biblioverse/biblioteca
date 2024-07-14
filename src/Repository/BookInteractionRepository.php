<?php

namespace App\Repository;

use App\Entity\BookInteraction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @extends ServiceEntityRepository<BookInteraction>
 *
 * @method BookInteraction|null find($id, $lockMode = null, $lockVersion = null)
 * @method BookInteraction|null findOneBy(array $criteria, array $orderBy = null)
 * @method BookInteraction[] findAll()
 * @method BookInteraction[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookInteractionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private Security $security)
    {
        parent::__construct($registry, BookInteraction::class);
    }

    public function getStartedBooks(): array
    {
        $results = $this->createQueryBuilder('b')
            ->andWhere('b.readPages >0')
            ->andWhere('b.finished = false')
            ->andWhere('b.hidden = false')
            ->andWhere('b.user = :val')
            ->setParameter('val', $this->security->getUser())
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
        if (!is_array($results)) {
            return [];
        }

        return $results;
    }

    public function getFavourite(): array
    {
        $results = $this->createQueryBuilder('b')
            ->andWhere('b.favorite = true')
            ->andWhere('b.hidden = false')
            ->andWhere('b.finished = false')
            ->andWhere('b.user = :val')
            ->setParameter('val', $this->security->getUser())
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
        if (!is_array($results)) {
            return [];
        }

        return $results;
    }

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
