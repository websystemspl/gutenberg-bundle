<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Editor;

/**
 * Mutable bag handed to every settings provider and finally serialised as the JSON
 * configuration the JavaScript editor boots from.
 */
final class EditorSettings
{
    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(private array $settings = [])
    {
    }

    public function set(string $key, mixed $value): self
    {
        $this->settings[$key] = $value;

        return $this;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->settings);
    }

    /**
     * Appends to a list-valued setting, creating it when missing.
     */
    public function append(string $key, mixed ...$values): self
    {
        $current = $this->settings[$key] ?? [];
        $this->settings[$key] = [...(array) $current, ...$values];

        return $this;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function merge(array $values): self
    {
        $this->settings = array_replace_recursive($this->settings, $values);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->settings;
    }
}
