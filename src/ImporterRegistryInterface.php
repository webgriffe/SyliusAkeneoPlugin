<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

interface ImporterRegistryInterface
{
    public function add(ImporterInterface $importer): void;

    /** @return ImporterInterface[] */
    public function all(): array;
}
