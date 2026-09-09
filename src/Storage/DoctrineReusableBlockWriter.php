<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Storage;

use Doctrine\ORM\EntityManagerInterface;
use WebSystems\GutenbergBundle\Entity\ReusableBlock;
use WebSystems\GutenbergBundle\Repository\ReusableBlockRepository;

/**
 * Stores a new reusable block, deriving a unique slug from its title.
 */
final class DoctrineReusableBlockWriter implements ReusableBlockWriterInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ReusableBlockRepository $repository,
    ) {
    }

    public function create(string $title, string $content): array
    {
        $title = trim($title);

        if ('' === $title) {
            throw new \InvalidArgumentException('A reusable block needs a title.');
        }

        $block = (new ReusableBlock())
            ->setTitle($title)
            ->setSlug($this->uniqueSlug($title))
            ->setContent($content);

        $this->entityManager->persist($block);
        $this->entityManager->flush();

        return ['ref' => (int) $block->getId(), 'title' => $block->getTitle()];
    }

    private function uniqueSlug(string $title): string
    {
        $base = $this->slugify($title);
        $slug = $base;
        $suffix = 1;

        while (null !== $this->repository->findOneBy(['slug' => $slug])) {
            $slug = $base.'-'.++$suffix;
        }

        return $slug;
    }

    private function slugify(string $title): string
    {
        // Transliterate where the intl extension allows it, then fall back to a plain filter.
        if (class_exists(\Transliterator::class)) {
            $transliterator = \Transliterator::create('Any-Latin; Latin-ASCII; Lower()');
            $title = $transliterator?->transliterate($title) ?? $title;
        }

        $slug = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', $title));
        $slug = trim($slug, '-');

        return '' !== $slug ? substr($slug, 0, 150) : 'blok-'.bin2hex(random_bytes(4));
    }
}
