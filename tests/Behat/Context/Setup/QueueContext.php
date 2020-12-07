<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webgriffe\SyliusAkeneoPlugin\Entity\QueueItemInterface;
use Webgriffe\SyliusAkeneoPlugin\Repository\QueueItemRepositoryInterface;

final class QueueContext implements Context
{
    /** @var FactoryInterface */
    private $queueItemFactory;

    /** @var QueueItemRepositoryInterface */
    private $queueItemRepository;

    public function __construct(
        FactoryInterface $queueItemFactory,
        QueueItemRepositoryInterface $queueItemRepository
    ) {
        $this->queueItemFactory = $queueItemFactory;
        $this->queueItemRepository = $queueItemRepository;
    }

    /**
     * @Given /^there is one item to import with identifier "([^"]*)" for the "([^"]*)" importer in the Akeneo queue$/
     * @Given /^there is a not imported item with identifier "([^"]*)" for the "([^"]*)" importer in the Akeneo queue$/
     */
    public function thereIsOneProductToImportWithIdentifierInTheAkeneoQueue(string $identifier, string $importer)
    {
        /** @var QueueItemInterface $queueItem */
        $queueItem = $this->queueItemFactory->createNew();
        $queueItem->setAkeneoEntity($importer);
        $queueItem->setAkeneoIdentifier($identifier);
        $queueItem->setCreatedAt(new \DateTime());
        $this->queueItemRepository->add($queueItem);
    }

    /**
     * @Given /^there is one product associations to import with identifier "([^"]*)" in the Akeneo queue$/
     */
    public function thereIsOneProductAssociationsToImportWithIdentifierInTheAkeneoQueue(string $identifier)
    {
        /** @var QueueItemInterface $queueItem */
        $queueItem = $this->queueItemFactory->createNew();
        $queueItem->setAkeneoEntity('ProductAssociations');
        $queueItem->setAkeneoIdentifier($identifier);
        $queueItem->setCreatedAt(new \DateTime());
        $this->queueItemRepository->add($queueItem);
    }

    /**
     * @Given /^there is an already imported item with identifier "([^"]*)" for the "([^"]*)" importer in the Akeneo queue$/
     */
    public function thereIsAnAlreadyImportedItemWithIdentifierForTheImporterInTheAkeneoQueue(string $identifier, string $importer)
    {
        /** @var QueueItemInterface $queueItem */
        $queueItem = $this->queueItemFactory->createNew();
        $queueItem->setAkeneoEntity($importer);
        $queueItem->setAkeneoIdentifier($identifier);
        $queueItem->setCreatedAt(new \DateTime());
        $queueItem->setImportedAt(new \DateTime());
        $this->queueItemRepository->add($queueItem);
    }
}
