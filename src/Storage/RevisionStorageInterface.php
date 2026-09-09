<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Storage;

use WebSystems\GutenbergBundle\Entity\Revision;

/**
 * Persists snapshots of block content so editors can roll back.
 *
 * The default implementation is Doctrine-backed; applications without an ORM get a no-op
 * implementation, and may substitute their own (object storage, event store, …).
 */
interface RevisionStorageInterface
{
    public function save(string $ownerClass, string $ownerId, string $field, string $content, ?string $author = null): void;

    /**
     * @return list<Revision>
     */
    public function history(string $ownerClass, string $ownerId, string $field, int $limit = 20): array;
}
