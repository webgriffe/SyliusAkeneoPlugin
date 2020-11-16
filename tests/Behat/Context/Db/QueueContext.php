<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Db;

use Behat\Behat\Context\Context;
use Webgriffe\SyliusAkeneoPlugin\Entity\QueueItemInterface;
use Webgriffe\SyliusAkeneoPlugin\Repository\QueueItemRepositoryInterface;
use Webmozart\Assert\Assert;

final class QueueContext implements Context
{
    /** @var QueueItemRepositoryInterface */
    private $queueItemRepository;

    public function __construct(
        QueueItemRepositoryInterface $queueItemRepository
    ) {
        $this->queueItemRepository = $queueItemRepository;
    }

    /**
     * @Given /^the queue item with identifier "([^"]*)" for the "([^"]*)" importer has been marked as imported$/
     */
    public function theQueueItemForProductWithIdentifierHasBeenMarkedAsImported(string $identifier, string $importer)
    {
        $queueItem = $this->getQueueItemByImporterAndIdentifier($importer, $identifier);
        Assert::notNull($queueItem->getImportedAt());
    }

    /**
     * @Given /^the queue item with identifier "([^"]*)" for the "([^"]*)" importer has not been marked as imported$/
     */
    public function theQueueItemForProductWithIdentifierHasNotBeenMarkedAsImported(string $identifier, string $importer)
    {
        $queueItem = $this->getQueueItemByImporterAndIdentifier($importer, $identifier);
        Assert::null($queueItem->getImportedAt());
    }

    /**
     * @Given /^the queue item with identifier "([^"]*)" for the "([^"]*)" importer has an error message$/
     */
    public function theQueueItemHasAnErrorMessage(string $identifier, string $importer)
    {
        $queueItem = $this->getQueueItemByImporterAndIdentifier($importer, $identifier);
        Assert::notNull($queueItem->getErrorMessage());
    }

    /**
     * @Given /^the queue item with identifier "([^"]*)" for the "([^"]*)" importer has an error message containing "([^"]*)"$/
     */
    public function theQueueItemHasAnErrorMessageContaining(string $identifier, string $importer, string $message)
    {
        $queueItem = $this->getQueueItemByImporterAndIdentifier($importer, $identifier);
        Assert::contains($queueItem->getErrorMessage(), $message);
    }

    /**
     * @Then /^the queue item with identifier "([^"]*)" for the "([^"]*)" importer should not be in the Akeneo queue$/
     */
    public function theProductShouldNotBeInTheAkeneoQueue(string $identifier, string $importer)
    {
        Assert::null(
            $this->queueItemRepository->findOneBy(['akeneoEntity' => $importer, 'akeneoIdentifier' => $identifier])
        );
    }

    /**
     * @Then /^the queue item with identifier "([^"]*)" for the "([^"]*)" importer should be in the Akeneo queue$/
     */
    public function theProductShouldBeInTheAkeneoQueue(string $identifier, string $importer)
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
    public function thereShouldBeNoProductInTheAkeneoQueue(string $importer)
    {
        Assert::isEmpty($this->queueItemRepository->findBy(['akeneoEntity' => $importer]));
    }

    /**
     * @Then /^there should be no item in the Akeneo queue$/
     */
    public function thereShouldBeNoItemInTheAkeneoQueue()
    {
        Assert::isEmpty($this->queueItemRepository->findAll());
    }

    /**
     * @Then /^there should be only one queue item with identifier "([^"]*)" for the "([^"]*)" importer in the Akeneo queue$/
     */
    public function thereShouldBeOnlyOneProductQueueItemForInTheAkeneoQueue(string $identifier, string $importer)
    {
        $items = $this->queueItemRepository->findBy(
            ['akeneoEntity' => $importer, 'akeneoIdentifier' => $identifier]
        );
        Assert::count($items, 1);
    }

    /**
     * @Then /^there should be (\d+) items for the "([^"]*)" importer in the Akeneo queue$/
     */
    public function thereShouldBeItemsForTheImporterInTheAkeneoQueue(int $count, string $importer)
    {
        $items = $this->queueItemRepository->findBy(['akeneoEntity' => $importer]);
        Assert::count($items, $count);
    }

    /**
     * @Then /^there should be items for the "([^"]*)" importer only in the Akeneo queue$/
     */
    public function thereShouldBeItemsForTheImporterOnlyInTheAkeneoQueue(string $importer)
    {
        $importerItems = $this->queueItemRepository->findBy(['akeneoEntity' => $importer]);
        Assert::count($this->queueItemRepository->findAll(), count($importerItems));
    }

    private function getQueueItemByImporterAndIdentifier(string $importer, string $identifier): QueueItemInterface
    {
        /** @var QueueItemInterface $item */
        $item = $this->queueItemRepository->findOneBy(['akeneoEntity' => $importer, 'akeneoIdentifier' => $identifier]);
        Assert::isInstanceOf($item, QueueItemInterface::class);

        return $item;
    }
}
