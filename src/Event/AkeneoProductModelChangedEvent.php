<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Event;

/**
 * @psalm-type AkeneoEventProductModel = array{
 *     code: string,
 *     family: string,
 *     family_variant: string,
 *     parent: ?string,
 *     categories: string[],
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
 *         resource: array|AkeneoEventProductModel
 *     },
 * }
 */
final class AkeneoProductModelChangedEvent
{
    private bool $ignorable = false;

    /**
     * @param AkeneoEventProductModel $akeneoProductModel
     * @param AkeneoEvent $akeneoEvent
     */
    public function __construct(
        private array $akeneoProductModel,
        private array $akeneoEvent,
    ) {
    }

    public function getAkeneoProductModel(): array
    {
        return $this->akeneoProductModel;
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
