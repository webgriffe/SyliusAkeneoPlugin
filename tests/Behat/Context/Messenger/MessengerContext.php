<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Messenger;

use Behat\Behat\Context\Context;
use InvalidArgumentException;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Throwable;
use Webgriffe\SyliusAkeneoPlugin\Message\ItemImport;
use Webgriffe\SyliusAkeneoPlugin\MessageHandler\ItemImportHandler;
use Webmozart\Assert\Assert;

final class MessengerContext implements Context
{
    private array $failedMessages = [];

    public function __construct(
        private InMemoryTransport $transport,
        private ItemImportHandler $itemImportHandler,
    ) {
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
            $this->queueItemRepository->findOneBy(['akeneoEntity' => $importer, 'akeneoIdentifier' => $identifier]),
        );
    }

    /**
     * @Then /^the queue item with identifier "([^"]*)" for the "([^"]*)" importer should be in the Akeneo queue$/
     */
    public function theProductShouldBeInTheAkeneoQueue(string $identifier, string $importer): void
    {
        Assert::isInstanceOf(
            $this->queueItemRepository->findOneBy(
                ['akeneoEntity' => $importer, 'akeneoIdentifier' => $identifier],
            ),
            QueueItemInterface::class,
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
            ['akeneoEntity' => $importer, 'akeneoIdentifier' => $identifier],
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

    private function getQueueItemByImporterAndIdentifier(string $importer, string $identifier): ItemImport
    {
        $sentMessages = $this->transport->get();
        foreach ($sentMessages as $sentMessage) {
            /** @var ItemImport|object $message */
            $message = $sentMessage->getMessage();
            if ($message instanceof ItemImport && $message->getAkeneoEntity() === $importer && $message->getAkeneoIdentifier() === $identifier) {
                return $message;
            }
        }

        throw new InvalidArgumentException(sprintf('No message founded for importer "%s" and identifier "%s".', $importer, $identifier));
    }

    /**
     * @When I consume the messages
     */
    public function iConsumeTheMessages(): void
    {
        foreach ($this->transport->get() as $envelope) {
            $message = $envelope->getMessage();
            if (!$message instanceof ItemImport) {
                continue;
            }
            try {
                $this->itemImportHandler->__invoke($message);
            } catch (Throwable $throwable) {
                $this->failedMessages[] = $message;

                continue;
            }
        }
    }

    /**
     * @Then the item import message for :identifier identifier and the :importer importer should have failed
     */
    public function theItemImportMessageForProductShouldHaveFailed(string $identifier, string $importer): void
    {
        $failedMessages = array_filter($this->failedMessages, static function (ItemImport $failedMessage) use ($identifier, $importer): bool {
            return $failedMessage->getAkeneoIdentifier() === $identifier && $failedMessage->getAkeneoEntity() === $importer;
        });
        Assert::count($failedMessages, 1);
    }
}
