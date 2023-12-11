<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Event;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;

final class ProductWithParentSearchBuilderBuiltEvent
{
    public function __construct(
        private ImporterInterface $importer,
        private SearchBuilder $searchBuilder,
        private string $productModelCode,
    ) {
    }

    public function getImporter(): ImporterInterface
    {
        return $this->importer;
    }

    public function getSearchBuilder(): SearchBuilder
    {
        return $this->searchBuilder;
    }

    public function getProductModelCode(): string
    {
        return $this->productModelCode;
    }
}
