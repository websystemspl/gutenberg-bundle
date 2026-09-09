<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Tests\Theme;

use PHPUnit\Framework\TestCase;
use WebSystems\GutenbergBundle\Theme\PresetStylesheet;

final class PresetStylesheetTest extends TestCase
{
    public function testEmitsCustomPropertiesAndPresetClassesForColours(): void
    {
        $css = (new PresetStylesheet([['name' => 'Brand', 'slug' => 'brand', 'color' => '#1d3557']]))->render();

        self::assertStringContainsString('--wp--preset--color--brand: #1d3557;', $css);
        self::assertStringContainsString('.has-brand-color{color:var(--wp--preset--color--brand) !important}', $css);
        self::assertStringContainsString('.has-brand-background-color{background-color:var(--wp--preset--color--brand) !important}', $css);
        self::assertStringContainsString('.has-brand-border-color{border-color:var(--wp--preset--color--brand) !important}', $css);
    }

    public function testEmitsGradientAndFontSizePresets(): void
    {
        $css = (new PresetStylesheet(
            gradients: [['name' => 'Deep', 'slug' => 'deep', 'gradient' => 'linear-gradient(135deg,#000,#fff)']],
            fontSizes: [['name' => 'Large', 'slug' => 'large', 'size' => '24px']],
        ))->render();

        self::assertStringContainsString('.has-deep-gradient-background{background:var(--wp--preset--gradient--deep) !important}', $css);
        self::assertStringContainsString('.has-large-font-size{font-size:var(--wp--preset--font-size--large) !important}', $css);
    }

    public function testNumericFontSizesBecomePixels(): void
    {
        $css = (new PresetStylesheet(fontSizes: [['name' => 'Small', 'slug' => 'small', 'size' => 14]]))->render();

        self::assertStringContainsString('--wp--preset--font-size--small: 14px;', $css);
    }

    public function testNothingIsEmittedWhenDisabledOrEmpty(): void
    {
        self::assertSame('', (new PresetStylesheet([['name' => 'B', 'slug' => 'b', 'color' => '#000']], enabled: false))->render());
        self::assertSame('', (new PresetStylesheet())->render());
    }

    public function testASlugCannotEscapeIntoTheSelector(): void
    {
        $css = (new PresetStylesheet([
            ['name' => 'Bad', 'slug' => 'evil{}body', 'color' => '#000000'],
            ['name' => 'Good', 'slug' => 'ok', 'color' => '#ffffff'],
        ]))->render();

        self::assertStringNotContainsString('evil', $css);
        self::assertStringContainsString('.has-ok-color', $css);
    }

    public function testAValueCannotEscapeItsDeclaration(): void
    {
        $css = (new PresetStylesheet([
            ['name' => 'Bad', 'slug' => 'bad', 'color' => '#000} body{display:none'],
            ['name' => 'Good', 'slug' => 'ok', 'color' => '#ffffff'],
        ]))->render();

        self::assertStringNotContainsString('display:none', $css);
        self::assertStringContainsString('--wp--preset--color--ok: #ffffff;', $css);
    }
}
