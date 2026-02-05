<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webgriffe\SyliusAkeneoPlugin\Event\IdentifiersModifiedSinceSearchBuilderBuiltEvent;
use Webgriffe\SyliusAkeneoPlugin\Product\Importer as ProductImporter;
use Webgriffe\SyliusAkeneoPlugin\ProductAssociations\Importer as ProductAssociationsImporter;

final class IdentifiersModifiedSinceSearchBuilderBuiltEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            IdentifiersModifiedSinceSearchBuilderBuiltEvent::class => 'onIdentifiersModifiedSinceSearchBuilderBuilt',
        ];
    }

    public function onIdentifiersModifiedSinceSearchBuilderBuilt(IdentifiersModifiedSinceSearchBuilderBuiltEvent $event): void
    {
        if (!$event->getImporter() instanceof ProductImporter &&
            !$event->getImporter() instanceof ProductAssociationsImporter) {
            return;
        }

        $searchBuilder = $event->getSearchBuilder();
        $searchBuilder->addFilter('price', 'NOT EMPTY');
    }
}
