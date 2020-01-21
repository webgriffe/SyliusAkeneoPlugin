<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Db;

use Behat\Behat\Context\Context;
use Sylius\Behat\Service\SharedStorageInterface;
use Webgriffe\SyliusAkeneoPlugin\Entity\QueueItemInterface;
use Webmozart\Assert\Assert;

final class QueueContext implements Context
{
    /** @var SharedStorageInterface */
    private $sharedStorage;

    public function __construct(SharedStorageInterface $sharedStorage)
    {
        $this->sharedStorage = $sharedStorage;
    }

    /**
     * @Given /^the queue item has been marked as imported$/
     */
    public function theQueueItemHasBeenMarkedAsImported()
    {
        /** @var QueueItemInterface $queueItem */
        $queueItem = $this->sharedStorage->get('queue_item');
        Assert::notNull($queueItem->getImportedAt());
    }

    /**
     * @Given /^the queue item has not been marked as imported$/
     */
    public function theQueueItemHasNotBeenMarkedAsImported()
    {
        /** @var QueueItemInterface $queueItem */
        $queueItem = $this->sharedStorage->get('queue_item');
        Assert::null($queueItem->getImportedAt());
    }

    /**
     * @Given /^the queue item has an error message$/
     */
    public function theQueueItemHasAnErrorMessage()
    {
        /** @var QueueItemInterface $queueItem */
        $queueItem = $this->sharedStorage->get('queue_item');
        Assert::notNull($queueItem->getErrorMessage());
    }
}
