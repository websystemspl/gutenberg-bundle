<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Storage;

use Doctrine\ORM\EntityManagerInterface;
use WebSystems\GutenbergBundle\Entity\Revision;
use WebSystems\GutenbergBundle\Repository\RevisionRepository;

/**
 * Stores revisions in the `gutenberg_revision` table, keeping only the newest $limit entries
 * per owner field.
 */
final class DoctrineRevisionStorage implements RevisionStorageInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RevisionRepository $repository,
        private readonly int $limit = 20,
    ) {
    }

    public function save(string $ownerClass, string $ownerId, string $field, string $content, ?string $author = null): void
    {
        $latest = $this->repository->findForOwner($ownerClass, $ownerId, $field, 1)[0] ?? null;

        if (null !== $latest && $latest->getContent() === $content) {
            return; // Nothing changed: do not clutter the history.
        }

        $this->entityManager->persist(new Revision($ownerClass, $ownerId, $field, $content, $author));
        $this->entityManager->flush();

        $this->repository->pruneOwner($ownerClass, $ownerId, $field, $this->limit);
    }

    public function history(string $ownerClass, string $ownerId, string $field, int $limit = 20): array
    {
        return $this->repository->findForOwner($ownerClass, $ownerId, $field, $limit);
    }
}
