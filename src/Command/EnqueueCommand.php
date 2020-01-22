<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Command;

use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
use Webgriffe\SyliusAkeneoPlugin\Entity\QueueItemInterface;
use Webgriffe\SyliusAkeneoPlugin\Repository\QueueItemRepositoryInterface;
use Webmozart\Assert\Assert;

final class EnqueueCommand extends Command
{
    protected static $defaultName = 'webgriffe:akeneo:enqueue';

    /** @var ApiClientInterface */
    private $apiClient;

    /** @var QueueItemRepositoryInterface */
    private $queueItemRepository;

    /** @var FactoryInterface */
    private $queueItemFactory;

    public function __construct(
        ApiClientInterface $apiClient,
        QueueItemRepositoryInterface $queueItemRepository,
        FactoryInterface $queueItemFactory
    ) {
        $this->apiClient = $apiClient;
        $this->queueItemRepository = $queueItemRepository;
        $this->queueItemFactory = $queueItemFactory;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('since', 's', InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // todo: since option could not exists
        $since = $input->getOption('since');
        // todo: date time throws exception
        $sinceDate = new \DateTime($since);
        $productModifiedAfterResponse = $this->apiClient->findProductsModifiedAfter($sinceDate);
        if ($productModifiedAfterResponse === null || empty($productModifiedAfterResponse)) {
            $output->writeln(sprintf('There are no products modified after %s', $sinceDate->format('Y-m-d H:i:s')));

            return 0;
        }
        foreach ($productModifiedAfterResponse as $identifier) {
            /** @var QueueItemInterface $queueItem */
            $queueItem = $this->queueItemFactory->createNew();
            Assert::isInstanceOf($queueItem, QueueItemInterface::class);
            $queueItem->setAkeneoEntity(QueueItemInterface::AKENEO_ENTITY_PRODUCT);
            $queueItem->setAkeneoIdentifier($identifier);
            $queueItem->setCreatedAt(new \DateTime());
            $this->queueItemRepository->add($queueItem);
        }

        return 0;
    }
}
