#!/usr/bin/env php
<?php

declare(strict_types=1);


use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

$autoloadFiles = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php',
];

$autoloaded = false;
foreach ($autoloadFiles as $autoloadFile) {
    if (file_exists($autoloadFile)) {
        require_once $autoloadFile;
        $autoloaded = true;

        break;
    }
}

if (!$autoloaded) {
    fwrite(\STDERR, "Cannot find autoload.php. Please run 'composer install' first.\n");
    exit(1);
}

class RenamePluginCommand extends Command
{
    private const EXCLUDE_DIRS = ['vendor', 'var', 'node_modules', '.git'];

    private const FILE_PATTERNS = ['*.php', '*.yaml', '*.yml', '*.xml', '.env*', 'compose*.yml'];

    protected function configure(): void
    {
        $this
            ->setName('rename')
            ->setDescription('Rename the plugin skeleton to your custom plugin name')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview changes without applying them')
            ->addOption('company', null, InputOption::VALUE_REQUIRED, 'Company name (PascalCase)')
            ->addOption('plugin-name', null, InputOption::VALUE_REQUIRED, 'Plugin name (PascalCase)')
            ->addOption('description', null, InputOption::VALUE_REQUIRED, 'Plugin description')
            ->addOption('skip-interaction', null, InputOption::VALUE_NONE, 'Skip interactive mode (useful for automation)')
            ->addOption('sylius', null, InputOption::VALUE_NONE, 'Use Sylius official plugin naming convention (Sylius\{Name}Plugin)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Sylius Plugin Renamer');

        $skipInteraction = (bool) $input->getOption('skip-interaction') || getenv('SKIP_INTERACTION') === '1';
        $dryRun = (bool) $input->getOption('dry-run');
        if ($dryRun) {
            $io->note('DRY RUN MODE - No files will be modified');
        }

        if (!file_exists(__DIR__ . '/../src/AcmeSyliusExamplePlugin.php')) {
            $io->warning('Plugin appears to be already renamed (AcmeSyliusExamplePlugin.php not found)');
            if (!$skipInteraction && !$io->confirm('Continue anyway?', false)) {
                return Command::SUCCESS;
            }
        }

        $syliusMode = (bool) $input->getOption('sylius');

        $names = $this->getPluginInformation($input, $io, $syliusMode, $skipInteraction);
        $description = $names['description'];

        $io->section('Configuration Summary');
        $io->table(
            ['Property', 'Value'],
            [
                ['Full namespace', "{$names['company']}\\{$names['plugin']}"],
                ['Full class name', $names['fullClass']],
                ['Extension class', $names['extensionClass']],
                ['Package name', $names['package']],
                ['Database name', $names['db']],
                ['Config key', $names['configKey']],
                ['Description', $description],
            ],
        );

        if (!$dryRun && !$skipInteraction) {
            if (!$io->confirm('Continue with this configuration?', true)) {
                $io->info('Renaming cancelled.');

                return Command::SUCCESS;
            }
        }

        try {
            $this->renamePluginFiles($io, $names, $dryRun);
            $this->updateFileContents($io, $names, $dryRun);
            $this->updateComposerJson($io, $names['package'], $description, $names, $dryRun);

            if (!$dryRun) {
                $this->runComposerDumpAutoload($io);
            }

            $this->checkRemainingReferences($io);

            if ($dryRun) {
                $io->success('Dry run completed! Run without --dry-run to apply changes.');
            } else {
                $io->success('Plugin renamed successfully!');

                unlink(__FILE__);
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('An error occurred: ' . $e->getMessage());
            if ($io->isVerbose()) {
                $io->writeln($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }

    private function getPluginInformation(InputInterface $input, SymfonyStyle $io, bool $syliusMode, bool $skipInteraction): array
    {
        $io->section('Plugin Information');

        $envCompany = getenv('COMPANY') ?: null;
        $envPluginName = getenv('PLUGIN_NAME') ?: null;
        $envDescription = getenv('DESCRIPTION') ?: null;
        $envSylius = getenv('SYLIUS') === '1';

        if ($envSylius) {
            $syliusMode = true;
        }

        $detected = $this->detectFromDirectory();

        $pluginName = $envPluginName
            ?? $input->getOption('plugin-name')
            ?? $detected['pluginName']
            ?? null;

        $wasAutoDetected = $detected !== null && $envPluginName === null && $input->getOption('plugin-name') === null;
        if ($wasAutoDetected) {
            $io->note("Auto-detected from directory: {$pluginName}Plugin");
        }

        if ($pluginName !== null && is_string($pluginName) && !$this->validateName($pluginName)) {
            throw new \InvalidArgumentException('Plugin name must be in PascalCase (start with uppercase letter, no spaces or special characters)');
        }

        if ($pluginName === null || !is_string($pluginName)) {
            $question = new Question('Plugin name (PascalCase)');
            $question->setValidator(function ($answer) {
                if (!is_string($answer) || !$this->validateName($answer)) {
                    throw new \RuntimeException('Plugin name must be in PascalCase');
                }
                return $answer;
            });
            $question->setMaxAttempts(null);
            $pluginName = $io->askQuestion($question);
        }

        if ($syliusMode) {
            $io->note('Using Sylius official naming convention: Sylius\{Name}Plugin');
            $companyName = 'Sylius';
        } else {
            $companyName = $envCompany ?? $input->getOption('company') ?? null;

            if ($companyName !== null && is_string($companyName) && !$this->validateName($companyName)) {
                throw new \InvalidArgumentException('Company name must be in PascalCase (start with uppercase letter, no spaces or special characters)');
            }

            if ($companyName === null || !is_string($companyName)) {
                $question = new Question('Company name (PascalCase)');
                $question->setValidator(function ($answer) {
                    if (!is_string($answer) || !$this->validateName($answer)) {
                        throw new \RuntimeException('Company name must be in PascalCase');
                    }
                    return $answer;
                });
                $question->setMaxAttempts(null);
                $companyName = $io->askQuestion($question);
            }
        }

        $description = $envDescription ?? $input->getOption('description') ?? null;
        if ($description === null || !is_string($description)) {
            if ($skipInteraction) {
                $description = $pluginName . ' plugin for Sylius';
            } else {
                $question = new Question('Plugin description', $pluginName . ' plugin for Sylius');
                $description = $io->askQuestion($question);
            }
        }

        if (!is_string($description)) {
            $description = $pluginName . ' plugin for Sylius';
        }

        assert(is_string($companyName));
        assert(is_string($pluginName));

        $names = $this->generateNameVariations($companyName, $pluginName, $syliusMode);
        $names['description'] = $description;

        return $names;
    }

    private function detectFromDirectory(): ?array
    {
        $dirName = basename(dirname(__DIR__));

        if (preg_match('/^(?:[A-Z][a-zA-Z0-9]*)?([A-Z][a-zA-Z0-9]*)Plugin$/', $dirName, $matches)) {
            return [
                'pluginName' => $matches[1],
            ];
        }

        return null;
    }

    private function generateNameVariations(string $company, string $pluginName, bool $syliusMode = false): array
    {
        $plugin = $pluginName . 'Plugin';
        $pluginSnake = $this->toSnakeCase($pluginName);

        if ($syliusMode) {
            $fullClass = 'Sylius' . $plugin;
            $extensionClass = 'Sylius' . $pluginName . 'Extension';
            $pluginKebab = $this->toKebabCase($pluginName);

            return [
                'company' => 'Sylius',
                'plugin' => $plugin,
                'fullClass' => $fullClass,
                'extensionClass' => $extensionClass,
                'package' => 'sylius/' . $pluginKebab . '-plugin',
                'db' => 'sylius_' . $pluginSnake,
                'configKey' => 'sylius_' . $pluginSnake,
            ];
        }

        $fullClass = $company . $plugin;
        $extensionClass = $company . $pluginName . 'Extension';

        $companyKebab = $this->toKebabCase($company);
        $fullPluginKebab = $this->toKebabCase($plugin);
        $companySnake = $this->toSnakeCase($company);

        return [
            'company' => $company,
            'plugin' => $plugin,
            'fullClass' => $fullClass,
            'extensionClass' => $extensionClass,
            'package' => $companyKebab . '/' . $fullPluginKebab,
            'db' => $companySnake . '_' . $pluginSnake,
            'configKey' => $companySnake . '_' . $pluginSnake,
        ];
    }

    private function renamePluginFiles(SymfonyStyle $io, array $names, bool $dryRun): void
    {
        $io->section('Renaming PHP files');

        $oldMainFile = __DIR__ . '/../src/AcmeSyliusExamplePlugin.php';
        $newMainFile = __DIR__ . "/../src/{$names['fullClass']}.php";

        $oldExtensionFile = __DIR__ . '/../src/DependencyInjection/AcmeSyliusExampleExtension.php';
        $newExtensionFile = __DIR__ . "/../src/DependencyInjection/{$names['extensionClass']}.php";

        $renamedFiles = [];

        if (file_exists($oldMainFile)) {
            if ($dryRun) {
                $renamedFiles[] = "[DRY RUN] src/AcmeSyliusExamplePlugin.php → src/{$names['fullClass']}.php";
            } else {
                if (!rename($oldMainFile, $newMainFile)) {
                    throw new \RuntimeException("Failed to rename {$oldMainFile}");
                }
                $renamedFiles[] = "src/AcmeSyliusExamplePlugin.php → src/{$names['fullClass']}.php";
            }
        }

        if (file_exists($oldExtensionFile)) {
            if ($dryRun) {
                $renamedFiles[] = "[DRY RUN] src/DependencyInjection/AcmeSyliusExampleExtension.php → src/DependencyInjection/{$names['extensionClass']}.php";
            } else {
                if (!rename($oldExtensionFile, $newExtensionFile)) {
                    throw new \RuntimeException("Failed to rename {$oldExtensionFile}");
                }
                $renamedFiles[] = "src/DependencyInjection/AcmeSyliusExampleExtension.php → src/DependencyInjection/{$names['extensionClass']}.php";
            }
        }

        if (count($renamedFiles) > 0) {
            $io->listing($renamedFiles);
        }

        $io->success('PHP files renamed');
    }

    private function updateFileContents(SymfonyStyle $io, array $names, bool $dryRun): void
    {
        $io->section('Updating file contents');

        $replacements = [
            'acme_sylius_example_plugin_%kernel.environment%' => 'sylius_%kernel.environment%',
            'Acme\\SyliusExamplePlugin' => "{$names['company']}\\{$names['plugin']}",
            'AcmeSyliusExamplePlugin' => $names['fullClass'],
            'AcmeSyliusExampleExtension' => $names['extensionClass'],
            '@AcmeSyliusExamplePlugin' => "@{$names['fullClass']}",
            'Tests\\Acme\\SyliusExamplePlugin' => "Tests\\{$names['company']}\\{$names['plugin']}",
            'acme_sylius_example_plugin' => $names['db'],
            'acme_sylius_example' => $names['configKey'],
        ];

        $finder = new Finder();
        $finder->files()
            ->in(__DIR__ . '/..')
            ->exclude(self::EXCLUDE_DIRS)
            ->ignoreDotFiles(false)
            ->name(self::FILE_PATTERNS)
            ->notName('rename-plugin.php');

        $updatedCount = 0;
        $updatedFiles = [];

        foreach ($finder as $file) {
            $filePath = $file->getRealPath();
            if ($filePath === false) {
                continue;
            }

            $content = file_get_contents($filePath);
            if ($content === false) {
                throw new \RuntimeException("Failed to read file: {$filePath}");
            }

            $originalContent = $content;

            foreach ($replacements as $search => $replace) {
                $content = str_replace($search, $replace, $content);
            }

            if ($content !== $originalContent) {
                if ($dryRun) {
                    $updatedFiles[] = $file->getRelativePathname();
                } else {
                    if (file_put_contents($filePath, $content) === false) {
                        throw new \RuntimeException("Failed to write file: {$filePath}");
                    }
                    $updatedFiles[] = $file->getRelativePathname();
                }
                ++$updatedCount;
            }
        }

        if ($updatedCount > 0) {
            $prefix = $dryRun ? '[DRY RUN] Would update' : 'Updated';
            $io->success("{$prefix} {$updatedCount} files:");
            $io->listing($updatedFiles);
        } else {
            $io->info('No files to update');
        }
    }

    private function updateComposerJson(SymfonyStyle $io, string $packageName, string $description, array $names, bool $dryRun): void
    {
        $io->section('Updating composer.json');

        $composerFile = __DIR__ . '/../composer.json';
        $composerContent = file_get_contents($composerFile);

        if ($composerContent === false) {
            throw new \RuntimeException("Failed to read {$composerFile}");
        }

        $composer = json_decode($composerContent, true);

        if (!is_array($composer)) {
            throw new \RuntimeException("Failed to parse {$composerFile}");
        }

        $composer['name'] = $packageName;
        $composer['description'] = $description;

        unset($composer['autoload']['psr-4']['Acme\\SyliusExamplePlugin\\']);
        $composer['autoload']['psr-4']["{$names['company']}\\{$names['plugin']}\\"] = 'src/';

        unset($composer['autoload-dev']['psr-4']['Tests\\Acme\\SyliusExamplePlugin\\']);
        $composer['autoload-dev']['psr-4']["Tests\\{$names['company']}\\{$names['plugin']}\\"] = ['tests/', 'tests/TestApplication/src/'];

        if ($dryRun) {
            $io->writeln('[DRY RUN] Would update composer.json with:');
            $io->writeln('  - name: ' . $packageName);
            $io->writeln('  - description: ' . $description);
            $io->writeln('  - autoload PSR-4 namespace: ' . "{$names['company']}\\{$names['plugin']}\\");
        } else {
            $newContent = json_encode($composer, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE) . "\n";
            if (file_put_contents($composerFile, $newContent) === false) {
                throw new \RuntimeException("Failed to write {$composerFile}");
            }
            $io->success('composer.json updated');
        }
    }

    private function runComposerDumpAutoload(SymfonyStyle $io): void
    {
        $io->section('Refreshing autoload files');

        exec('composer dump-autoload 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            $io->warning('Failed to run composer dump-autoload. Please run it manually.');

            return;
        }

        $io->success('Autoload files refreshed');
        if ($io->isVerbose()) {
            $io->writeln(implode("\n", $output));
        }
    }

    private function checkRemainingReferences(SymfonyStyle $io): void
    {
        $io->section('Checking for remaining references');

        $finder = new Finder();
        $finder->files()
            ->in(__DIR__ . '/..')
            ->exclude(self::EXCLUDE_DIRS)
            ->notName(['*.md', 'rename-plugin.php']);

        $patterns = ['Acme', 'SyliusExample', 'acme_sylius_example'];
        $found = [];

        foreach ($finder as $file) {
            $filePath = $file->getRealPath();
            if ($filePath === false) {
                continue;
            }

            $content = file_get_contents($filePath);
            if ($content === false) {
                throw new \RuntimeException("Failed to read file: {$filePath}");
            }

            foreach ($patterns as $pattern) {
                if (str_contains($content, $pattern)) {
                    $found[] = $file->getRelativePathname();

                    break;
                }
            }
        }

        if (count($found) > 0) {
            $io->warning('Found remaining references in ' . count($found) . ' files:');
            $io->listing($found);
            $io->note('Please review these references manually. Some may be intentional (documentation, guides).');
        } else {
            $io->success('No remaining references found!');
        }
    }

    private function validateName(string $name): bool
    {
        if (empty($name)) {
            return false;
        }

        return (bool) preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name);
    }

    private function toKebabCase(string $str): string
    {
        $result = preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $str);

        return strtolower($result !== null ? $result : $str);
    }

    private function toSnakeCase(string $str): string
    {
        $result = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $str);

        return strtolower($result !== null ? $result : $str);
    }
}

$application = new Application('Sylius Plugin Renamer', '1.0.0');
$application->add(new RenamePluginCommand());
$application->setDefaultCommand('rename', true);
$application->run();
