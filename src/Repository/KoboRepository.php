<?php

namespace App\Repository;

use App\Entity\Kobo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<Kobo>
 */
class KoboRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Kobo::class);
    }

    public function byAccessKey(string $token): ?Kobo
    {
        $query = $this->createQueryBuilder('kobo')
            ->select('kobo', 'user')
            ->join('kobo.user', 'user')
            ->where('kobo.accessKey = :accessKey')
            ->setParameter('accessKey', $token)
            ->getQuery();
        /** @var Kobo|null $result */
        $result = $query->getOneOrNullResult();

        return $result;
    }

    /**
     * @param UserInterface|null $user
     * @return array<int,Kobo>
     */
    public function findAllByUser(UserInterface $user = null): array
    {
        if ($user === null) {
            return [];
        }

        $query = $this->createQueryBuilder('kobo')
            ->select('kobo')
            ->where('kobo.user = :user')
            ->setParameter('user', $user)
            ->getQuery();
        /** @var Kobo[] $result */
        $result = $query->getResult();

        return $result;
    }
}
