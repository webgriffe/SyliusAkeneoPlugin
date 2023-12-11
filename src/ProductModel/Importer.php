<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ProductModel;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use DateTime;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Webgriffe\SyliusAkeneoPlugin\Event\IdentifiersModifiedSinceSearchBuilderBuiltEvent;
use Webgriffe\SyliusAkeneoPlugin\Event\ProductWithParentSearchBuilderBuiltEvent;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webgriffe\SyliusAkeneoPlugin\Message\ItemImport;
use Webgriffe\SyliusAkeneoPlugin\Product\Importer as ProductImporter;
use Webmozart\Assert\Assert;

/**
 * @psalm-type AkeneoProductModel = array{
 *     code: string,
 *     family: string,
 *     family_variant: string,
 *     parent: ?string,
 * }
 * @psalm-type AkeneoProduct = array{
 *     identifier: string,
 *     enabled: bool,
 *     family: ?string,
 *     parent: ?string,
 *  }
 */
final class Importer implements ImporterInterface
{
    public const AKENEO_ENTITY = 'ProductModel';

    public function __construct(
        private AkeneoPimClientInterface $akeneoPimClient,
        private EventDispatcherInterface $eventDispatcher,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function getAkeneoEntity(): string
    {
        return self::AKENEO_ENTITY;
    }

    public function getIdentifiersModifiedSince(DateTime $sinceDate): array
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder->addFilter('updated', '>', $sinceDate->format('Y-m-d H:i:s'));
        $this->eventDispatcher->dispatch(
            new IdentifiersModifiedSinceSearchBuilderBuiltEvent($this, $searchBuilder, $sinceDate),
        );
        /** @var AkeneoProductModel[] $productModels */
        $productModels = $this->akeneoPimClient->getProductModelApi()->all(50, ['search' => $searchBuilder->getFilters()]);
        $identifiers = [];
        foreach ($productModels as $productModel) {
            $productModelCode = $productModel['code'];
            Assert::stringNotEmpty($productModelCode);
            $identifiers[] = $productModelCode;
        }

        return $identifiers;
    }

    public function import(string $identifier): void
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder->addFilter('parent', '=', $identifier);
        $this->eventDispatcher->dispatch(
            new ProductWithParentSearchBuilderBuiltEvent($this, $searchBuilder, $identifier),
        );
        /** @var AkeneoProduct[] $products */
        $products = $this->akeneoPimClient->getProductApi()->all(50, ['search' => $searchBuilder->getFilters()]);

        foreach ($products as $product) {
            $this->messageBus->dispatch(new ItemImport(
                ProductImporter::AKENEO_ENTITY,
                $product['identifier'],
            ));
        }
    }
}
