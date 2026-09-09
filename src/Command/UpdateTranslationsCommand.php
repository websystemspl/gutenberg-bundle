<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Command;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use WebSystems\GutenbergBundle\Translation\FileTranslationCatalogue;
use WebSystems\GutenbergBundle\Translation\TranslationException;
use WebSystems\GutenbergBundle\Translation\TranslationInstaller;
use WebSystems\GutenbergBundle\Translation\WordPressLocale;

/**
 * Installs the editor's interface translations from the official WordPress.org language packs.
 *
 *     bin/console gutenberg:translations pl_PL
 *     bin/console gutenberg:translations pl de fr --wp-version=6.8
 */
#[AsCommand(
    name: 'gutenberg:translations',
    description: 'Download the editor interface translations for one or more locales from WordPress.org',
)]
final class UpdateTranslationsCommand
{
    public function __construct(
        private readonly FileTranslationCatalogue $catalogue,
        private readonly string $configuredLocale,
        private readonly ?TranslationInstaller $installer = null,
    ) {
    }

    /**
     * @param list<string> $locales
     */
    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Locales to install, e.g. "pl_PL" or "pl"; defaults to the configured editor locale')]
        array $locales = [],
        #[Option(description: 'WordPress release to take the packs from; defaults to the current release')]
        ?string $wpVersion = null,
        #[Option(description: 'Only list the locales already installed')]
        bool $list = false,
    ): int {
        if ($list) {
            $installed = $this->catalogue->getInstalledLocales();

            if ([] === $installed) {
                $io->warning('No editor translations are installed; the interface will stay in English.');

                return Command::SUCCESS;
            }

            $io->title('Installed editor translations');
            $io->listing(array_map(
                fn (string $locale): string => \sprintf('%s  (%d messages)', $locale, \count($this->catalogue->getMessages($locale) ?? [])),
                $installed,
            ));

            return Command::SUCCESS;
        }

        if (null === $this->installer) {
            $io->error('symfony/http-client is required to download language packs. Run: composer require symfony/http-client');

            return Command::FAILURE;
        }

        $locales = [] !== $locales ? $locales : [$this->configuredLocale];
        $failures = 0;

        foreach ($locales as $locale) {
            $normalized = WordPressLocale::normalize($locale);
            $io->section($normalized);

            try {
                $result = $this->installer->install($normalized, $wpVersion);
                $io->text(\sprintf('Installed %d messages from WordPress %s into %s', $result['messages'], $result['version'], $result['path']));
            } catch (TranslationException $exception) {
                ++$failures;
                $io->error($exception->getMessage());
            }
        }

        if ($failures > 0) {
            return Command::FAILURE;
        }

        $io->success('Editor translations installed. Reload the editor to see them.');

        return Command::SUCCESS;
    }
}
