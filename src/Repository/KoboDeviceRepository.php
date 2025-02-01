<?php

namespace App\Repository;

use App\Entity\KoboDevice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<KoboDevice>
 */
class KoboDeviceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KoboDevice::class);
    }

    public function byAccessKey(string $token): ?KoboDevice
    {
        $query = $this->createQueryBuilder('koboDevice')
            ->select('koboDevice', 'user', 'shelves')
            ->join('koboDevice.user', 'user')
            ->leftJoin('koboDevice.shelves', 'shelves')
            ->where('koboDevice.accessKey = :accessKey')
            ->setParameter('accessKey', $token)
            ->getQuery();
        /** @var KoboDevice|null $result */
        $result = $query->getOneOrNullResult();

        return $result;
    }

    /**
     * @return array<int, KoboDevice>
     */
    public function findAllByUser(?UserInterface $user = null): array
    {
        if (!$user instanceof UserInterface) {
            return [];
        }

        $query = $this->createQueryBuilder('koboDevice')
            ->select('koboDevice')
            ->where('koboDevice.user = :user')
            ->setParameter('user', $user)
            ->getQuery();
        /** @var KoboDevice[] $result */
        $result = $query->getResult();

        return $result;
    }

    public function save(KoboDevice $koboDevice): void
    {
        $this->getEntityManager()->persist($koboDevice);
        $this->getEntityManager()->flush();
    }
}
