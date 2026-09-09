<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Keeps the bundled block editor in sync with upstream WordPress.
 *
 * The editor is assembled from the official `@wordpress/*` packages, so "updating Gutenberg"
 * means resolving the latest published versions, pinning them and rebuilding the asset bundle
 * that ships in public/.
 */
#[AsCommand(
    name: 'gutenberg:update',
    description: 'Check for new @wordpress/* editor releases and rebuild the bundled editor assets',
)]
final class UpdateEditorCommand
{
    private const REGISTRY = 'https://registry.npmjs.org/';

    public function __construct(
        private readonly string $bundleDirectory,
        private readonly ?HttpClientInterface $httpClient = null,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'Pin the @wordpress packages to their latest published versions')]
        bool $update = false,
        #[Option(description: 'Reinstall dependencies and rebuild public/editor.js after resolving versions')]
        bool $build = false,
        #[Option(description: 'Report what would change without touching package.json or running npm')]
        bool $dryRun = false,
    ): int {
        $manifestPath = $this->bundleDirectory.'/package.json';

        if (!is_file($manifestPath)) {
            $io->error(\sprintf('No package.json found at "%s"; the bundle sources appear to be incomplete.', $manifestPath));

            return Command::FAILURE;
        }

        if (null === $this->httpClient) {
            $io->error('symfony/http-client is required to query the npm registry. Run: composer require symfony/http-client');

            return Command::FAILURE;
        }

        /** @var array{dependencies?: array<string, string>} $manifest */
        $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, \JSON_THROW_ON_ERROR);
        $dependencies = $manifest['dependencies'] ?? [];
        $wordpress = array_filter($dependencies, static fn (string $package): bool => str_starts_with($package, '@wordpress/'), \ARRAY_FILTER_USE_KEY);

        if ([] === $wordpress) {
            $io->error('package.json declares no @wordpress/* dependencies.');

            return Command::FAILURE;
        }

        $io->title('Gutenberg editor packages');
        $rows = [];
        $outdated = [];

        foreach ($wordpress as $package => $constraint) {
            $latest = $this->fetchLatestVersion($package);
            $isOutdated = null !== $latest && ltrim($constraint, '^~') !== $latest;
            $rows[] = [$package, $constraint, $latest ?? '?', $isOutdated ? 'outdated' : 'up to date'];

            if ($isOutdated && null !== $latest) {
                $outdated[$package] = '^'.$latest;
            }
        }

        $io->table(['Package', 'Pinned', 'Latest', 'Status'], $rows);

        if ([] === $outdated) {
            $io->success('The bundled editor already tracks the latest published @wordpress packages.');
        } else {
            $io->warning(\sprintf('%d package(s) have newer releases.', \count($outdated)));
        }

        if ($dryRun || (!$update && !$build)) {
            if ([] !== $outdated) {
                $io->comment('Run with --update to pin the new versions, and --build to rebuild public/editor.js.');
            }

            return Command::SUCCESS;
        }

        if ($update && [] !== $outdated) {
            $manifest['dependencies'] = array_replace($dependencies, $outdated);
            file_put_contents($manifestPath, json_encode($manifest, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES)."\n");
            $io->success(\sprintf('Pinned %d package(s) in package.json.', \count($outdated)));
        }

        if (!$build) {
            $io->comment('Re-run with --build to reinstall dependencies and regenerate the editor assets.');

            return Command::SUCCESS;
        }

        if (!class_exists(Process::class)) {
            $io->error('symfony/process is required to run npm. Run: composer require symfony/process');

            return Command::FAILURE;
        }

        foreach ([['npm', 'install'], ['npm', 'run', 'build']] as $command) {
            $io->section(implode(' ', $command));
            $process = new Process($command, $this->bundleDirectory, timeout: 900);
            $exitCode = $process->run(static fn (string $type, string $buffer): mixed => $io->write($buffer));

            if (0 !== $exitCode) {
                $io->error(\sprintf('"%s" failed with exit code %d.', implode(' ', $command), $exitCode));

                return Command::FAILURE;
            }
        }

        $io->success('Editor assets rebuilt. Run "bin/console assets:install" to publish them.');

        return Command::SUCCESS;
    }

    private function fetchLatestVersion(string $package): ?string
    {
        try {
            $response = $this->httpClient?->request('GET', self::REGISTRY.rawurlencode($package).'/latest', ['timeout' => 15]);
            $payload = $response?->toArray() ?? [];

            return \is_string($payload['version'] ?? null) ? $payload['version'] : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
