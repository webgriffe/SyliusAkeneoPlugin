<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Db;

use Behat\Behat\Context\Context;
use Sylius\Behat\Service\SharedStorageInterface;
use Webgriffe\SyliusAkeneoPlugin\Entity\QueueItemInterface;
use Webgriffe\SyliusAkeneoPlugin\Repository\QueueItemRepositoryInterface;
use Webmozart\Assert\Assert;

final class QueueContext implements Context
{
    /** @var SharedStorageInterface */
    private $sharedStorage;

    /** @var QueueItemRepositoryInterface */
    private $queueItemRepository;

    public function __construct(
        SharedStorageInterface $sharedStorage,
        QueueItemRepositoryInterface $queueItemRepository
    ) {
        $this->sharedStorage = $sharedStorage;
        $this->queueItemRepository = $queueItemRepository;
    }

    /**
     * @Given /^the(?:| last) queue item has been marked as imported$/
     */
    public function theQueueItemHasBeenMarkedAsImported()
    {
        /** @var QueueItemInterface $queueItem */
        $queueItem = $this->sharedStorage->get('queue_item');
        Assert::notNull($queueItem->getImportedAt());
    }

    /**
     * @Given /^the(?:| last) queue item has not been marked as imported$/
     */
    public function theQueueItemHasNotBeenMarkedAsImported()
    {
        /** @var QueueItemInterface $queueItem */
        $queueItem = $this->sharedStorage->get('queue_item');
        Assert::null($queueItem->getImportedAt());
    }

    /**
     * @Given /^the(?:| last) queue item has an error message$/
     */
    public function theQueueItemHasAnErrorMessage()
    {
        /** @var QueueItemInterface $queueItem */
        $queueItem = $this->sharedStorage->get('queue_item');
        $queueItem = $this->queueItemRepository->find($queueItem->getId());
        Assert::notNull($queueItem->getErrorMessage());
    }

    /**
     * @Then /^the product "([^"]*)" should not be in the Akeneo queue$/
     */
    public function theProductShouldNotBeInTheAkeneoQueue(string $identifier)
    {
        Assert::null(
            $this->queueItemRepository->findOneBy(
                ['akeneoEntity' => 'Product', 'akeneoIdentifier' => $identifier]
            )
        );
    }

    /**
     * @Then /^the product associations for product "([^"]*)" should not be in the Akeneo queue$/
     */
    public function theProductAssociationsForProductShouldNotBeInTheAkeneoQueue(string $identifier)
    {
        Assert::null(
            $this->queueItemRepository->findOneBy(
                ['akeneoEntity' => 'ProductAssociations', 'akeneoIdentifier' => $identifier]
            )
        );
    }

    /**
     * @Then /^the product "([^"]*)" should be in the Akeneo queue$/
     */
    public function theProductShouldBeInTheAkeneoQueue(string $identifier)
    {
        Assert::isInstanceOf(
            $this->queueItemRepository->findOneBy(
                ['akeneoEntity' => 'Product', 'akeneoIdentifier' => $identifier]
            ),
            QueueItemInterface::class
        );
    }

    /**
     * @Then /^the product associations for product "([^"]*)" should be in the Akeneo queue$/
     */
    public function theProductAssociationsForProductShouldBeInTheAkeneoQueue(string $identifier)
    {
        Assert::isInstanceOf(
            $this->queueItemRepository->findOneBy(
                ['akeneoEntity' => 'ProductAssociations', 'akeneoIdentifier' => $identifier]
            ),
            QueueItemInterface::class
        );
    }

    /**
     * @Then /^there should be no product in the Akeneo queue$/
     */
    public function thereShouldBeNoProductInTheAkeneoQueue()
    {
        Assert::isEmpty(
            $this->queueItemRepository->findBy(['akeneoEntity' => 'Product'])
        );
    }

    /**
     * @Then /^there should be no product associations in the Akeneo queue$/
     */
    public function thereShouldBeNoProductAssociationsInTheAkeneoQueue()
    {
        Assert::isEmpty(
            $this->queueItemRepository->findBy(['akeneoEntity' => 'ProductAssociations'])
        );
    }

    /**
     * @Then /^there should be no item in the Akeneo queue$/
     */
    public function thereShouldBeNoItemInTheAkeneoQueue()
    {
        Assert::isEmpty($this->queueItemRepository->findAll());
    }

    /**
     * @Then /^there should be only one product queue item for "([^"]*)" in the Akeneo queue$/
     */
    public function thereShouldBeOnlyOneProductQueueItemForInTheAkeneoQueue(string $identifier)
    {
        $items = $this->queueItemRepository->findBy(
            ['akeneoEntity' => 'Product', 'akeneoIdentifier' => $identifier]
        );
        Assert::count($items, 1);
    }
}
