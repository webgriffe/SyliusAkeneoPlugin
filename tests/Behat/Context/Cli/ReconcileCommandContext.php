<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Cli;

use Behat\Behat\Context\Context;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Webgriffe\SyliusAkeneoPlugin\Command\ReconcileCommand;

final class ReconcileCommandContext implements Context
{
    public function __construct(
        private KernelInterface $kernel,
        private ReconcileCommand $reconcileCommand
    ) {
    }

    /**
     * @When I reconcile items
     */
    public function iReconcileItems(): void
    {
        $application = new Application($this->kernel);
        $application->add($this->reconcileCommand);
        $command = $application->find('webgriffe:akeneo:reconcile');

        $commandTester = new CommandTester($command);

        $commandTester->execute(['command' => 'webgriffe:akeneo:reconcile']);
    }
}
