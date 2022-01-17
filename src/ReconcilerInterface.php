<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

interface ReconcilerInterface
{
    /**
     * A string used to identify the Akeneo resource managed by this reconciler (for example: Product, Category, ecc...)
     */
    public function getAkeneoEntity(): string;

    /**
     * Must return the list of Akeneo identifiers of all entities managed by this reconciler.
     *
     * @return string[]
     */
    public function getAllIdentifiers(): array;

    /**
     * Must implement the reconciliation logic for the Akeneo resource managed by this reconciler.
     */
    public function reconcile(array $identifiersToReconcileWith): void;
}
