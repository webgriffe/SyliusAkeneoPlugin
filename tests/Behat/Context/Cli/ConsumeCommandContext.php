<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Cli;

use Behat\Behat\Context\Context;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Webgriffe\SyliusAkeneoPlugin\Command\ConsumeCommand;
use Webgriffe\SyliusAkeneoPlugin\Repository\QueueItemRepositoryInterface;

final class ConsumeCommandContext implements Context
{
    /** @var KernelInterface */
    private $kernel;

    /** @var QueueItemRepositoryInterface */
    private $queueItemRepository;

    /** @var ConsumeCommand */
    private $consumeCommand;

    public function __construct(
        KernelInterface $kernel,
        QueueItemRepositoryInterface $queueItemRepository,
        ConsumeCommand $consumeCommand
    ) {
        $this->kernel = $kernel;
        $this->queueItemRepository = $queueItemRepository;
        $this->consumeCommand = $consumeCommand;
    }

    /**
     * @When /^I import products from queue$/
     */
    public function iImportProductsFromQueue()
    {
        $application = new Application($this->kernel);
        $application->add($this->consumeCommand);
        $command = $application->find('webgriffe:akeneo:consume');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => 'webgriffe:akeneo:consume']);
    }
}
