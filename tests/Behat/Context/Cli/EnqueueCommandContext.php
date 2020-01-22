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
     * @When I enqueue products modified since :date
     */
    public function iEnqueueProductsModifiedSince(\DateTime $date)
    {
        $application = new Application($this->kernel);
        $application->add($this->enqueueCommand);
        $command = $application->find('webgriffe:akeneo:enqueue');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => 'webgriffe:akeneo:enqueue', '--since' => $date->format('Y-m-d H:i:s')]);
    }
}
