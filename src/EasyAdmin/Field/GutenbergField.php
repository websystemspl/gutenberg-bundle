<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\EasyAdmin\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Symfony\Contracts\Translation\TranslatableInterface;
use WebSystems\GutenbergBundle\Form\Type\GutenbergType;

/**
 * EasyAdmin field that edits a property with the block editor.
 *
 *     yield GutenbergField::new('content', 'Treść')->setHeight(800);
 *
 * The editor assets are attached to the field itself, so no extra configureAssets() call is
 * needed in the CRUD controller.
 */
final class GutenbergField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_RENDER_ON_DETAIL = 'renderOnDetail';
    public const OPTION_EXCERPT_LENGTH = 'excerptLength';

    public static function new(string $propertyName, TranslatableInterface|string|bool|null $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplatePath('@WebSystemsGutenberg/easyadmin/field_gutenberg.html.twig')
            ->setFormType(GutenbergType::class)
            ->addCssClass('field-gutenberg')
            ->addCssFiles('bundles/websystemsgutenberg/editor.css')
            ->addJsFiles('bundles/websystemsgutenberg/editor.js')
            ->setCustomOption(self::OPTION_RENDER_ON_DETAIL, true)
            ->setCustomOption(self::OPTION_EXCERPT_LENGTH, 120)
        ;
    }

    public function setHeight(int $pixels): self
    {
        return $this->setFormTypeOption('editor_height', $pixels);
    }

    /**
     * @param list<string> $blocks
     */
    public function setAllowedBlocks(array $blocks): self
    {
        return $this->setFormTypeOption('allowed_blocks', $blocks);
    }

    /**
     * @param list<array<string, mixed>> $template Gutenberg block template applied to empty documents
     */
    public function setBlockTemplate(array $template, string|false|null $lock = null): self
    {
        return $this
            ->setFormTypeOption('block_template', $template)
            ->setFormTypeOption('template_lock', $lock);
    }

    /**
     * Render the actual blocks on the detail page instead of the raw block markup.
     */
    public function renderOnDetail(bool $render = true): self
    {
        return $this->setCustomOption(self::OPTION_RENDER_ON_DETAIL, $render);
    }

    public function setExcerptLength(int $length): self
    {
        return $this->setCustomOption(self::OPTION_EXCERPT_LENGTH, $length);
    }

    public static function isRenderedOnDetail(FieldDto $field): bool
    {
        return (bool) $field->getCustomOption(self::OPTION_RENDER_ON_DETAIL);
    }
}
