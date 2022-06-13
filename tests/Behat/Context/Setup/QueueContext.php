<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Sylius\Behat\Service\SharedStorageInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Webgriffe\SyliusAkeneoPlugin\Entity\QueueItemInterface;
use Webgriffe\SyliusAkeneoPlugin\Message\ItemImport;
use Webgriffe\SyliusAkeneoPlugin\Repository\QueueItemRepositoryInterface;

final class QueueContext implements Context
{
    public function __construct(
        private SharedStorageInterface $sharedStorage,
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @Given there is one item to import with identifier :identifier for the :importer importer in the Akeneo queue
     * @Given there is a not imported item with identifier :identifier for the :importer importer in the Akeneo queue
     */
    public function thereIsOneProductToImportWithIdentifierInTheAkeneoQueue(string $identifier, string $importer): void
    {
        $itemImport = new ItemImport($importer, $identifier);
        $this->messageBus->dispatch($itemImport);

        $this->sharedStorage->set('item_import', $itemImport);
    }

    /**
     * @Given there is one product associations to import with identifier :identifier in the Akeneo queue
     */
    public function thereIsOneProductAssociationsToImportWithIdentifierInTheAkeneoQueue(string $identifier): void
    {
        $itemImport = new ItemImport('ProductAssociations', $identifier);
        $this->messageBus->dispatch($itemImport);

        $this->sharedStorage->set('item', $itemImport);
    }

    /**
     * @Given /^there is an already imported item with identifier "([^"]*)" for the "([^"]*)" importer in the Akeneo queue$/
     */
    public function thereIsAnAlreadyImportedItemWithIdentifierForTheImporterInTheAkeneoQueue(string $identifier, string $importer): void
    {
        /** @var QueueItemInterface $queueItem */
        $queueItem = $this->queueItemFactory->createNew();
        $queueItem->setAkeneoEntity($importer);
        $queueItem->setAkeneoIdentifier($identifier);
        $queueItem->setCreatedAt(new \DateTime());
        $queueItem->setImportedAt(new \DateTime());
        $this->queueItemRepository->add($queueItem);
        $this->sharedStorage->set('item', $queueItem);
    }

    /**
     * @Given /^(this item) has been imported (\d+) days ago$/
     */
    public function thisItemHasBeenImportedDaysAgo(QueueItemInterface $queueItem, int $days): void
    {
        $queueItem->setImportedAt(new \DateTime("$days days ago"));
        $this->queueItemRepository->add($queueItem);
    }

    /**
     * @Given /^(this item) has been imported now$/
     */
    public function thisItemHasBeenImportedNow(QueueItemInterface $queueItem): void
    {
        $queueItem->setImportedAt(new \DateTime());
        $this->queueItemRepository->add($queueItem);
    }
}
