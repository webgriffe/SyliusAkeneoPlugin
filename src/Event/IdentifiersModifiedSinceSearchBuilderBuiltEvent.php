<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Event;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use DateTimeInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;

/**
 * @readonly
 */
final class IdentifiersModifiedSinceSearchBuilderBuiltEvent
{
    public function __construct(
        private ImporterInterface $importer,
        private SearchBuilder $searchBuilder,
        private DateTimeInterface $sinceDate,
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

    public function getSinceDate(): DateTimeInterface
    {
        return $this->sinceDate;
    }
}
