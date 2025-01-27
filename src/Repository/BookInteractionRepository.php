<?php

namespace App\Repository;

use App\Entity\BookInteraction;
use App\Enum\ReadingList;
use App\Enum\ReadStatus;
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
    public function __construct(ManagerRegistry $registry, private readonly Security $security)
    {
        parent::__construct($registry, BookInteraction::class);
    }

    public function getStartedBooks(): array
    {
        $results = $this->createQueryBuilder('b')
            ->andWhere('b.readPages >0')
            ->andWhere('b.readStatus = :read_status')
            ->andWhere('b.readingList != :reading_list')
            ->andWhere('b.user = :val')
            ->setParameter('val', $this->security->getUser())
            ->setParameter('read_status', ReadStatus::Started)
            ->setParameter('reading_list', ReadingList::Ignored)
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

    /**
     * @return array<BookInteraction>
     */
    public function getFavourite(?int $max = null, bool $hideFinished = true): array
    {
        $qb = $this->createQueryBuilder('b')
            ->andWhere('b.readingList = :to_read');
        $qb->setParameter('to_read', ReadingList::ToRead);
        if ($hideFinished) {
            $qb->andWhere('b.readingList != :finished');
            $qb->setParameter('finished',ReadStatus::Finished);
        }

        $qb->andWhere('b.readingList != :hidden')
            ->setParameter('hidden', ReadingList::Ignored)
            ->andWhere('b.user = :val')
            ->setParameter('val', $this->security->getUser())
            ->orderBy('b.created', 'ASC')
            ->setMaxResults($max);

        /** @var int|BookInteraction[] $results */
        $results = $qb->getQuery()->getResult();
        if (!is_array($results)) {
            return [];
        }

        return $results;
    }
}
