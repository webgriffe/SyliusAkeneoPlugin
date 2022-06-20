<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Sylius\Behat\Service\SharedStorageInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Webgriffe\SyliusAkeneoPlugin\Message\ItemImport;

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
}
