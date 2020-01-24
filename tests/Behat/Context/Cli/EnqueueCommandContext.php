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
     * @When /^I run enqueue command with since date "([^"]+)"$/
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
     * @When I run enqueue command with no since date
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
     * @Then /^the command should have run successfully$/
     */
    public function theCommandShouldHaveRunSuccessfully()
    {
        if ($this->sharedStorage->has('command_exception')) {
            throw $this->sharedStorage->get('command_exception');
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

    /**
     * @When /^I run enqueue command with since file "([^"]+)"$/
     */
    public function iRunEnqueueCommandWithSinceFile($filename)
    {
        $commandTester = $this->getCommandTester();
        $filepath = vfsStream::url('root/' . $filename);

        try {
            $commandTester->execute(['command' => 'webgriffe:akeneo:enqueue', '--since-file' => $filepath]);
        } catch (\Throwable $t) {
            $this->sharedStorage->set('command_exception', $t);
        }
    }

    private function getCommandTester(): CommandTester
    {
        $application = new Application($this->kernel);
        $application->add($this->enqueueCommand);
        $command = $application->find('webgriffe:akeneo:enqueue');

        return new CommandTester($command);
    }
}
