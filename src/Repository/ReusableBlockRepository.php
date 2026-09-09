<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use WebSystems\GutenbergBundle\Entity\ReusableBlock;

/**
 * @extends ServiceEntityRepository<ReusableBlock>
 */
class ReusableBlockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReusableBlock::class);
    }
}
