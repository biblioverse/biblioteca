<?php

namespace App\Repository;

use App\Entity\OpdsAccess;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OpdsAccess>
 */
class OpdsAccessRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OpdsAccess::class);
    }

    public function findOneByToken(string $token): ?OpdsAccess
    {
        $qb = $this->createQueryBuilder('oa');
        $qb->where('oa.token = :token');
        $qb->setParameter('token', $token);
        $qb->join('oa.user', 'u');

        return $this->findOneBy(['token' => $token]);
    }
}
