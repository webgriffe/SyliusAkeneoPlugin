<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Cli;

use Behat\Behat\Context\Context;
use DateTime;
use org\bovigo\vfs\vfsStream;
use Sylius\Behat\Service\SharedStorageInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;
use Webgriffe\SyliusAkeneoPlugin\Command\ImportCommand;
use Webmozart\Assert\Assert;

final class ImportCommandContext implements Context
{
    public function __construct(
        private KernelInterface $kernel,
        private ImportCommand $importCommand,
        private SharedStorageInterface $sharedStorage,
    ) {
    }

    /**
     * @BeforeScenario
     */
    public function before(): void
    {
        vfsStream::setup();
    }

    /**
     * @When I import items for all importers modified since date :date
     */
    public function iImportItemsForAllImportersModifiedSinceDate(DateTime $date): void
    {
        $commandTester = $this->getCommandTester();

        try {
            $commandTester->execute(['command' => 'webgriffe:akeneo:import', '--since' => $date->format('Y-m-d H:i:s')]);
        } catch (Throwable $t) {
            $this->sharedStorage->set('command_exception', $t);
        }
    }

    /**
     * @When I import items for all importers with no since date
     */
    public function iImportItemsForAllImportersWithNoSinceDate(): void
    {
        $commandTester = $this->getCommandTester();

        try {
            $commandTester->execute(['command' => 'webgriffe:akeneo:import']);
        } catch (Throwable $t) {
            $this->sharedStorage->set('command_exception', $t);
        }
    }

    /**
     * @Then I should be notified that a since date is required
     */
    public function iShouldBeNotifiedThatASinceDateIsRequired(): void
    {
        /** @var Throwable|mixed $throwable */
        $throwable = $this->sharedStorage->get('command_exception');
        Assert::isInstanceOf($throwable, Throwable::class);
        Assert::contains(
            $throwable->getMessage(),
            'One of "--since", "--since-file" or "--all" option must be specified',
        );
    }

    /**
     * @When I import items for all importers with invalid since date
     */
    public function iImportItemsForAllImportersWithInvalidSinceDate(): void
    {
        $commandTester = $this->getCommandTester();

        try {
            $commandTester->execute(['command' => 'webgriffe:akeneo:import', '--since' => 'bad date']);
        } catch (Throwable $t) {
            $this->sharedStorage->set('command_exception', $t);
        }
    }

    /**
     * @Then I should be notified that the since date must be a valid date
     */
    public function iShouldBeNotifiedThatTheSinceDateMustBeAValidDate(): void
    {
        /** @var Throwable|mixed $throwable */
        $throwable = $this->sharedStorage->get('command_exception');
        Assert::isInstanceOf($throwable, Throwable::class);
        Assert::contains($throwable->getMessage(), 'The "since" argument must be a valid date');
    }

    /**
     * @When I import items with since date specified from a not existent file
     */
    public function iImportItemsWithSinceDateSpecifiedFromANotExistentFile(): void
    {
        $commandTester = $this->getCommandTester();
        $filepath = vfsStream::url('root/not-existent-file.txt');

        try {
            $commandTester->execute(['command' => 'webgriffe:akeneo:import', '--since-file' => $filepath]);
        } catch (Throwable $t) {
            $this->sharedStorage->set('command_exception', $t);
        }
    }

    /**
     * @Then I should be notified that the since date file does not exists
     */
    public function iShouldBeNotifiedThatTheSinceDateFileDoesNotExists(): void
    {
        /** @var Throwable|mixed $throwable */
        $throwable = $this->sharedStorage->get('command_exception');
        Assert::isInstanceOf($throwable, Throwable::class);
        Assert::contains($throwable->getMessage(), 'does not exists');
    }

    /**
     * @When I import all from Akeneo
     * @When I import all "ProductAssociations" from Akeneo
     * @When I import all "ProductModels" from Akeneo
     */
    public function iImportAllItemsForAllImporters(?string $importer = null): void
    {
        $commandTester = $this->getCommandTester();

        $params = [
            'command' => 'webgriffe:akeneo:import',
            '--all' => true,
        ];
        if ($importer !== null) {
            $params['--importer'] = [$importer];
        }
        $commandTester->execute($params);
    }

    /**
     * @When I try to import all from Akeneo
     */
    public function iTryToImportAllFromAkeneo(): void
    {
        $commandTester = $this->getCommandTester();

        try {
            $commandTester->execute(['command' => 'webgriffe:akeneo:import', '--all' => true]);
        } catch (Throwable $t) {
            $this->sharedStorage->set('command_exception', $t);
        }
    }

    /**
     * @When I import all items for the :importer importer
     */
    public function iImportAllItemsForTheImporter(string $importer): void
    {
        $commandTester = $this->getCommandTester();

        $commandTester->execute(
            ['command' => 'webgriffe:akeneo:import', '--all' => true, '--importer' => [$importer]],
        );
    }

    /**
     * @When I import all items for a not existent importer
     */
    public function iImportAllItemsForANotExistentImporter(): void
    {
        $commandTester = $this->getCommandTester();

        try {
            $commandTester->execute(
                ['command' => 'webgriffe:akeneo:import', '--all' => true, '--importer' => ['not_existent']],
            );
        } catch (Throwable $t) {
            $this->sharedStorage->set('command_exception', $t);
        }
    }

    /**
     * @Then I should be notified that the importer does not exist
     */
    public function iShouldBeNotifiedThatTheImporterDoesNotExists(): void
    {
        /** @var Throwable|mixed $throwable */
        $throwable = $this->sharedStorage->get('command_exception');
        Assert::isInstanceOf($throwable, Throwable::class);
        Assert::regex($throwable->getMessage(), '/Importer ".*?" does not exists/');
    }

    /**
     * @Then /^I should get an error about product name cannot be empty$/
     */
    public function iShouldGetAnErrorAboutProductNameNotEmpty(): void
    {
        $throwable = $this->sharedStorage->get('command_exception');
        $this->sharedStorage->set('error', $throwable);
        Assert::isInstanceOf($throwable, Throwable::class);
        Assert::contains($throwable->getMessage(), 'Please enter product name');
    }

    private function getCommandTester(): CommandTester
    {
        $application = new Application($this->kernel);
        $application->add($this->importCommand);
        $command = $application->find('webgriffe:akeneo:import');

        return new CommandTester($command);
    }
}
