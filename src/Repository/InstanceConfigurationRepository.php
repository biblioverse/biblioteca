<?php

namespace App\Repository;

use App\Entity\InstanceConfiguration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InstanceConfiguration>
 */
class InstanceConfigurationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InstanceConfiguration::class);
    }

    public function save(InstanceConfiguration $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
