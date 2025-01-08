<?php

namespace App\Repository;

use App\Entity\AiModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AiModel>
 */
class AiModelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AiModel::class);
    }

    public function findAllIndexed(): array
    {
        $allModels = $this->findAll();
        $models = [];
        foreach ($allModels as $model) {
            $models[$model->getId()] = $model;
        }

        return $models;
    }
}
