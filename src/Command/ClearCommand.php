<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Command;

use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webgriffe\SyliusAkeneoPlugin\Entity\QueueItem;
use Webgriffe\SyliusAkeneoPlugin\Repository\QueueItemRepositoryInterface;

final class ClearCommand extends Command
{
    public const SUCCESS = 0;

    public const FAILURE = 1;

    public const DEFAULT_DAYS = 10;

    public const DAYS_OPTION_NAME = 'days';

    /** @var QueueItemRepositoryInterface */
    private $queueItemRepository;

    /** @var ManagerRegistry */
    private $managerRegistry;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'webgriffe:akeneo:clear';

    /**
     * ClearCommand constructor.
     */
    public function __construct(QueueItemRepositoryInterface $queueItemRepository, ManagerRegistry $managerRegistry)
    {
        $this->queueItemRepository = $queueItemRepository;
        $this->managerRegistry = $managerRegistry;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Clean the Akeneo\'s queue of items older than N days.')
            ->setHelp('This command allows you to clean the Akeneo\'s queue of item older than a specificed numbers of days.')
            ->addOption(
                self::DAYS_OPTION_NAME,
                'd',
                InputOption::VALUE_REQUIRED,
                'Number of days from which to purge the queue of previous items',
                self::DEFAULT_DAYS
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // get the number of days from user
        $numberOfDays = $input->getOption(self::DAYS_OPTION_NAME);
        if (is_string($numberOfDays)) {
            $numberOfDays = (int) $numberOfDays;
        } else {
            $numberOfDays = self::DEFAULT_DAYS;
        }

        // get the beginning date
        $dateToDelete = $this->getPreviousDateNDays($numberOfDays);

        $queueItems = $this->queueItemRepository->findToDelete($dateToDelete);
        if (!$queueItems) {
            $output->writeln('No items to delete found');

            return self::FAILURE;
        }

        /** @var EntityManagerInterface $objectManager */
        $objectManager = $this->managerRegistry->getManager();

        /** @var QueueItem $queueItem */
        foreach ($queueItems as $queueItem) {
            $objectManager->remove($queueItem);
        }

        // save DB edits
        $objectManager->flush();

        $output->writeln(sprintf('%c items deleted.', count($queueItems)));

        return self::SUCCESS;
    }

    /**
     * @throws \Exception
     */
    private function getPreviousDateNDays(int $numberOfDays): DateTime
    {
        $today = new DateTime('now');

        return $today->sub(new DateInterval(sprintf('P%dD', $numberOfDays)));
    }
}
