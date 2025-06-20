<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

final class ImporterRegistry implements ImporterRegistryInterface
{
    /** @var ImporterInterface[] */
    private array $registry = [];

    #[\Override]
    public function add(ImporterInterface $importer): void
    {
        $this->registry[] = $importer;
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
