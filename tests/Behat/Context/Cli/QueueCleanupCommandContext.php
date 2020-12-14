<?php


namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Cli;


use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;
use Sylius\Behat\Service\SharedStorageInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Webgriffe\SyliusAkeneoPlugin\Command\QueueCleanupCommand;
use Webmozart\Assert\Assert;

final class QueueCleanupCommandContext implements Context
{
    /**
     * @var KernelInterface
     */
    private $kernel;
    /**
     * @var QueueCleanupCommand
     */
    private $queueCleanupCommand;
    /**
     * @var SharedStorageInterface
     */
    private $sharedStorage;

    public function __construct(KernelInterface $kernel, QueueCleanupCommand $queueCleanupCommand, SharedStorageInterface $sharedStorage)
    {
        $this->kernel = $kernel;
        $this->queueCleanupCommand = $queueCleanupCommand;
        $this->sharedStorage = $sharedStorage;
    }

    /**
     * @When I clean the queue
     */
    public function iCleanTheQueue()
    {
        $commandTester = $this->getCommandTester();

        $commandTester->execute(['command' => 'webgriffe:akeneo:cleanup-queue']);
        $this->sharedStorage->set('command_display', $commandTester->getDisplay());
    }

    /**
     * @Then I should be notified that there are no items to clean
     */
    public function iShouldBeNotifiedThatThereAreNoItemsToClean()
    {
        $output = $this->sharedStorage->get('command_display');
        Assert::contains($output, 'There are no items to clean');
    }

    private function getCommandTester(): CommandTester
    {
        $application = new Application($this->kernel);
        $application->add($this->queueCleanupCommand);
        $command = $application->find('webgriffe:akeneo:cleanup-queue');

        return new CommandTester($command);
    }

}
