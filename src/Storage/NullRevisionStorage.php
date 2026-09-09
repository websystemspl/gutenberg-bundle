<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Storage;

/**
 * Used when revisions are disabled or Doctrine is not installed, so callers never have to
 * null-check the storage service.
 */
final class NullRevisionStorage implements RevisionStorageInterface
{
    public function save(string $ownerClass, string $ownerId, string $field, string $content, ?string $author = null): void
    {
    }

    public function history(string $ownerClass, string $ownerId, string $field, int $limit = 20): array
    {
        return [];
    }
}
