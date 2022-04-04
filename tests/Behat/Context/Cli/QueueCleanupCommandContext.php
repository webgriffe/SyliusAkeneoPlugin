<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Cli;

use Behat\Behat\Context\Context;
use Sylius\Behat\Service\SharedStorageInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Webgriffe\SyliusAkeneoPlugin\Command\QueueCleanupCommand;
use Webmozart\Assert\Assert;

final class QueueCleanupCommandContext implements Context
{
    public function __construct(private KernelInterface $kernel, private QueueCleanupCommand $queueCleanupCommand, private SharedStorageInterface $sharedStorage)
    {
    }

    /**
     * @When I clean the queue
     * @When /^I clean the queue specifying (\d+) days of retention$/
     */
    public function iCleanTheQueue(int $days = null)
    {
        $this->sharedStorage->set('command_input_days', $days);
        $commandTester = $this->getCommandTester();

        $input = ['command' => 'webgriffe:akeneo:cleanup-queue'];
        if ($days !== null) {
            $input['days'] = (string) $days;
        }
        $commandTester->execute($input);
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

    /**
     * @Then /^I should be notified that (\d+) item[s]? (has|have) been deleted$/
     */
    public function iShouldBeNotifiedThatItemHasBeenDeleted(int $count)
    {
        $output = $this->sharedStorage->get('command_display');
        Assert::regex($output, "/$count items imported before \d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} has been deleted/");
    }

    /**
     * @Then /^there shouldn\'t be any more item[s]? to clean$/
     */
    public function thereShouldntBeAnyMoreItemToClean()
    {
        $this->iCleanTheQueue($this->sharedStorage->has('command_input_days') ? $this->sharedStorage->get('command_input_days') : null);
        $this->iShouldBeNotifiedThatThereAreNoItemsToClean();
    }

    private function getCommandTester(): CommandTester
    {
        $application = new Application($this->kernel);
        $application->add($this->queueCleanupCommand);
        $command = $application->find('webgriffe:akeneo:cleanup-queue');

        return new CommandTester($command);
    }
}
