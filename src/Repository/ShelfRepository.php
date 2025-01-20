<?php

namespace App\Repository;

use App\Entity\KoboDevice;
use App\Entity\Shelf;
use App\Entity\User;
use App\Kobo\SyncToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
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

    public function findByKoboAndId(KoboDevice $koboDevice, string $shelfId): ?Shelf
    {
        $qb = $this->createQueryBuilder('shelf')
            ->select('shelf')
            ->join('shelf.kobosDevices', 'kobosDevice')
            ->where('kobosDevice.id = :koboDeviceId')
            ->setParameter('koboDeviceId', $koboDevice->getId())
            ->andWhere('shelf.id = :shelfId')
            ->setParameter('shelfId', $shelfId)
            ->setMaxResults(1);

        /** @var Shelf|null $result */
        $result = $qb->getQuery()->getOneOrNullResult();

        return $result;
    }

    /**
     * @return Shelf[]
     */
    public function findManualShelvesForUser(User $user): array
    {
        $qb = $this->createQueryBuilder('shelf')
            ->select('shelf')
            ->where('shelf.user = :user')
            ->setParameter('user', $user)
            ->andWhere('shelf.queryFilter is null')
            ->andWhere('shelf.queryString is null');

        return $qb->getQuery()->getResult();
    }

    public function findByKoboAndName(KoboDevice $koboDevice, string $name): ?Shelf
    {
        $qb = $this->createQueryBuilder('shelf')
            ->select('shelf')
            ->join('shelf.koboDevices', 'koboDevice')
            ->where('koboDevice.id = :id')
            ->setParameter('id', $koboDevice->getId())
            ->andWhere('shelf.name = :name')
            ->setParameter('name', $name)
            ->setMaxResults(1);

        /** @var Shelf|null $result */
        $result = $qb->getQuery()->getOneOrNullResult();

        return $result;
    }

    /**
     * @return array<Shelf>
     */
    public function getShelvesToSync(KoboDevice $koboDevice, SyncToken $syncToken): array
    {
        $qb = $this->createQueryBuilder('shelf')
            ->select('shelf')
            ->join('shelf.koboDevices', 'koboDevice')
            ->where('koboDevice.id = :id')
            ->setParameter('id', $koboDevice->getId());

        if ($syncToken->tagLastModified instanceof \DateTimeInterface) {
            $qb->andWhere($qb->expr()->orX(
                'shelf.updated > :tagLastModified',
                'shelf.created > :tagLastModified'
            ))
                ->setParameter('tagLastModified', $syncToken->tagLastModified);
        }

        /** @var Shelf[] $result */
        $result = $qb->getQuery()->getResult();

        return $result;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByKoboAndUuid(KoboDevice $koboDevice, string $uuid): ?Shelf
    {
        $qb = $this->createQueryBuilder('shelf')
            ->select('shelf')
            ->join('shelf.koboDevices', 'koboDevice')
            ->where('koboDevice.id = :id')
            ->andWhere('shelf.uuid = :uuid')
            ->setParameter('id', $koboDevice->getId())
            ->setMaxResults(1)
            ->setParameter('uuid', $uuid);

        /** @var Shelf|null $result */
        $result = $qb->getQuery()->getOneOrNullResult();

        return $result;
    }

    /**
     * @return int Number of affected rows
     */
    public function deleteByName(string $name): int
    {
        $query = $this->createQueryBuilder('shelf')
            ->where('shelf.name = :name')
            ->setParameter('name', $name)
            ->delete()
            ->getQuery();

        /** @var int $nbAffectedRows */
        $nbAffectedRows = $query->getSingleScalarResult();

        return $nbAffectedRows;
    }

    public function flush(): void
    {
        $this->_em->flush();
    }
}
