<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterRegistryInterface;
use Webgriffe\SyliusAkeneoPlugin\Repository\QueueItemRepositoryInterface;

final class ConsumeCommand extends Command
{
    use LockableTrait;

    protected static $defaultName = 'webgriffe:akeneo:consume';

    public function __construct(
        private QueueItemRepositoryInterface $queueItemRepository,
        private ImporterRegistryInterface $importerRegistry,
        private ManagerRegistry $managerRegistry,
    ) {
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
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');

            return 0;
        }

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
                    $this->release();

                    throw $t;
                }
                $queueItem->setErrorMessage($t->getMessage() . \PHP_EOL . $t->getTraceAsString());
                $output->writeln(
                    sprintf(
                        'There has been an error importing <info>%s</info> entity with identifier <info>%s</info>. ' .
                        'The error was: <error>%s</error>.',
                        $queueItem->getAkeneoEntity(),
                        $akeneoIdentifier,
                        $t->getMessage(),
                    ),
                );
                if ($output->isVeryVerbose()) {
                    $output->writeln((string) $t);
                }
            }

            $this->queueItemRepository->add($queueItem);
            $output->writeln(
                sprintf(
                    '<info>%s</info> entity with identifier <info>%s</info> has been imported.',
                    $queueItem->getAkeneoEntity(),
                    $akeneoIdentifier,
                ),
            );
        }

        $this->release();

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
