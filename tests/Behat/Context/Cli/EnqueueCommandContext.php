<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Cli;

use Behat\Behat\Context\Context;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Webgriffe\SyliusAkeneoPlugin\Command\EnqueueCommand;

final class EnqueueCommandContext implements Context
{
    /** @var KernelInterface */
    private $kernel;

    /** @var EnqueueCommand */
    private $enqueueCommand;

    public function __construct(
        KernelInterface $kernel,
        EnqueueCommand $consumeCommand
    ) {
        $this->kernel = $kernel;
        $this->enqueueCommand = $consumeCommand;
    }

    /**
     * @When /^I run enqueue command with since date "([^"]+)"$/
     */
    public function iRunEnqueueCommandWithSinceDate($date = null)
    {
        $application = new Application($this->kernel);
        $application->add($this->enqueueCommand);
        $command = $application->find('webgriffe:akeneo:enqueue');
        $commandTester = new CommandTester($command);
        $input = ['command' => 'webgriffe:akeneo:enqueue'];
        if ($date !== null) {
            $input['since'] = $date;
        }
        $commandTester->execute($input);
    }
}
