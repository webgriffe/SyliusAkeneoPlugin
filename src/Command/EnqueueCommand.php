<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Command;

use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
use Webgriffe\SyliusAkeneoPlugin\DateTimeBuilderInterface;
use Webgriffe\SyliusAkeneoPlugin\Entity\QueueItemInterface;
use Webgriffe\SyliusAkeneoPlugin\Repository\QueueItemRepositoryInterface;
use Webmozart\Assert\Assert;

final class EnqueueCommand extends Command
{
    public const SINCE_OPTION_NAME = 'since';

    public const SINCE_FILE_OPTION_NAME = 'since-file';

    protected static $defaultName = 'webgriffe:akeneo:enqueue';

    /** @var ApiClientInterface */
    private $apiClient;

    /** @var QueueItemRepositoryInterface */
    private $queueItemRepository;

    /** @var FactoryInterface */
    private $queueItemFactory;

    /** @var DateTimeBuilderInterface */
    private $dateTimeBuilder;

    public function __construct(
        ApiClientInterface $apiClient,
        QueueItemRepositoryInterface $queueItemRepository,
        FactoryInterface $queueItemFactory,
        DateTimeBuilderInterface $dateTimeBuilder
    ) {
        $this->apiClient = $apiClient;
        $this->queueItemRepository = $queueItemRepository;
        $this->queueItemFactory = $queueItemFactory;
        $this->dateTimeBuilder = $dateTimeBuilder;
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

        $products = $this->apiClient->findProductsModifiedSince($sinceDate);
        if ($products === null || empty($products)) {
            $output->writeln(sprintf('There are no products modified since %s', $sinceDate->format('Y-m-d H:i:s')));
            if ($filepath) {
                $this->writeSinceDateFile($filepath);
            }

            return 0;
        }
        foreach ($products as $product) {
            if ($this->isEntityAlreadyQueuedToImport($product)) {
                continue;
            }
            /** @var QueueItemInterface $queueItem */
            $queueItem = $this->queueItemFactory->createNew();
            Assert::isInstanceOf($queueItem, QueueItemInterface::class);
            $queueItem->setAkeneoEntity(QueueItemInterface::AKENEO_ENTITY_PRODUCT);
            $queueItem->setAkeneoIdentifier($product['identifier']);
            $queueItem->setCreatedAt(new \DateTime());
            $this->queueItemRepository->add($queueItem);
        }

        if ($filepath) {
            $this->writeSinceDateFile($filepath);
        }

        return 0;
    }

    protected function getSinceDateByFile(string $filepath): \DateTime
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

    protected function writeSinceDateFile(string $filepath): void
    {
        file_put_contents($filepath, $this->dateTimeBuilder->build()->format('Y-m-d H:i:s'));
    }

    private function isEntityAlreadyQueuedToImport($product): bool
    {
        $queueItem = $this->queueItemRepository->findOneToImport(
            QueueItemInterface::AKENEO_ENTITY_PRODUCT,
            $product['identifier']
        );
        if ($queueItem) {
            return true;
        }
        return false;
    }
}
