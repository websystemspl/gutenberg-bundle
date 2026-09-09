<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Tests\Translation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WebSystems\GutenbergBundle\Translation\WordPressLocale;

final class WordPressLocaleTest extends TestCase
{
    #[DataProvider('locales')]
    public function testNormalisesSymfonyLocalesToWordPressOnes(string $input, string $expected): void
    {
        self::assertSame($expected, WordPressLocale::normalize($input));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function locales(): iterable
    {
        yield 'language only' => ['pl', 'pl_PL'];
        yield 'already regional' => ['pl_PL', 'pl_PL'];
        yield 'dashed' => ['pt-BR', 'pt_BR'];
        yield 'mixed case' => ['De_de', 'de_DE'];
        yield 'exception: english' => ['en', 'en_US'];
        yield 'exception: portuguese' => ['pt', 'pt_PT'];
        yield 'exception: no region' => ['ja', 'ja'];
    }
}
