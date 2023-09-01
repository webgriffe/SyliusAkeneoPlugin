<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\MessageHandler;

use Doctrine\DBAL\Exception as DoctineException;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Throwable;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterRegistryInterface;
use Webgriffe\SyliusAkeneoPlugin\Message\ItemImport;
use Webgriffe\SyliusAkeneoPlugin\TemporaryFilesManager;

final class ItemImportHandler
{
    public function __construct(
        private ImporterRegistryInterface $importerRegistry,
        private TemporaryFilesManager $temporaryFilesManager,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(ItemImport $message): void
    {
        $akeneoIdentifier = $message->getAkeneoIdentifier();
        $importer = $this->resolveImporter($message->getAkeneoEntity());

        try {
            $importer->import($akeneoIdentifier);
        } catch (DoctineException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->entityManager->clear();
            // TODO Log to DB here $this->logger->log($e); - See: https://github.com/webgriffe/SyliusAkeneoPlugin/issues/168
            throw $e;
        } finally {
            $this->temporaryFilesManager->deleteAllTemporaryFiles();
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
