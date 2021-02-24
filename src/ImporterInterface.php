<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

interface ImporterInterface
{
    public const EVENT_AKENEO_IMPORT = 'akeneo-import';

    /**
     * A string used to identify the Akeneo resource managed by this importer (for example: Product, Category, ecc...)
     */
    public function getAkeneoEntity(): string;

    /**
     * Must implement the import logic for the Akeneo resource managed by this importer.
     */
    public function import(string $identifier): void;

    /**
     * Must return the list of Akeneo identifiers of entities managed by this importer modified after the given
     * date/time.
     *
     * @return string[]
     */
    public function getIdentifiersModifiedSince(\DateTime $sinceDate): array;
}
