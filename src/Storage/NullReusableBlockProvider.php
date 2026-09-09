<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Storage;

/**
 * Used when Doctrine is not installed: reusable blocks simply do not exist.
 */
final class NullReusableBlockProvider implements ReusableBlockProviderInterface
{
    public function findContent(string|int $reference): ?string
    {
        return null;
    }

    public function listReferences(): array
    {
        return [];
    }
}
