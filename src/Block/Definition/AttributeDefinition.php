<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Block\Definition;

/**
 * One editable field of a custom block.
 *
 * Serves double duty: it is the JSON schema handed to the editor (which builds the control
 * automatically) and the default/coercion source used when rendering server-side.
 */
final class AttributeDefinition
{
    public const CONTROL_TEXT = 'text';
    public const CONTROL_TEXTAREA = 'textarea';
    public const CONTROL_RICH_TEXT = 'richtext';
    public const CONTROL_NUMBER = 'number';
    public const CONTROL_RANGE = 'range';
    public const CONTROL_TOGGLE = 'toggle';
    public const CONTROL_SELECT = 'select';
    public const CONTROL_COLOR = 'color';
    public const CONTROL_IMAGE = 'image';
    public const CONTROL_URL = 'url';
    public const CONTROL_HTML = 'html';

    /**
     * @param string                               $control   One of the CONTROL_* constants
     * @param 'string'|'number'|'boolean'|'object' $type      Storage type inside the block delimiter's JSON
     * @param array<string, string>                $choices   value => label, for CONTROL_SELECT
     * @param bool                                 $inContent Render the control inline on the canvas instead of the sidebar
     */
    public function __construct(
        public readonly string $name,
        public readonly string $control,
        public readonly string $label,
        public readonly string $type = 'string',
        public readonly mixed $default = null,
        public readonly ?string $help = null,
        public readonly ?string $placeholder = null,
        public readonly array $choices = [],
        public readonly ?float $min = null,
        public readonly ?float $max = null,
        public readonly ?float $step = null,
        public readonly bool $inContent = false,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toEditorSchema(): array
    {
        $schema = [
            'name' => $this->name,
            'control' => $this->control,
            'label' => $this->label,
            'type' => $this->type,
            'default' => $this->default,
            'inContent' => $this->inContent,
        ];

        foreach (['help' => $this->help, 'placeholder' => $this->placeholder, 'min' => $this->min, 'max' => $this->max, 'step' => $this->step] as $key => $value) {
            if (null !== $value) {
                $schema[$key] = $value;
            }
        }

        if ([] !== $this->choices) {
            $choices = [];

            foreach ($this->choices as $value => $label) {
                $choices[] = ['value' => (string) $value, 'label' => $label];
            }

            $schema['choices'] = $choices;
        }

        return $schema;
    }

    /**
     * Coerces a raw attribute value coming from the editor into the declared PHP type.
     */
    public function coerce(mixed $value): mixed
    {
        if (null === $value) {
            return $this->default;
        }

        return match ($this->type) {
            'number' => is_numeric($value) ? $value + 0 : $this->default,
            'boolean' => filter_var($value, \FILTER_VALIDATE_BOOL),
            'object' => \is_array($value) ? $value : $this->default,
            default => \is_scalar($value) ? (string) $value : $this->default,
        };
    }
}
