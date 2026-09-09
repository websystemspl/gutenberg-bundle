<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Storage;

use WebSystems\GutenbergBundle\Entity\ReusableBlock;
use WebSystems\GutenbergBundle\Repository\ReusableBlockRepository;

/**
 * Reads reusable blocks from the `gutenberg_reusable_block` table, accepting either the
 * numeric id or the human-readable slug as a reference.
 */
final class DoctrineReusableBlockProvider implements ReusableBlockProviderInterface
{
    public function __construct(
        private readonly ReusableBlockRepository $repository,
    ) {
    }

    public function findContent(string|int $reference): ?string
    {
        $block = is_numeric($reference)
            ? $this->repository->find((int) $reference)
            : $this->repository->findOneBy(['slug' => $reference]);

        return $block?->getContent();
    }

    public function listReferences(): array
    {
        $references = [];

        foreach ($this->repository->findBy([], ['title' => 'ASC']) as $block) {
            /** @var ReusableBlock $block */
            $references[(string) $block->getId()] = $block->getTitle();
        }

        return $references;
    }
}
