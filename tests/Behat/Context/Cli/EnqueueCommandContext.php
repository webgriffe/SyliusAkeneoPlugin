<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Cli;

use Behat\Behat\Context\Context;
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
        EnqueueCommand $consumeCommand,
        SharedStorageInterface $sharedStorage
    ) {
        $this->kernel = $kernel;
        $this->enqueueCommand = $consumeCommand;
        $this->sharedStorage = $sharedStorage;
    }

    /**
     * @When /^I run enqueue command with since date "([^"]+)"$/
     */
    public function iRunEnqueueCommandWithSinceDate($date)
    {
        $application = new Application($this->kernel);
        $application->add($this->enqueueCommand);
        $command = $application->find('webgriffe:akeneo:enqueue');
        $commandTester = new CommandTester($command);

        try {
            $commandTester->execute(['command' => 'webgriffe:akeneo:enqueue', 'since' => $date]);
        } catch (\Throwable $t) {
            $this->sharedStorage->set('command_exception', $t);
        }
    }

    /**
     * @When I run enqueue command with no since date
     */
    public function iRunEnqueueCommandWithNoSinceDate()
    {
        $application = new Application($this->kernel);
        $application->add($this->enqueueCommand);
        $command = $application->find('webgriffe:akeneo:enqueue');
        $commandTester = new CommandTester($command);

        try {
            $commandTester->execute(['command' => 'webgriffe:akeneo:enqueue']);
        } catch (\Throwable $t) {
            $this->sharedStorage->set('command_exception', $t);
        }
    }

    /**
     * @Then /^the command should have thrown exception with message containing \'([^\']*)\'$/
     */
    public function theCommandShouldHaveThrownExceptionWithMessageContaining($message)
    {
        /** @var \Throwable $throwable */
        $throwable = $this->sharedStorage->get('command_exception');
        Assert::isInstanceOf($throwable, \Throwable::class);
        Assert::contains($throwable->getMessage(), $message);
    }
}
