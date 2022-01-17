<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

final class ReconcilerRegistry implements ReconcilerRegistryInterface
{
    /** @var ReconcilerInterface[] */
    private $registry = [];

    public function add(ReconcilerInterface $reconciliation): void
    {
        $this->registry[] = $reconciliation;
    }

    /**
     * @inheritdoc
     */
    public function all(): array
    {
        return $this->registry;
    }
}
