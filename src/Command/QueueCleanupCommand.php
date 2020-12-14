<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Command;

use DateInterval;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webgriffe\SyliusAkeneoPlugin\DateTimeBuilder;
use Webgriffe\SyliusAkeneoPlugin\Entity\QueueItem;
use Webgriffe\SyliusAkeneoPlugin\Repository\QueueItemRepositoryInterface;

final class QueueCleanupCommand extends Command
{
    private const SUCCESS = 0;

    private const FAILURE = 1;

    private const DEFAULT_DAYS = 10;

    private const DAYS_ARGUMENT_NAME = 'days';

    /** @var QueueItemRepositoryInterface */
    private $queueItemRepository;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'webgriffe:akeneo:cleanup-queue';

    /**
     * QueueCleanupCommand constructor.
     */
    public function __construct(QueueItemRepositoryInterface $queueItemRepository)
    {
        $this->queueItemRepository = $queueItemRepository;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Clean the Akeneo\'s queue of items older than N days.')
            ->setHelp('This command allows you to clean the Akeneo\'s queue of item older than a specificed numbers of days.')
            ->addArgument(
                self::DAYS_ARGUMENT_NAME,
                InputArgument::OPTIONAL,
                'Number of days from which to purge the queue of previous items',
                (string) (self::DEFAULT_DAYS)
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $numberOfDays = self::DEFAULT_DAYS;
        // get the number of days from user
        $numberOfDaysEntered = $input->getArgument(self::DAYS_ARGUMENT_NAME);
        if($numberOfDaysEntered) {
            if (!is_string($numberOfDaysEntered) || (int) $numberOfDaysEntered < 0) {
                $output->writeln('Sorry, the number of days entered is not valid!');
                return self::FAILURE;
            }
            $numberOfDays = (int)$numberOfDaysEntered;
        }

        // get the beginning date
        $dateToDelete = $this->getPreviousDateNDays($numberOfDays);

        $queueItems = $this->queueItemRepository->findToCleanup($dateToDelete);

        if(count($queueItems) === 0) {
            $output->writeln('There are no items to clean');
            return self::SUCCESS;
        }

        /** @var QueueItem $queueItem */
        foreach ($queueItems as $queueItem) {
            $this->queueItemRepository->remove($queueItem);
        }

        $output->writeln(sprintf('<info>%s</info> items imported before <info>%s</info> has been deleted.', count($queueItems), $dateToDelete->format("Y-m-d H:i:s")));

        return self::SUCCESS;
    }

    /**
     * @throws \Exception
     */
    private function getPreviousDateNDays(int $numberOfDays): DateTime
    {
        $dtBuilder = new DateTimeBuilder();
        $today = $dtBuilder->build();

        return $today->sub(new DateInterval(sprintf('P%dD', $numberOfDays)));
    }
}
