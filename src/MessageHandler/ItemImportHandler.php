<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\MessageHandler;

use RuntimeException;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterRegistryInterface;
use Webgriffe\SyliusAkeneoPlugin\Message\ItemImport;
use Webgriffe\SyliusAkeneoPlugin\Product\Importer;
use Webgriffe\SyliusAkeneoPlugin\TemporaryFilesManager;
use Webgriffe\SyliusAkeneoPlugin\TemporaryFilesManagerInterface;

final class ItemImportHandler
{
    public function __construct(
        private ImporterRegistryInterface $importerRegistry,
        private TemporaryFilesManager $temporaryFilesManager,
    ) {
    }

    public function __invoke(ItemImport $message): void
    {
        $akeneoIdentifier = $message->getAkeneoIdentifier();
        $importer = $this->resolveImporter($message->getAkeneoEntity());

        try {
            $importer->import($akeneoIdentifier);
        } finally {
            if ($message->getAkeneoEntity() === Importer::AKENEO_ENTITY) {
                $this->temporaryFilesManager->deleteAllTemporaryFiles(TemporaryFilesManagerInterface::PRODUCT_VARIANT_PREFIX . $akeneoIdentifier);
            }
        }
    }

    private function resolveImporter(string $akeneoEntity): ImporterInterface
    {
        foreach ($this->importerRegistry->all() as $importer) {
            if ($importer->getAkeneoEntity() === $akeneoEntity) {
                return $importer;
            }
        }

        throw new RuntimeException(sprintf('Cannot find suitable importer for entity "%s".', $akeneoEntity));
    }
}
