<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

final class ReconcilerRegistry implements ReconcilerRegistryInterface
{
    /** @var ReconcilerInterface[] */
    private array $registry = [];

    #[\Override]
    public function add(ReconcilerInterface $reconciliation): void
    {
        $this->registry[] = $reconciliation;
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function all(): array
    {
        return $this->registry;
    }
}
