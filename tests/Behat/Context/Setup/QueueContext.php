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
     * @Given /^there is one product to import with identifier "([^"]*)" in the Akeneo queue$/
     */
    public function thereIsOneProductToImportWithIdentifierInTheAkeneoQueue(string $identifier)
    {
        /** @var QueueItemInterface $queueItem */
        $queueItem = $this->queueItemFactory->createNew();
        $queueItem->setAkeneoEntity(QueueItemInterface::AKENEO_ENTITY_PRODUCT);
        $queueItem->setAkeneoIdentifier($identifier);
        $queueItem->setCreatedAt(new \DateTime());
        $this->queueItemRepository->add($queueItem);
        $this->sharedStorage->set('queue_item', $queueItem);
    }
}
