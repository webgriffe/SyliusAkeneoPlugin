<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webgriffe\SyliusAkeneoPlugin\Entity\QueueItemInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webgriffe\SyliusAkeneoPlugin\Repository\QueueItemRepositoryInterface;
use Webmozart\Assert\Assert;

final class ConsumeCommand extends Command
{
    protected static $defaultName = 'webgriffe:akeneo:consume';

    /** @var QueueItemRepositoryInterface */
    private $queueItemRepository;

    /** @var ImporterInterface */
    private $productImporter;

    /** @var ManagerRegistry */
    private $managerRegistry;

    public function __construct(
        QueueItemRepositoryInterface $queueItemRepository,
        ImporterInterface $productImporter,
        ManagerRegistry $managerRegistry
    ) {
        $this->queueItemRepository = $queueItemRepository;
        $this->productImporter = $productImporter;
        $this->managerRegistry = $managerRegistry;
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
            $akeneoIdentifier = $queueItem->getAkeneoIdentifier();

            try {
                $importer = $this->resolveImporter($queueItem->getAkeneoEntity());
                $importer->import($akeneoIdentifier);
                $queueItem->setImportedAt(new \DateTime());
            } catch (\Throwable $t) {
                /** @var EntityManagerInterface $objectManager */
                $objectManager = $this->managerRegistry->getManager();
                if (!$objectManager->isOpen()) {
                    $this->managerRegistry->resetManager();
                    /** @var QueueItemInterface $queueItem */
                    $queueItem = $this->queueItemRepository->find($queueItem->getId());
                    Assert::isInstanceOf($queueItem, QueueItemInterface::class);
                }
                $queueItem->setErrorMessage($t->getMessage());
            }

            $this->queueItemRepository->add($queueItem);
        }

        return 0;
    }

    private function resolveImporter(string $akeneoEntity): ImporterInterface
    {
        // TODO implement better Akeneo entity importer resolver
        $map = [
            QueueItemInterface::AKENEO_ENTITY_PRODUCT => $this->productImporter,
        ];

        return $map[$akeneoEntity];
    }
}
