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
            ->select('koboDevice', 'user')
            ->join('koboDevice.user', 'user')
            ->where('koboDevice.accessKey = :accessKey')
            ->setParameter('accessKey', $token)
            ->getQuery();
        /** @var KoboDevice|null $result */
        $result = $query->getOneOrNullResult();

        return $result;
    }

    /**
     * @param UserInterface|null $user
     * @return array<int, KoboDevice>
     */
    public function findAllByUser(?UserInterface $user = null): array
    {
        if ($user === null) {
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
}
