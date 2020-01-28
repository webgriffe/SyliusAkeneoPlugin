<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Command;

use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webgriffe\SyliusAkeneoPlugin\DateTimeBuilderInterface;
use Webgriffe\SyliusAkeneoPlugin\Entity\QueueItemInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterRegistryInterface;
use Webgriffe\SyliusAkeneoPlugin\Repository\QueueItemRepositoryInterface;
use Webmozart\Assert\Assert;

final class EnqueueCommand extends Command
{
    public const SINCE_OPTION_NAME = 'since';

    public const SINCE_FILE_OPTION_NAME = 'since-file';

    protected static $defaultName = 'webgriffe:akeneo:enqueue';

    /** @var QueueItemRepositoryInterface */
    private $queueItemRepository;

    /** @var FactoryInterface */
    private $queueItemFactory;

    /** @var DateTimeBuilderInterface */
    private $dateTimeBuilder;

    /** @var ImporterRegistryInterface */
    private $importerRegistry;

    public function __construct(
        QueueItemRepositoryInterface $queueItemRepository,
        FactoryInterface $queueItemFactory,
        DateTimeBuilderInterface $dateTimeBuilder,
        ImporterRegistryInterface $importerRegistry
    ) {
        $this->queueItemRepository = $queueItemRepository;
        $this->queueItemFactory = $queueItemFactory;
        $this->dateTimeBuilder = $dateTimeBuilder;
        $this->importerRegistry = $importerRegistry;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription(
            sprintf(
                'Retrieve from Akeneo products that has been modified since the date/datetime specified' .
                ' with --%s parameter or in a file with --%s parameter',
                self::SINCE_OPTION_NAME,
                self::SINCE_FILE_OPTION_NAME
            )
        );
        $this->addOption(
            self::SINCE_OPTION_NAME,
            's',
            InputOption::VALUE_REQUIRED,
            'Date or datetime with format Y-m-d H:i:s'
        );
        $this->addOption(
            self::SINCE_FILE_OPTION_NAME,
            'sf',
            InputOption::VALUE_REQUIRED,
            'Relative or absolute path to a file containing a datetime'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filepath = null;
        if ($sinceOptionValue = $input->getOption(self::SINCE_OPTION_NAME)) {
            try {
                Assert::string($sinceOptionValue);
                /** @var string $sinceOptionValue */
                $sinceDate = new \DateTime($sinceOptionValue);
            } catch (\Throwable $t) {
                throw new \InvalidArgumentException(
                    sprintf('The "%s" argument must be a valid date', self::SINCE_OPTION_NAME)
                );
            }
        } elseif ($filepath = $input->getOption(self::SINCE_FILE_OPTION_NAME)) {
            Assert::string($filepath);
            /** @var string $filepath */
            $sinceDate = $this->getSinceDateByFile($filepath);
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'One of "--%s" and "--%s" paramaters must be specified',
                    self::SINCE_OPTION_NAME,
                    self::SINCE_FILE_OPTION_NAME
                )
            );
        }

        foreach ($this->importerRegistry->all() as $importer) {
            $identifiers = $importer->getIdentifiersModifiedSince($sinceDate);
            if (empty($identifiers)) {
                $output->writeln(
                    sprintf(
                        'There are no <info>%s</info> entities modified since <info>%s</info>',
                        $importer->getAkeneoEntity(),
                        $sinceDate->format('Y-m-d H:i:s')
                    )
                );

                continue;
            }
            foreach ($identifiers as $identifier) {
                if ($this->isEntityAlreadyQueuedToImport($importer->getAkeneoEntity(), $identifier)) {
                    continue;
                }
                /** @var QueueItemInterface $queueItem */
                $queueItem = $this->queueItemFactory->createNew();
                Assert::isInstanceOf($queueItem, QueueItemInterface::class);
                $queueItem->setAkeneoEntity($importer->getAkeneoEntity());
                $queueItem->setAkeneoIdentifier($identifier);
                $queueItem->setCreatedAt(new \DateTime());
                $this->queueItemRepository->add($queueItem);
                $output->writeln(
                    sprintf(
                        '<info>%s</info> entity with identifier <info>%s</info> enqueued.',
                        $importer->getAkeneoEntity(),
                        $sinceDate->format('Y-m-d H:i:s')
                    )
                );
            }
        }

        if ($filepath) {
            $this->writeSinceDateFile($filepath);
        }

        return 0;
    }

    private function getSinceDateByFile(string $filepath): \DateTime
    {
        if (!file_exists($filepath)) {
            throw new \InvalidArgumentException(
                sprintf('The file "%s" does not exists', $filepath)
            );
        }
        if (!is_readable($filepath)) {
            throw new \InvalidArgumentException(
                sprintf('The file "%s" is not readable', $filepath)
            );
        }
        if (!is_writable($filepath)) {
            throw new \InvalidArgumentException(
                sprintf('The file "%s" is not writable', $filepath)
            );
        }

        try {
            $content = file_get_contents($filepath);
            Assert::string($content);
            /** @var string $content */
            $sinceDate = new \DateTime($content);
        } catch (\Throwable $t) {
            throw new \RuntimeException(sprintf('The file "%s" must contain a valid datetime', $filepath), 0, $t);
        }

        return $sinceDate;
    }

    private function writeSinceDateFile(string $filepath): void
    {
        file_put_contents($filepath, $this->dateTimeBuilder->build()->format('Y-m-d H:i:s'));
    }

    private function isEntityAlreadyQueuedToImport(string $akeneoEntity, string $akeneoIdentifier): bool
    {
        $queueItem = $this->queueItemRepository->findOneToImport($akeneoEntity, $akeneoIdentifier);
        if ($queueItem) {
            return true;
        }

        return false;
    }
}
