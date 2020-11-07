<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Cli;

use Behat\Behat\Context\Context;
use org\bovigo\vfs\vfsStream;
use Sylius\Behat\Service\SharedStorageInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Webgriffe\SyliusAkeneoPlugin\Command\EnqueueCommand;
use Webmozart\Assert\Assert;

final class EnqueueCommandContext implements Context
{
    /** @var KernelInterface */
    private $kernel;

    /** @var EnqueueCommand */
    private $enqueueCommand;

    /** @var SharedStorageInterface */
    private $sharedStorage;

    public function __construct(
        KernelInterface $kernel,
        EnqueueCommand $enqueueCommand,
        SharedStorageInterface $sharedStorage
    ) {
        $this->kernel = $kernel;
        $this->enqueueCommand = $enqueueCommand;
        $this->sharedStorage = $sharedStorage;
    }

    /**
     * @When /^I enqueue items for all importers modified since date "([^"]+)"$/
     */
    public function iRunEnqueueCommandWithSinceDate($date)
    {
        $commandTester = $this->getCommandTester();

        try {
            $commandTester->execute(['command' => 'webgriffe:akeneo:enqueue', '--since' => $date]);
        } catch (\Throwable $t) {
            $this->sharedStorage->set('command_exception', $t);
        }
    }

    /**
     * @When I enqueue items for all importers with no since date
     */
    public function iRunEnqueueCommandWithNoSinceDate()
    {
        $commandTester = $this->getCommandTester();

        try {
            $commandTester->execute(['command' => 'webgriffe:akeneo:enqueue']);
        } catch (\Throwable $t) {
            $this->sharedStorage->set('command_exception', $t);
        }
    }

    /**
     * @Then /^I should be notified that a since date is required$/
     */
    public function theCommandShouldHaveThrownExceptionWithMessageContaining()
    {
        /** @var \Throwable $throwable */
        $throwable = $this->sharedStorage->get('command_exception');
        Assert::isInstanceOf($throwable, \Throwable::class);
        Assert::contains(
            $throwable->getMessage(),
            'One of "--since", "--since-file" or "--all" option must be specified'
        );
    }

    /**
     * @When /^I enqueue items for all importers with invalid since date$/
     */
    public function iEnqueueItemsForAllImportersWithInvalidSinceDate()
    {
        $commandTester = $this->getCommandTester();

        try {
            $commandTester->execute(['command' => 'webgriffe:akeneo:enqueue', '--since' => 'bad date']);
        } catch (\Throwable $t) {
            $this->sharedStorage->set('command_exception', $t);
        }
    }

    /**
     * @Then /^I should be notified that the since date must be a valid date$/
     */
    public function iShouldBeNotifiedThatTheSinceDateMustBeAValidDate()
    {
        /** @var \Throwable $throwable */
        $throwable = $this->sharedStorage->get('command_exception');
        Assert::isInstanceOf($throwable, \Throwable::class);
        Assert::contains($throwable->getMessage(), 'The "since" argument must be a valid date');
    }

    /**
     * @When /^I enqueue items with since date specified from a not existent file$/
     */
    public function iEnqueueItemsWithSinceDateSpecifiedFromANotExistentFile()
    {
        $commandTester = $this->getCommandTester();
        $filepath = vfsStream::url('root/not-existent-file.txt');

        try {
            $commandTester->execute(['command' => 'webgriffe:akeneo:enqueue', '--since-file' => $filepath]);
        } catch (\Throwable $t) {
            $this->sharedStorage->set('command_exception', $t);
        }
    }

    /**
     * @Then /^I should be notified that the since date file does not exists$/
     */
    public function iShouldBeNotifiedThatTheSinceDateFileDoesNotExists()
    {
        /** @var \Throwable $throwable */
        $throwable = $this->sharedStorage->get('command_exception');
        Assert::isInstanceOf($throwable, \Throwable::class);
        Assert::contains($throwable->getMessage(), 'does not exists');
    }

    /**
     * @When /^I enqueue items for all importers modified since date specified from file "([^"]+)"$/
     */
    public function iEnqueueItemsWithSinceDateSpecifiedFromFile(string $file)
    {
        $commandTester = $this->getCommandTester();
        $filepath = vfsStream::url('root/' . $file);

        try {
            $commandTester->execute(['command' => 'webgriffe:akeneo:enqueue', '--since-file' => $filepath]);
        } catch (\Throwable $t) {
            $this->sharedStorage->set('command_exception', $t);
        }
    }

    /**
     * @When /^I enqueue all items for all importers$/
     */
    public function iEnqueueAllItemsForAllImporters()
    {
        $commandTester = $this->getCommandTester();

        $commandTester->execute(['command' => 'webgriffe:akeneo:enqueue', '--all' => true]);
    }

    /**
     * @When /^I enqueue all items for the "([^"]+)" importer$/
     */
    public function iEnqueueItemsModifiedSinceDateForTheImporter(string $importer)
    {
        $commandTester = $this->getCommandTester();

        $commandTester->execute(
            ['command' => 'webgriffe:akeneo:enqueue', '--all' => true, '--importer' => [$importer]]
        );
    }

    private function getCommandTester(): CommandTester
    {
        $application = new Application($this->kernel);
        $application->add($this->enqueueCommand);
        $command = $application->find('webgriffe:akeneo:enqueue');

        return new CommandTester($command);
    }
}
