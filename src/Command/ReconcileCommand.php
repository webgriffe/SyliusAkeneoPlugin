<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webgriffe\SyliusAkeneoPlugin\ReconcilerInterface;
use Webgriffe\SyliusAkeneoPlugin\ReconcilerRegistryInterface;
use Webmozart\Assert\Assert;

/**
 * @psalm-suppress PropertyNotSetInConstructor $lock
 */
final class ReconcileCommand extends Command
{
    use LockableTrait;

    public const SUCCESS = 0;

    public const FAILURE = 1;

    private const RECONCILER_OPTION_NAME = 'reconciler';

    protected static $defaultName = 'webgriffe:akeneo:reconcile';

    /**
     * ReconcileCommand constructor.
     */
    public function __construct(private ReconcilerRegistryInterface $reconciliationRegistry)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Reconciles Akeneo entities on Sylius.')
            ->addOption(
                self::RECONCILER_OPTION_NAME,
                'i',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Reconcile items only for specified reconcilers',
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
            $akeneoItemsToReconcile = $reconciler->getAllIdentifiers();
            $reconciler->reconcile($akeneoItemsToReconcile);
        }

        $this->release();

        return self::SUCCESS;
    }

    /**
     * @return ReconcilerInterface[]
     */
    private function getReconcilers(InputInterface $input): array
    {
        $allReconcilers = $this->reconciliationRegistry->all();
        if (count($allReconcilers) === 0) {
            return [];
        }
        $reconcilersCodes = array_map(
            static fn (ReconcilerInterface $reconciler): string => $reconciler->getAkeneoEntity(),
            $allReconcilers,
        );

        $reconcilersToUse = $input->getOption(self::RECONCILER_OPTION_NAME);
        Assert::isArray($reconcilersToUse);
        Assert::allString($reconcilersToUse);

        if (count($reconcilersToUse) === 0) {
            return $allReconcilers;
        }

        /** @var ReconcilerInterface[]|array<string, ReconcilerInterface>|false $allReconcilers */
        $allReconcilers = array_combine($reconcilersCodes, $allReconcilers);
        Assert::notFalse($allReconcilers);

        /** @var ReconcilerInterface[] $reconcilers */
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
