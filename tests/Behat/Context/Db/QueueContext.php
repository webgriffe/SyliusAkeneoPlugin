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
     * @Given /^the queue item for product with identifier "([^"]*)" has been marked as imported$/
     */
    public function theQueueItemForProductWithIdentifierHasBeenMarkedAsImported(string $identifier)
    {
        $queueItem = $this->getQueueItemByProductIdentifier($identifier);
        Assert::notNull($queueItem->getImportedAt());
    }

    /**
     * @Given /^the queue item for product with identifier "([^"]*)" has not been marked as imported$/
     */
    public function theQueueItemForProductWithIdentifierHasNotBeenMarkedAsImported(string $identifier)
    {
        $queueItem = $this->getQueueItemByProductIdentifier($identifier);
        Assert::null($queueItem->getImportedAt());
    }

    /**
     * @Given /^the queue item for product with identifier "([^"]*)" has an error message$/
     */
    public function theQueueItemHasAnErrorMessage(string $identifier)
    {
        $queueItem = $this->getQueueItemByProductIdentifier($identifier);
        Assert::notNull($queueItem->getErrorMessage());
    }

    /**
     * @Given /^the queue item for product with identifier "([^"]*)" has an error message containing "([^"]*)"$/
     */
    public function theQueueItemHasAnErrorMessageContaining(string $identifier, string $message)
    {
        $queueItem = $this->getQueueItemByProductIdentifier($identifier);
        Assert::contains($queueItem->getErrorMessage(), $message);
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
        Assert::isEmpty($this->queueItemRepository->findBy(['akeneoEntity' => 'Product']));
    }

    /**
     * @Then /^there should be no product associations in the Akeneo queue$/
     */
    public function thereShouldBeNoProductAssociationsInTheAkeneoQueue()
    {
        Assert::isEmpty($this->queueItemRepository->findBy(['akeneoEntity' => 'ProductAssociations']));
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

    private function getQueueItemByProductIdentifier(string $identifier): QueueItemInterface
    {
        /** @var QueueItemInterface $item */
        $item = $this->queueItemRepository->findOneBy(['akeneoEntity' => 'Product', 'akeneoIdentifier' => $identifier]);
        Assert::isInstanceOf($item, QueueItemInterface::class);

        return $item;
    }
}
