<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Translation;

/**
 * Supplies the editor's user-interface translations.
 *
 * The data is the "jed" message map that @wordpress/i18n's setLocaleData() consumes, i.e. the
 * `locale_data.messages` object of a WordPress script-translation file.
 */
interface TranslationCatalogueInterface
{
    public function has(string $locale): bool;

    /**
     * @return array<string, mixed>|null null when the locale has not been installed
     */
    public function getMessages(string $locale): ?array;

    /**
     * @return list<string> the locales currently installed
     */
    public function getInstalledLocales(): array;
}
