<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Storage;

/**
 * Resolves `core/block` references to the block content they point at.
 */
interface ReusableBlockProviderInterface
{
    /**
     * @return string|null the stored block content, or null when the reference is dangling
     */
    public function findContent(string|int $reference): ?string;

    /**
     * @return array<string, string> reference => title, for the editor's inserter
     */
    public function listReferences(): array;
}
