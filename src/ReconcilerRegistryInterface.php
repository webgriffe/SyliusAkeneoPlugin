<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

interface ReconcilerRegistryInterface
{
    public function add(ReconcilerInterface $reconciliation): void;

    /** @return ReconcilerInterface[] */
    public function all(): array;
}
