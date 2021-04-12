<?php


namespace Webgriffe\SyliusAkeneoPlugin;


interface ReconcilerInterface
{
    /**
     * A string used to identify the Akeneo resource managed by this importer (for example: Product, Category, ecc...)
     */
    public function getAkeneoEntity(): string;

    /**
     * Must return the list of Akeneo identifiers of entities managed by this importer modified after the given
     * date/time.
     *
     * @return string[]
     */
    public function getIdentifiersModifiedSince(\DateTime $sinceDate): array;

    /**
     * Must implement the reconciliation logic for the Akeneo resource managed by this reconciler.
     */
    public function reconcile(array $identifiersToReconcileWith): void;
}
