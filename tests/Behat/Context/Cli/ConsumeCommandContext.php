<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Cli;

use Behat\Behat\Context\Context;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Webgriffe\SyliusAkeneoPlugin\Command\ConsumeCommand;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webgriffe\SyliusAkeneoPlugin\Repository\QueueItemRepositoryInterface;

final class ConsumeCommandContext implements Context
{
    /** @var KernelInterface */
    private $kernel;

    /** @var QueueItemRepositoryInterface */
    private $queueItemRepository;

    /** @var ImporterInterface */
    private $productModelImporter;

    public function __construct(
        KernelInterface $kernel,
        QueueItemRepositoryInterface $queueItemRepository,
        ImporterInterface $productModelImporter
    ) {
        $this->kernel = $kernel;
        $this->queueItemRepository = $queueItemRepository;
        $this->productModelImporter = $productModelImporter;
    }

    /**
     * @When /^I import products from queue$/
     */
    public function iImportProductsFromQueue()
    {
        $application = new Application($this->kernel);
        $application->add(new ConsumeCommand($this->queueItemRepository, $this->productModelImporter));
        $command = $application->find('webgriffe:akeneo:consume');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => 'webgriffe:akeneo:consume']);
    }
}
