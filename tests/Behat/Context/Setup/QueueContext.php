<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webgriffe\SyliusAkeneoPlugin\Entity\QueueItemInterface;
use Webgriffe\SyliusAkeneoPlugin\Repository\QueueItemRepositoryInterface;

final class QueueContext implements Context
{
    /**
     * @var FactoryInterface
     */
    private $queueItemFactory;
    /**
     * @var QueueItemRepositoryInterface
     */
    private $queueItemRepository;

    public function __construct(FactoryInterface $queueItemFactory, QueueItemRepositoryInterface $queueItemRepository)
    {
        $this->queueItemFactory = $queueItemFactory;
        $this->queueItemRepository = $queueItemRepository;
    }

    /**
     * @Given /^there is one product model to import with identifier "([^"]*)" in the Akeneo queue$/
     */
    public function thereIsOneProductModelToImportWithIdentifierInTheAkeneoQueue(string $identifier)
    {
        /** @var QueueItemInterface $queueItem */
        $queueItem = $this->queueItemFactory->createNew();
        $queueItem->setAkeneoEntity(QueueItemInterface::AKENEO_ENTITY_PRODUCT_MODEL);
        $queueItem->setAkeneoIdentifier($identifier);
        $queueItem->setCreatedAt(new \DateTime());
        $this->queueItemRepository->add($queueItem);
    }
}
