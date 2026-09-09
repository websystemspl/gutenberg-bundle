<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Block;

use Symfony\Contracts\Service\Attribute\Required;
use Twig\Environment;
use WebSystems\GutenbergBundle\Attribute\AsBlock;
use WebSystems\GutenbergBundle\Block\Definition\AttributeBuilder;
use WebSystems\GutenbergBundle\Renderer\Context\BlockRenderContext;

/**
 * Base class for custom blocks rendered through Twig.
 *
 * A complete block is one class with an #[AsBlock] attribute plus a template:
 *
 *     #[AsBlock(name: 'app/hero', title: 'Hero', template: 'blocks/hero.html.twig')]
 *     final class HeroBlock extends AbstractBlockType
 *     {
 *         public function configureAttributes(AttributeBuilder $builder): void
 *         {
 *             $builder->richText('title', 'Nagłówek', 'Witaj')
 *                     ->image('background', 'Tło');
 *         }
 *     }
 */
abstract class AbstractBlockType implements BlockTypeInterface
{
    private Environment $twig;

    #[Required]
    public function setTwig(Environment $twig): void
    {
        $this->twig = $twig;
    }

    public function configureAttributes(AttributeBuilder $builder): void
    {
    }

    public function render(BlockRenderContext $context): string
    {
        return $this->twig->render($this->getTemplate(), [
            'attributes' => $context->attributes,
            'inner' => $context->innerHtml,
            'block' => $context->block,
            'preview' => $context->preview,
            'context' => $context->context,
        ]);
    }

    /**
     * Defaults to the `template` argument of #[AsBlock]; override to compute it dynamically.
     */
    protected function getTemplate(): string
    {
        $attributes = (new \ReflectionClass(static::class))->getAttributes(AsBlock::class);

        if ([] === $attributes || null === $template = $attributes[0]->newInstance()->template) {
            throw new \LogicException(\sprintf('Block "%s" must declare a template: either pass "template:" to #[AsBlock] or override %s::getTemplate().', static::class, self::class));
        }

        return $template;
    }

    protected function getTwig(): Environment
    {
        return $this->twig;
    }
}
