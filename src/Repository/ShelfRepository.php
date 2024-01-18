<?php

namespace App\Repository;

use App\Entity\Kobo;
use App\Entity\Shelf;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Shelf>
 *
 * @method Shelf|null find($id, $lockMode = null, $lockVersion = null)
 * @method Shelf|null findOneBy(array $criteria, array $orderBy = null)
 * @method Shelf[] findAll()
 * @method Shelf[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShelfRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Shelf::class);
    }

    //    /**
    //     * @return Shelf[] Returns an array of Shelf objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Shelf
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function findByKoboAndId(Kobo $kobo, string $shelfId): ?Shelf
    {
        $qb = $this->createQueryBuilder('shelf')
            ->select('shelf')
            ->join('shelf.kobo', 'kobo')
            ->where('kobo.id = :koboId')
            ->setParameter('koboId', $kobo->getId())
            ->andWhere('shelf.id = :shelfId')
            ->setParameter('shelfId', $shelfId)
            ->setMaxResults(1);

        /** @var Shelf|null $result */
        $result = $qb->getQuery()->getOneOrNullResult();

        return $result;
    }

    public function findByKoboAndName(Kobo $kobo, mixed $name): ?Shelf
    {
        $qb = $this->createQueryBuilder('shelf')
            ->select('shelf')
            ->join('shelf.kobo', 'kobo')
            ->where('kobo.id = :id')
            ->setParameter('id', $kobo->getId())
            ->andWhere('shelf.name = :name')
            ->setParameter('name', $name)
            ->setMaxResults(1);

        /** @var Shelf|null $result */
        $result = $qb->getQuery()->getOneOrNullResult();

        return $result;
    }
}
