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
    /** @var KernelInterface */
    private $kernel;

    /** @var ReconcileCommand */
    private $reconciliateCommand;

    public function __construct(
        KernelInterface $kernel,
        ReconcileCommand $reconciliateCommand
    ) {
        $this->kernel = $kernel;
        $this->reconciliateCommand = $reconciliateCommand;
    }

    /**
     * @When /^I reconciliate items$/
     */
    public function iReconciliateItems()
    {
        $application = new Application($this->kernel);
        $application->add($this->reconciliateCommand);
        $command = $application->find('webgriffe:akeneo:reconcile');

        $commandTester = new CommandTester($command);

        $commandTester->execute(['command' => 'webgriffe:akeneo:reconcile']);
    }
}
