<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webgriffe\SyliusAkeneoPlugin\Entity\QueueItemInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webgriffe\SyliusAkeneoPlugin\Repository\QueueItemRepositoryInterface;

final class ConsumeCommand extends Command
{
    protected static $defaultName = 'webgriffe:akeneo:consume';

    /** @var QueueItemRepositoryInterface */
    private $queueItemRepository;

    /** @var ImporterInterface */
    private $productModelImporter;

    /** @var ImporterInterface */
    private $productImporter;

    public function __construct(
        QueueItemRepositoryInterface $queueItemRepository,
        ImporterInterface $productModelImporter,
        ImporterInterface $productImporter
    ) {
        $this->queueItemRepository = $queueItemRepository;
        $this->productModelImporter = $productModelImporter;
        $this->productImporter = $productImporter;
        parent::__construct();
    }

    protected function configure(): void
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queueItems = $this->queueItemRepository->findAllToImport();
        foreach ($queueItems as $queueItem) {
            try {
                $importer = $this->resolveImporter($queueItem->getAkeneoEntity());
                $importer->import($queueItem->getAkeneoIdentifier());
                $queueItem->setImportedAt(new \DateTime());
            } catch (\Throwable $t) {
                $queueItem->setErrorMessage($t->getMessage());
            }
            // TODO persist $queueItem with imported date
        }

        return 0;
    }

    private function resolveImporter(string $akeneoEntity): ImporterInterface
    {
        // TODO implement better Akeneo entity importer resolver
        $map = [
            QueueItemInterface::AKENEO_ENTITY_PRODUCT_MODEL => $this->productModelImporter,
            QueueItemInterface::AKENEO_ENTITY_PRODUCT => $this->productImporter,
        ];

        return $map[$akeneoEntity];
    }
}
