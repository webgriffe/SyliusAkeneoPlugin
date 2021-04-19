<?php


namespace Webgriffe\SyliusAkeneoPlugin\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webgriffe\SyliusAkeneoPlugin\ReconcilerRegistryInterface;
use Webgriffe\SyliusAkeneoPlugin\ReconcilerInterface;
use Webmozart\Assert\Assert;

/**
 * @psalm-suppress PropertyNotSetInConstructor $lock
 */
final class ReconciliateCommand extends Command
{
    use LockableTrait;

    public const SUCCESS = 0;

    public const FAILURE = 1;

    private const IMPORTER_OPTION_NAME = 'importer';

    protected static $defaultName = 'webgriffe:akeneo:reconciliate';

    /**
     * @var ReconcilerRegistryInterface
     */
    private $reconciliationRegistry;

    /**
     * ReconciliateCommand constructor.
     * @param ReconcilerRegistryInterface $reconciliationRegistry
     */
    public function __construct(ReconcilerRegistryInterface $reconciliationRegistry)
    {
        parent::__construct();
        $this->reconciliationRegistry = $reconciliationRegistry;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Replicates the status of Akeneo products on Sylius.')
            ->setHelp('This command allows you to reconciliate the Akeneo\'s products status with the current on Sylius.')
            ->addOption(
                self::IMPORTER_OPTION_NAME,
                'i',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Reconciliate items only for specified importers'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');

            return self::SUCCESS;
        }

        foreach ($this->getReconcilers($input) as $reconciler) {
            $sinceDate = (new \DateTime())->setTimestamp(0);
            $akeneoItemsToReconcile = $reconciler->getIdentifiersModifiedSince($sinceDate);
            if (count($akeneoItemsToReconcile) === 0) {
                $output->writeln(
                    sprintf(
                        'There are no <info>%s</info> entities since <info>%s</info>',
                        $reconciler->getAkeneoEntity(),
                        $sinceDate->format('Y-m-d H:i:s')
                    )
                );

                continue;
            }

            $reconciler->reconcile($akeneoItemsToReconcile);
        }

        return self::SUCCESS;
    }

    /**
     * @param InputInterface $input
     * @return ReconcilerInterface[]
     */
    private function getReconcilers(InputInterface $input): array
    {
        $allReconcilers = $this->reconciliationRegistry->all();
        if (count($allReconcilers) === 0) {
            throw new \RuntimeException('There are no reconcilers in registry.');
        }
        $reconcilersCodes = array_map(
            static function (ReconcilerInterface $reconciler) {
                return $reconciler->getAkeneoEntity();
            },
            $allReconcilers
        );

        $reconcilersToUse = $input->getOption(self::IMPORTER_OPTION_NAME);
        Assert::isArray($reconcilersToUse);

        if (count($reconcilersToUse) === 0) {
            return $allReconcilers;
        }

        $allReconcilers = array_combine($reconcilersCodes, $allReconcilers);
        Assert::isArray($allReconcilers);

        $reconcilers = [];
        foreach ($reconcilersToUse as $reconcilerToUse) {
            if (!array_key_exists($reconcilerToUse, $allReconcilers)) {
                throw new \InvalidArgumentException(sprintf('Reconciler "%s" does not exists.', $reconcilerToUse));
            }
            $reconcilers[] = $allReconcilers[$reconcilerToUse];
        }

        return $reconcilers;
    }
}
