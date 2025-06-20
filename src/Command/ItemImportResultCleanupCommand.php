<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Command;

use DateInterval;
use DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webgriffe\SyliusAkeneoPlugin\DateTimeBuilder;
use Webgriffe\SyliusAkeneoPlugin\Entity\ItemImportResultInterface;
use Webgriffe\SyliusAkeneoPlugin\Respository\ItemImportResultRepositoryInterface;

#[AsCommand(name: 'webgriffe:akeneo:cleanup-item-import-results')]
final class ItemImportResultCleanupCommand extends Command
{
    private const DEFAULT_DAYS = 30;

    private const DAYS_ARGUMENT_NAME = 'days';

    public function __construct(private ItemImportResultRepositoryInterface $itemImportResultRepository)
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setDescription('Clean the Akeneo\'s item import results older than N days.')
            ->setHelp(
                'This command allows you to clean the Akeneo\'s item import results older than a specified numbers ' .
                'of days.',
            )
            ->addArgument(
                self::DAYS_ARGUMENT_NAME,
                InputArgument::OPTIONAL,
                'Number of days from which to purge the item import results',
                (string) (self::DEFAULT_DAYS),
            )
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $numberOfDays = self::DEFAULT_DAYS;
        // get the number of days from user
        $numberOfDaysEntered = $input->getArgument(self::DAYS_ARGUMENT_NAME);
        if ($numberOfDaysEntered !== null) {
            if (!is_string($numberOfDaysEntered) || (int) $numberOfDaysEntered < 0) {
                $output->writeln('Sorry, the number of days entered is not valid!');

                return Command::FAILURE;
            }
            $numberOfDays = (int) $numberOfDaysEntered;
        }

        // get the beginning date
        $dateToDelete = $this->getPreviousDateNDays($numberOfDays);

        $itemImportResults = $this->itemImportResultRepository->findToCleanup($dateToDelete);

        if (count($itemImportResults) === 0) {
            $output->writeln('There are no items to clean');

            return Command::SUCCESS;
        }

        /** @var ItemImportResultInterface $itemImportResult */
        foreach ($itemImportResults as $itemImportResult) {
            $this->itemImportResultRepository->remove($itemImportResult);
        }

        $output->writeln(
            sprintf(
                '<info>%s</info> item import results created before <info>%s</info> has been deleted.',
                count($itemImportResults),
                $dateToDelete->format('Y-m-d H:i:s'),
            ),
        );

        return Command::SUCCESS;
    }

    private function getPreviousDateNDays(int $numberOfDays): DateTime
    {
        $dtBuilder = new DateTimeBuilder();

        return $dtBuilder->build()->sub(new DateInterval(sprintf('P%dD', $numberOfDays)));
    }
}
