<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use WebSystems\GutenbergBundle\Repository\RevisionRepository;

/**
 * A point-in-time snapshot of one block-editor field of one entity.
 *
 * Revisions are stored polymorphically (owner class + identifier + field name) so that the
 * bundle never needs to know about the host application's entities.
 */
#[ORM\Entity(repositoryClass: RevisionRepository::class)]
#[ORM\Table(name: 'gutenberg_revision')]
#[ORM\Index(name: 'idx_gutenberg_revision_owner', columns: ['owner_class', 'owner_id', 'field'])]
class Revision
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 191)]
    private string $ownerClass;

    #[ORM\Column(length: 64)]
    private string $ownerId;

    #[ORM\Column(length: 64)]
    private string $field;

    #[ORM\Column(type: Types::TEXT)]
    private string $content;

    #[ORM\Column(length: 191, nullable: true)]
    private ?string $author;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $ownerClass, string $ownerId, string $field, string $content, ?string $author = null)
    {
        $this->ownerClass = $ownerClass;
        $this->ownerId = $ownerId;
        $this->field = $field;
        $this->content = $content;
        $this->author = $author;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwnerClass(): string
    {
        return $this->ownerClass;
    }

    public function getOwnerId(): string
    {
        return $this->ownerId;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
