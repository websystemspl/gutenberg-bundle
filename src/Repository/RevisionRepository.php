<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use WebSystems\GutenbergBundle\Entity\Revision;

/**
 * @extends ServiceEntityRepository<Revision>
 */
class RevisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Revision::class);
    }

    /**
     * @return list<Revision>
     */
    public function findForOwner(string $ownerClass, string $ownerId, string $field, int $limit = 20): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.ownerClass = :class AND r.ownerId = :id AND r.field = :field')
            ->setParameter('class', $ownerClass)
            ->setParameter('id', $ownerId)
            ->setParameter('field', $field)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function pruneOwner(string $ownerClass, string $ownerId, string $field, int $keep): int
    {
        $survivors = array_map(
            static fn (Revision $revision): ?int => $revision->getId(),
            $this->findForOwner($ownerClass, $ownerId, $field, $keep),
        );

        $qb = $this->createQueryBuilder('r')
            ->delete()
            ->andWhere('r.ownerClass = :class AND r.ownerId = :id AND r.field = :field')
            ->setParameter('class', $ownerClass)
            ->setParameter('id', $ownerId)
            ->setParameter('field', $field);

        if ([] !== $survivors) {
            $qb->andWhere('r.id NOT IN (:survivors)')->setParameter('survivors', $survivors);
        }

        return (int) $qb->getQuery()->execute();
    }
}
