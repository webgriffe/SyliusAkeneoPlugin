<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterRegistryInterface;
use Webgriffe\SyliusAkeneoPlugin\Repository\QueueItemRepositoryInterface;

final class ConsumeCommand extends Command
{
    protected static $defaultName = 'webgriffe:akeneo:consume';

    /** @var QueueItemRepositoryInterface */
    private $queueItemRepository;

    /** @var ImporterRegistryInterface */
    private $importerRegistry;

    /** @var ManagerRegistry */
    private $managerRegistry;

    public function __construct(
        QueueItemRepositoryInterface $queueItemRepository,
        ImporterRegistryInterface $importerRegistry,
        ManagerRegistry $managerRegistry
    ) {
        $this->queueItemRepository = $queueItemRepository;
        $this->importerRegistry = $importerRegistry;
        $this->managerRegistry = $managerRegistry;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Process the Queue by calling the proper importer for each item');
    }

    /**
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queueItems = $this->queueItemRepository->findAllToImport();
        foreach ($queueItems as $queueItem) {
            $akeneoIdentifier = $queueItem->getAkeneoIdentifier();

            try {
                $importer = $this->resolveImporter($queueItem->getAkeneoEntity());
                $importer->import($akeneoIdentifier);
                $queueItem->setImportedAt(new \DateTime());
                $queueItem->setErrorMessage(null);
            } catch (\Throwable $t) {
                /** @var EntityManagerInterface $objectManager */
                $objectManager = $this->managerRegistry->getManager();
                if (!$objectManager->isOpen()) {
                    throw $t;
                }
                $queueItem->setErrorMessage($t->getMessage() . \PHP_EOL . $t->getTraceAsString());
            }

            $this->queueItemRepository->add($queueItem);
        }

        return 0;
    }

    private function resolveImporter(string $akeneoEntity): ImporterInterface
    {
        foreach ($this->importerRegistry->all() as $importer) {
            if ($importer->getAkeneoEntity() === $akeneoEntity) {
                return $importer;
            }
        }

        throw new \RuntimeException(sprintf('Cannot find suitable importer for entity "%s".', $akeneoEntity));
    }
}
