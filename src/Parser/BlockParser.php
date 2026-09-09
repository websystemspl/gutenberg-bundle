<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Parser;

use WebSystems\GutenbergBundle\Block\Block;

/**
 * Parses the WordPress block grammar: HTML annotated with `<!-- wp:name {json} -->` delimiters.
 *
 * The grammar is deliberately forgiving, like WordPress' reference parser: unbalanced or
 * unknown delimiters degrade to freeform HTML instead of throwing, so a half-broken
 * document still renders.
 */
final class BlockParser
{
    private const TOKEN = '/<!--\s+(?P<closer>\/)?wp:(?P<namespace>[a-z][a-z0-9_-]*\/)?(?P<name>[a-z][a-z0-9_-]*)\s+(?P<attrs>\{(?:(?!\}\s+\/?-->).)*?\}\s+)?(?P<void>\/)?-->/s';

    /**
     * @return list<Block>
     */
    public function parse(?string $document): array
    {
        $document = (string) $document;

        if ('' === trim($document)) {
            return [];
        }

        if (false === preg_match_all(self::TOKEN, $document, $matches, \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE)) {
            return [Block::freeform($document)];
        }

        /** @var list<Block> $output */
        $output = [];
        /** @var list<array{name: string, attributes: array<string, mixed>, innerBlocks: list<Block>, innerHtml: string, innerContent: list<string|null>}> $stack */
        $stack = [];
        $offset = 0;

        foreach ($matches as $match) {
            $tokenStart = $match[0][1];
            $html = substr($document, $offset, $tokenStart - $offset);
            $offset = $tokenStart + \strlen($match[0][0]);

            $isCloser = '' !== ($match['closer'][0] ?? '');
            $isVoid = '' !== ($match['void'][0] ?? '');
            $name = ('' !== ($match['namespace'][0] ?? '') ? $match['namespace'][0] : 'core/').$match['name'][0];
            $attributes = $this->decodeAttributes($match['attrs'][0] ?? '');

            if ([] === $stack) {
                if ('' !== trim($html)) {
                    $output[] = Block::freeform($html);
                }

                if ($isCloser) {
                    continue; // Stray closer: ignore, mirroring WordPress' recovery behaviour.
                }

                if ($isVoid) {
                    $output[] = new Block($name, $attributes);

                    continue;
                }

                $stack[] = $this->newFrame($name, $attributes);

                continue;
            }

            $top = \count($stack) - 1;

            if ('' !== $html) {
                $stack[$top]['innerHtml'] .= $html;
                $stack[$top]['innerContent'][] = $html;
            }

            if ($isCloser) {
                $this->closeFrame(array_pop($stack), $stack, $output);

                continue;
            }

            if ($isVoid) {
                $stack[$top]['innerBlocks'][] = new Block($name, $attributes);
                $stack[$top]['innerContent'][] = null;

                continue;
            }

            $stack[] = $this->newFrame($name, $attributes);
        }

        $trailing = substr($document, $offset);

        // Unbalanced openers: flush the stack innermost-first so no content is lost.
        while ([] !== $stack) {
            $frame = array_pop($stack);

            if ('' !== $trailing) {
                $frame['innerHtml'] .= $trailing;
                $frame['innerContent'][] = $trailing;
                $trailing = '';
            }

            $this->closeFrame($frame, $stack, $output);
        }

        if ('' !== trim($trailing)) {
            $output[] = Block::freeform($trailing);
        }

        return $output;
    }

    /**
     * @param array{name: string, attributes: array<string, mixed>, innerBlocks: list<Block>, innerHtml: string, innerContent: list<string|null>} $frame
     * @param list<array{name: string, attributes: array<string, mixed>, innerBlocks: list<Block>, innerHtml: string, innerContent: list<string|null>}> $stack
     * @param list<Block> $output
     */
    private function closeFrame(array $frame, array &$stack, array &$output): void
    {
        $block = new Block($frame['name'], $frame['attributes'], $frame['innerBlocks'], $frame['innerHtml'], $frame['innerContent']);

        if ([] === $stack) {
            $output[] = $block;

            return;
        }

        $parent = \count($stack) - 1;
        $stack[$parent]['innerBlocks'][] = $block;
        $stack[$parent]['innerContent'][] = null;
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array{name: string, attributes: array<string, mixed>, innerBlocks: list<Block>, innerHtml: string, innerContent: list<string|null>}
     */
    private function newFrame(string $name, array $attributes): array
    {
        return ['name' => $name, 'attributes' => $attributes, 'innerBlocks' => [], 'innerHtml' => '', 'innerContent' => []];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeAttributes(string $json): array
    {
        $json = trim($json);

        if ('' === $json) {
            return [];
        }

        try {
            $decoded = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return \is_array($decoded) ? $decoded : [];
    }
}
