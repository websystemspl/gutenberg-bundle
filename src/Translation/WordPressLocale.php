<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Translation;

/**
 * Translates a Symfony locale into the locale code WordPress.org publishes packs under.
 *
 * Most languages follow the `xx_XX` pattern, so only the exceptions are listed.
 */
final class WordPressLocale
{
    private const EXCEPTIONS = [
        'en' => 'en_US',
        'pt' => 'pt_PT',
        'sv' => 'sv_SE',
        'da' => 'da_DK',
        'nb' => 'nb_NO',
        'uk' => 'uk_UA',
        'el' => 'el',
        'ja' => 'ja',
        'he' => 'he_IL',
        'zh' => 'zh_CN',
        'ko' => 'ko_KR',
        'vi' => 'vi',
        'th' => 'th',
        'ar' => 'ar',
        'fa' => 'fa_IR',
        'et' => 'et',
        'sr' => 'sr_RS',
        'sq' => 'sq',
        'ca' => 'ca',
        'eu' => 'eu',
        'gl' => 'gl_ES',
    ];

    public static function normalize(string $locale): string
    {
        $locale = str_replace('-', '_', trim($locale));

        if (str_contains($locale, '_')) {
            [$language, $region] = explode('_', $locale, 2);

            return strtolower($language).'_'.strtoupper($region);
        }

        $language = strtolower($locale);

        return self::EXCEPTIONS[$language] ?? $language.'_'.strtoupper($language);
    }
}
