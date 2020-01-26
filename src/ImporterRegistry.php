<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;


class ImporterRegistry implements ImporterRegistryInterface
{
    /** @var ImporterInterface[] */
    private $registry = [];

    public function add(ImporterInterface $importer): void
    {
        $this->registry[] = $importer;
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        return $this->registry;
    }
}
