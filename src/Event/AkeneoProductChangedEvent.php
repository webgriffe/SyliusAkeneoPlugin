<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Event;

/**
 * @psalm-type AkeneoEventProduct = array{
 *     uuid: string,
 *     identifier: string,
 *     enabled: bool,
 *     family: ?string,
 *     categories: string[],
 *     groups: string[],
 *     parent: ?string,
 *     values: array<string, array>,
 *     created: string,
 *     updated: string,
 *     associations: array<string, array>,
 *     quantified_associations: array<string, array>,
 * }
 * @psalm-type AkeneoEvent = array{
 *     action: string,
 *     event_id: string,
 *     event_datetime: string,
 *     author: string,
 *     author_type: string,
 *     pim_source: string,
 *     data: array{
 *         resource: AkeneoEventProduct|array
 *     },
 * }
 */
final class AkeneoProductChangedEvent
{
    private bool $ignorable = false;

    /**
     * @param AkeneoEventProduct $akeneoProduct
     * @param AkeneoEvent $akeneoEvent
     */
    public function __construct(
        private array $akeneoProduct,
        private array $akeneoEvent,
    ) {
    }

    public function getAkeneoProduct(): array
    {
        return $this->akeneoProduct;
    }

    public function getAkeneoEvent(): array
    {
        return $this->akeneoEvent;
    }

    public function isIgnorable(): bool
    {
        return $this->ignorable;
    }

    public function setIgnorable(bool $ignorable): void
    {
        $this->ignorable = $ignorable;
    }
}
