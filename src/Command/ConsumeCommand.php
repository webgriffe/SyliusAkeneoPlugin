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

    public function __construct(
        QueueItemRepositoryInterface $queueItemRepository,
        ImporterInterface $productModelImporter
    ) {
        $this->queueItemRepository = $queueItemRepository;
        $this->productModelImporter = $productModelImporter;
        parent::__construct();
    }

    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queueItems = $this->queueItemRepository->findAllToImport();
        foreach ($queueItems as $queueItem) {
            $importer = $this->resolveImporter($queueItem->getAkeneoEntity());
            $importer->import($queueItem->getAkeneoIdentifier());
            $queueItem->setImportedAt(new \DateTime());
            // TODO persist $queueItem with imported date
        }
    }

    private function resolveImporter(string $akeneoEntity): ImporterInterface
    {
        // TODO implement better Akeneo entity importer resolver
        $map = [
            QueueItemInterface::AKENEO_ENTITY_PRODUCT_MODEL => $this->productModelImporter,
        ];

        return $map[$akeneoEntity];
    }
}
