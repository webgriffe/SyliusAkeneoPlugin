<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Cli;

use Behat\Behat\Context\Context;
use org\bovigo\vfs\vfsStream;
use Sylius\Behat\Service\SharedStorageInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;
use Webgriffe\SyliusAkeneoPlugin\Command\EnqueueCommand;
use Webmozart\Assert\Assert;

final class EnqueueCommandContext implements Context
{
    public function __construct(
        private KernelInterface $kernel,
        private EnqueueCommand $enqueueCommand,
        private SharedStorageInterface $sharedStorage
    ) {
    }

    /**
     * @When I enqueue items for all importers modified since date :date
     */
    public function iRunEnqueueCommandWithSinceDate(string $date): void
    {
        $commandTester = $this->getCommandTester();

        try {
            $commandTester->execute(['command' => 'webgriffe:akeneo:enqueue', '--since' => $date]);
        } catch (Throwable $t) {
            $this->sharedStorage->set('command_exception', $t);
        }
    }

    /**
     * @When I enqueue items for all importers with no since date
     */
    public function iEnqueueItemsForAllImportersWithNoSinceDate(): void
    {
        $commandTester = $this->getCommandTester();

        try {
            $commandTester->execute(['command' => 'webgriffe:akeneo:enqueue']);
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
     * @When I enqueue items for all importers with invalid since date
     */
    public function iEnqueueItemsForAllImportersWithInvalidSinceDate(): void
    {
        $commandTester = $this->getCommandTester();

        try {
            $commandTester->execute(['command' => 'webgriffe:akeneo:enqueue', '--since' => 'bad date']);
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
     * @When I enqueue items with since date specified from a not existent file
     */
    public function iEnqueueItemsWithSinceDateSpecifiedFromANotExistentFile(): void
    {
        $commandTester = $this->getCommandTester();
        $filepath = vfsStream::url('root/not-existent-file.txt');

        try {
            $commandTester->execute(['command' => 'webgriffe:akeneo:enqueue', '--since-file' => $filepath]);
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
     * @When I enqueue items for all importers modified since date specified from file :file
     */
    public function iEnqueueItemsForAllImportersModifiedSinceDateSpecifiedFromFile(string $file): void
    {
        $commandTester = $this->getCommandTester();
        $filepath = vfsStream::url('root/' . $file);

        try {
            $commandTester->execute(['command' => 'webgriffe:akeneo:enqueue', '--since-file' => $filepath]);
        } catch (Throwable $t) {
            $this->sharedStorage->set('command_exception', $t);
        }
    }

    /**
     * @When I enqueue all items for all importers
     */
    public function iEnqueueAllItemsForAllImporters(): void
    {
        $commandTester = $this->getCommandTester();

        $commandTester->execute(['command' => 'webgriffe:akeneo:enqueue', '--all' => true]);
    }

    /**
     * @When I enqueue all items for the :importer importer
     */
    public function iEnqueueAllItemsForTheImporter(string $importer): void
    {
        $commandTester = $this->getCommandTester();

        $commandTester->execute(
            ['command' => 'webgriffe:akeneo:enqueue', '--all' => true, '--importer' => [$importer]],
        );
    }

    /**
     * @When I enqueue all items for a not existent importer
     */
    public function iEnqueueAllItemsForANotExistentImporter(): void
    {
        $commandTester = $this->getCommandTester();

        try {
            $commandTester->execute(
                ['command' => 'webgriffe:akeneo:enqueue', '--all' => true, '--importer' => ['not_existent']],
            );
        } catch (Throwable $t) {
            $this->sharedStorage->set('command_exception', $t);
        }
    }

    /**
     * @Then I should be notified that the importer does not exists
     */
    public function iShouldBeNotifiedThatTheImporterDoesNotExists(): void
    {
        /** @var Throwable|mixed $throwable */
        $throwable = $this->sharedStorage->get('command_exception');
        Assert::isInstanceOf($throwable, Throwable::class);
        Assert::regex($throwable->getMessage(), '/Importer ".*?" does not exists/');
    }

    private function getCommandTester(): CommandTester
    {
        $application = new Application($this->kernel);
        $application->add($this->enqueueCommand);
        $command = $application->find('webgriffe:akeneo:enqueue');

        return new CommandTester($command);
    }
}
