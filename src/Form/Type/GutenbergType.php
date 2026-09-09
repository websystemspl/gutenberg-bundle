<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Form field that edits its value with the Gutenberg block editor.
 *
 * The underlying value stays a plain string of block markup, so the field works with any
 * persistence layer and degrades to a textarea when JavaScript is unavailable.
 *
 *     $builder->add('content', GutenbergType::class, ['editor_height' => 800]);
 */
final class GutenbergType extends AbstractType
{
    /**
     * @param list<string>              $defaultAllowedBlocks
     * @param list<array<string, mixed>> $defaultTemplate
     */
    public function __construct(
        private readonly int $defaultHeight = 720,
        private readonly array $defaultAllowedBlocks = [],
        private readonly array $defaultTemplate = [],
        private readonly ?UrlGeneratorInterface $urlGenerator = null,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'editor_height' => $this->defaultHeight,
                'allowed_blocks' => $this->defaultAllowedBlocks,
                'block_template' => $this->defaultTemplate,
                'template_lock' => null,
                'show_inspector' => true,
                'compound' => false,
            ])
            ->setAllowedTypes('editor_height', 'int')
            ->setAllowedTypes('allowed_blocks', 'string[]')
            ->setAllowedTypes('block_template', 'array')
            ->setAllowedTypes('show_inspector', 'bool')
            ->setAllowedValues('template_lock', [null, false, 'all', 'insert', 'contentOnly'])
        ;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['editor_height'] = $options['editor_height'];
        $view->vars['config_url'] = $this->configUrl();
        $view->vars['editor_settings'] = array_filter([
            'allowedBlocks' => $options['allowed_blocks'],
            'template' => $options['block_template'],
            'templateLock' => $options['template_lock'],
            'showInspector' => $options['show_inspector'],
        ], static fn (mixed $value): bool => null !== $value && [] !== $value);
    }

    /**
     * The editor boots from this endpoint. When the bundle routes are not imported the field
     * still renders and degrades to a plain textarea instead of throwing.
     */
    private function configUrl(): ?string
    {
        try {
            return $this->urlGenerator?->generate('web_systems_gutenberg_config');
        } catch (\Throwable) {
            return null;
        }
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'gutenberg';
    }
}
