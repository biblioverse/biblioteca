<?php

namespace App\Repository;

use App\Entity\EreaderEmail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<EreaderEmail>
 */
class EreaderEmailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EreaderEmail::class);
    }

    /**
     * @return array<int, EreaderEmail>
     */
    public function findAllByUser(?UserInterface $user = null): array
    {
        if (!$user instanceof UserInterface) {
            return [];
        }

        $query = $this->createQueryBuilder('ereaderEmail')
            ->select('ereaderEmail')
            ->where('ereaderEmail.user = :user')
            ->orderBy('ereaderEmail.name', 'ASC')
            ->setParameter('user', $user)
            ->getQuery();
        /** @var EreaderEmail[] $result */
        $result = $query->getResult();

        return $result;
    }

    public function save(EreaderEmail $ereaderEmail): void
    {
        $this->getEntityManager()->persist($ereaderEmail);
        $this->getEntityManager()->flush();
    }
}
