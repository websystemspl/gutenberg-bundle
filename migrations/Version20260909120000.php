<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates the tables owned by web-systems/gutenberg-bundle.
 *
 * Written against the Schema API rather than raw SQL so the same migration runs on every
 * platform Doctrine supports.
 */
final class Version20260909120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the Gutenberg reusable block and revision tables.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('gutenberg_reusable_block')) {
            $table = $schema->createTable('gutenberg_reusable_block');
            $table->addColumn('id', 'integer', ['autoincrement' => true]);
            $table->addColumn('slug', 'string', ['length' => 191]);
            $table->addColumn('title', 'string', ['length' => 191]);
            $table->addColumn('content', 'text');
            $table->addColumn('created_at', 'datetime_immutable');
            $table->addColumn('updated_at', 'datetime_immutable');
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['slug'], 'uniq_gutenberg_reusable_block_slug');
        }

        if (!$schema->hasTable('gutenberg_revision')) {
            $table = $schema->createTable('gutenberg_revision');
            $table->addColumn('id', 'integer', ['autoincrement' => true]);
            $table->addColumn('owner_class', 'string', ['length' => 191]);
            $table->addColumn('owner_id', 'string', ['length' => 64]);
            $table->addColumn('field', 'string', ['length' => 64]);
            $table->addColumn('content', 'text');
            $table->addColumn('author', 'string', ['length' => 191, 'notnull' => false]);
            $table->addColumn('created_at', 'datetime_immutable');
            $table->setPrimaryKey(['id']);
            $table->addIndex(['owner_class', 'owner_id', 'field'], 'idx_gutenberg_revision_owner');
        }
    }

    public function down(Schema $schema): void
    {
        foreach (['gutenberg_revision', 'gutenberg_reusable_block'] as $table) {
            if ($schema->hasTable($table)) {
                $schema->dropTable($table);
            }
        }
    }

    public function isTransactional(): bool
    {
        return false; // MySQL/MariaDB cannot roll back DDL anyway.
    }
}
