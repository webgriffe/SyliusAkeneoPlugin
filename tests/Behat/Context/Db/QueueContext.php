<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Db;

use Behat\Behat\Context\Context;
use Webgriffe\SyliusAkeneoPlugin\Entity\QueueItemInterface;
use Webgriffe\SyliusAkeneoPlugin\Repository\QueueItemRepositoryInterface;
use Webmozart\Assert\Assert;

final class QueueContext implements Context
{
    public function __construct(private QueueItemRepositoryInterface $queueItemRepository)
    {
    }

    /**
     * @Given /^the queue item with identifier "([^"]*)" for the "([^"]*)" importer has been marked as imported$/
     */
    public function theQueueItemForProductWithIdentifierHasBeenMarkedAsImported(string $identifier, string $importer): void
    {
        $queueItem = $this->getQueueItemByImporterAndIdentifier($importer, $identifier);
        Assert::notNull($queueItem->getImportedAt());
    }

    /**
     * @Given /^the queue item with identifier "([^"]*)" for the "([^"]*)" importer has not been marked as imported$/
     */
    public function theQueueItemForProductWithIdentifierHasNotBeenMarkedAsImported(string $identifier, string $importer): void
    {
        $queueItem = $this->getQueueItemByImporterAndIdentifier($importer, $identifier);
        Assert::null($queueItem->getImportedAt());
    }

    /**
     * @Given /^the queue item with identifier "([^"]*)" for the "([^"]*)" importer has an error message$/
     */
    public function theQueueItemHasAnErrorMessage(string $identifier, string $importer): void
    {
        $queueItem = $this->getQueueItemByImporterAndIdentifier($importer, $identifier);
        Assert::notNull($queueItem->getErrorMessage());
    }

    /**
     * @Given /^the queue item with identifier "([^"]*)" for the "([^"]*)" importer has an error message containing "([^"]*)"$/
     */
    public function theQueueItemHasAnErrorMessageContaining(string $identifier, string $importer, string $message): void
    {
        $queueItem = $this->getQueueItemByImporterAndIdentifier($importer, $identifier);
        Assert::contains((string) $queueItem->getErrorMessage(), $message);
    }

    /**
     * @Then /^the queue item with identifier "([^"]*)" for the "([^"]*)" importer should not be in the Akeneo queue$/
     */
    public function theProductShouldNotBeInTheAkeneoQueue(string $identifier, string $importer): void
    {
        Assert::null(
            $this->queueItemRepository->findOneBy(['akeneoEntity' => $importer, 'akeneoIdentifier' => $identifier])
        );
    }

    /**
     * @Then /^the queue item with identifier "([^"]*)" for the "([^"]*)" importer should be in the Akeneo queue$/
     */
    public function theProductShouldBeInTheAkeneoQueue(string $identifier, string $importer): void
    {
        Assert::isInstanceOf(
            $this->queueItemRepository->findOneBy(
                ['akeneoEntity' => $importer, 'akeneoIdentifier' => $identifier]
            ),
            QueueItemInterface::class
        );
    }

    /**
     * @Then /^there should be no item in the queue for the "([^"]*)" importer/
     */
    public function thereShouldBeNoProductInTheAkeneoQueue(string $importer): void
    {
        Assert::isEmpty($this->queueItemRepository->findBy(['akeneoEntity' => $importer]));
    }

    /**
     * @Then /^there should be no item in the Akeneo queue$/
     */
    public function thereShouldBeNoItemInTheAkeneoQueue(): void
    {
        Assert::isEmpty($this->queueItemRepository->findAll());
    }

    /**
     * @Then /^there should be only one queue item with identifier "([^"]*)" for the "([^"]*)" importer in the Akeneo queue$/
     */
    public function thereShouldBeOnlyOneProductQueueItemForInTheAkeneoQueue(string $identifier, string $importer): void
    {
        $items = $this->queueItemRepository->findBy(
            ['akeneoEntity' => $importer, 'akeneoIdentifier' => $identifier]
        );
        Assert::count($items, 1);
    }

    /**
     * @Then /^there should be (\d+) items for the "([^"]*)" importer in the Akeneo queue$/
     */
    public function thereShouldBeItemsForTheImporterInTheAkeneoQueue(int $count, string $importer): void
    {
        $items = $this->queueItemRepository->findBy(['akeneoEntity' => $importer]);
        Assert::count($items, $count);
    }

    /**
     * @Then /^there should be items for the "([^"]*)" importer only in the Akeneo queue$/
     */
    public function thereShouldBeItemsForTheImporterOnlyInTheAkeneoQueue(string $importer): void
    {
        $importerItems = $this->queueItemRepository->findBy(['akeneoEntity' => $importer]);
        Assert::count($this->queueItemRepository->findAll(), count($importerItems));
    }

    private function getQueueItemByImporterAndIdentifier(string $importer, string $identifier): QueueItemInterface
    {
        /** @var QueueItemInterface|null $item */
        $item = $this->queueItemRepository->findOneBy(['akeneoEntity' => $importer, 'akeneoIdentifier' => $identifier]);
        Assert::isInstanceOf($item, QueueItemInterface::class);

        return $item;
    }
}
