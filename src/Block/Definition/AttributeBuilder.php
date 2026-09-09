<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Block\Definition;

/**
 * Fluent description of a custom block's editable fields.
 *
 * Each call adds one control to the editor sidebar (or, with `inContent: true`, inline on the
 * canvas) and one attribute to the block's stored JSON. No JavaScript is involved.
 */
final class AttributeBuilder
{
    /** @var array<string, AttributeDefinition> */
    private array $attributes = [];

    public function text(string $name, string $label, string $default = '', ?string $help = null, ?string $placeholder = null, bool $inContent = false): self
    {
        return $this->add(new AttributeDefinition($name, AttributeDefinition::CONTROL_TEXT, $label, 'string', $default, $help, $placeholder, inContent: $inContent));
    }

    public function textarea(string $name, string $label, string $default = '', ?string $help = null, ?string $placeholder = null): self
    {
        return $this->add(new AttributeDefinition($name, AttributeDefinition::CONTROL_TEXTAREA, $label, 'string', $default, $help, $placeholder));
    }

    /**
     * Formatted text edited directly on the canvas (bold, links, inline code…).
     */
    public function richText(string $name, string $label, string $default = '', ?string $help = null, ?string $placeholder = null, bool $inContent = true): self
    {
        return $this->add(new AttributeDefinition($name, AttributeDefinition::CONTROL_RICH_TEXT, $label, 'string', $default, $help, $placeholder, inContent: $inContent));
    }

    public function html(string $name, string $label, string $default = '', ?string $help = null): self
    {
        return $this->add(new AttributeDefinition($name, AttributeDefinition::CONTROL_HTML, $label, 'string', $default, $help));
    }

    public function number(string $name, string $label, int|float $default = 0, ?float $min = null, ?float $max = null, ?float $step = null, ?string $help = null): self
    {
        return $this->add(new AttributeDefinition($name, AttributeDefinition::CONTROL_NUMBER, $label, 'number', $default, $help, min: $min, max: $max, step: $step));
    }

    public function range(string $name, string $label, int|float $default = 0, float $min = 0, float $max = 100, float $step = 1, ?string $help = null): self
    {
        return $this->add(new AttributeDefinition($name, AttributeDefinition::CONTROL_RANGE, $label, 'number', $default, $help, min: $min, max: $max, step: $step));
    }

    public function toggle(string $name, string $label, bool $default = false, ?string $help = null): self
    {
        return $this->add(new AttributeDefinition($name, AttributeDefinition::CONTROL_TOGGLE, $label, 'boolean', $default, $help));
    }

    /**
     * @param array<string, string> $choices value => label
     */
    public function select(string $name, string $label, array $choices, ?string $default = null, ?string $help = null): self
    {
        $default ??= (string) array_key_first($choices);

        return $this->add(new AttributeDefinition($name, AttributeDefinition::CONTROL_SELECT, $label, 'string', $default, $help, choices: $choices));
    }

    public function color(string $name, string $label, ?string $default = null, ?string $help = null): self
    {
        return $this->add(new AttributeDefinition($name, AttributeDefinition::CONTROL_COLOR, $label, 'string', $default, $help));
    }

    /**
     * Media-library picker. Stored as `{"id": int|null, "url": string, "alt": string, "width": int, "height": int}`.
     */
    public function image(string $name, string $label, ?string $help = null): self
    {
        return $this->add(new AttributeDefinition($name, AttributeDefinition::CONTROL_IMAGE, $label, 'object', null, $help));
    }

    /**
     * Link picker. Stored as `{"url": string, "label": string, "newTab": bool}`.
     */
    public function url(string $name, string $label, ?string $help = null): self
    {
        return $this->add(new AttributeDefinition($name, AttributeDefinition::CONTROL_URL, $label, 'object', null, $help));
    }

    public function add(AttributeDefinition $definition): self
    {
        $this->attributes[$definition->name] = $definition;

        return $this;
    }

    /**
     * @return array<string, AttributeDefinition>
     */
    public function all(): array
    {
        return $this->attributes;
    }
}
