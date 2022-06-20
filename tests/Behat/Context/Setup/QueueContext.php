<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webgriffe\SyliusAkeneoPlugin\Entity\QueueItemInterface;
use Webgriffe\SyliusAkeneoPlugin\Repository\QueueItemRepositoryInterface;

final class QueueContext implements Context
{
    /** @var FactoryInterface */
    private $queueItemFactory;

    /** @var QueueItemRepositoryInterface */
    private $queueItemRepository;

    /** @var SharedStorageInterface */
    private $sharedStorage;

    public function __construct(
        FactoryInterface $queueItemFactory,
        QueueItemRepositoryInterface $queueItemRepository,
        SharedStorageInterface $sharedStorage
    ) {
        $this->queueItemFactory = $queueItemFactory;
        $this->queueItemRepository = $queueItemRepository;
        $this->sharedStorage = $sharedStorage;
    }

    /**
     * @Given /^there is one item to import with identifier "([^"]*)" for the "([^"]*)" importer in the Akeneo queue$/
     * @Given /^there is a not imported item with identifier "([^"]*)" for the "([^"]*)" importer in the Akeneo queue$/
     */
    public function thereIsOneProductToImportWithIdentifierInTheAkeneoQueue(string $identifier, string $importer): void
    {
        /** @var QueueItemInterface $queueItem */
        $queueItem = $this->queueItemFactory->createNew();
        $queueItem->setAkeneoEntity($importer);
        $queueItem->setAkeneoIdentifier($identifier);
        $queueItem->setCreatedAt(new \DateTime());
        $this->queueItemRepository->add($queueItem);
        $this->sharedStorage->set('item', $queueItem);
    }

    /**
     * @Given /^there is one product associations to import with identifier "([^"]*)" in the Akeneo queue$/
     */
    public function thereIsOneProductAssociationsToImportWithIdentifierInTheAkeneoQueue(string $identifier): void
    {
        /** @var QueueItemInterface $queueItem */
        $queueItem = $this->queueItemFactory->createNew();
        $queueItem->setAkeneoEntity('ProductAssociations');
        $queueItem->setAkeneoIdentifier($identifier);
        $queueItem->setCreatedAt(new \DateTime());
        $this->queueItemRepository->add($queueItem);
        $this->sharedStorage->set('item', $queueItem);
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
