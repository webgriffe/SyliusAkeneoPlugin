<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Product;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTaxonInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webgriffe\SyliusAkeneoPlugin\ReconcilerInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlersResolverInterface;
use Webmozart\Assert\Assert;

final class Importer implements ImporterInterface, ReconcilerInterface
{
    private const AKENEO_ENTITY = 'Product';

    public function __construct(
        private ProductVariantFactoryInterface $productVariantFactory,
        private ProductVariantRepositoryInterface $productVariantRepository,
        private ProductRepositoryInterface $productRepository,
        private AkeneoPimClientInterface $apiClient,
        private ValueHandlersResolverInterface $valueHandlersResolver,
        private ProductFactoryInterface $productFactory,
        private TaxonsResolverInterface $taxonsResolver,
        private ProductOptionsResolverInterface $productOptionsResolver,
        private EventDispatcherInterface $eventDispatcher,
        private ChannelsResolverInterface $channelsResolver,
        private StatusResolverInterface $statusResolver,
        private FactoryInterface $productTaxonFactory,
        private StatusResolverInterface $variantStatusResolver,
    ) {
    }

    public function getAkeneoEntity(): string
    {
        return self::AKENEO_ENTITY;
    }

    public function import(string $identifier): void
    {
        $productVariantResponse = $this->apiClient->getProductApi()->get($identifier);

        $product = $this->getOrCreateProductFromVariantResponse($productVariantResponse);

        $this->handleChannels($product, $productVariantResponse);

        $this->handleTaxons($product, $productVariantResponse);

        /** @var ProductVariantInterface|null $productVariant */
        $productVariant = $this->productVariantRepository->findOneBy(['code' => $identifier]);
        if (!$productVariant instanceof ProductVariantInterface) {
            /** @var ProductVariantInterface $productVariant */
            $productVariant = $this->productVariantFactory->createNew();
            $productVariant->setCode($identifier);
        }
        $product->addVariant($productVariant);
        $productVariant->setProduct($product);

        $product->setEnabled($this->statusResolver->resolve($productVariantResponse));
        $productVariant->setEnabled($this->variantStatusResolver->resolve($productVariantResponse));

        /** @var array<string, array> $attributesValues */
        $attributesValues = $productVariantResponse['values'];
        /** @var string|null $familyCode */
        $familyCode = $productVariantResponse['family'];
        if ($familyCode !== null) {
            $attributesValues = $this->addMissingAttributes($attributesValues, $familyCode);
        }
        foreach ($attributesValues as $attribute => $value) {
            $valueHandlers = $this->valueHandlersResolver->resolve($productVariant, $attribute, $value);
            foreach ($valueHandlers as $valueHandler) {
                $valueHandler->handle($productVariant, $attribute, $value);
            }
        }

        $eventName = $product->getId() ? 'update' : 'create';
        $this->dispatchPreEvent($product, $eventName);
        // TODO We should handle $event->isStopped() where $event is the return value of the dispatchPreEvent method.
        //      See \Sylius\Bundle\ResourceBundle\Controller\ResourceController.
        $this->productRepository->add($product);
        $this->dispatchPostEvent($product, $eventName);
    }

    /** @psalm-return array<array-key, string> */
    public function getIdentifiersModifiedSince(DateTime $sinceDate): array
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder->addFilter('updated_at', '>', $sinceDate->format('Y-m-d H:i:s'));
        $products = $this->apiClient->getProductApi()->all(50, ['search' => $searchBuilder->getFilters()]);
        $identifiers = [];
        foreach ($products as $product) {
            Assert::keyExists($product, 'identifier');
            $productIdentifier = (string) $product['identifier'];
            Assert::stringNotEmpty($productIdentifier);
            $identifiers[] = $productIdentifier;
        }

        return $identifiers;
    }

    /** @psalm-return array<array-key, string> */
    public function getAllIdentifiers(): array
    {
        return $this->getIdentifiersModifiedSince((new DateTime())->setTimestamp(0));
    }

    private function getOrCreateProductFromVariantResponse(array $productVariantResponse): ProductInterface
    {
        $identifier = $productVariantResponse['identifier'];
        $parentCode = $productVariantResponse['parent'];
        if ($parentCode !== null) {
            $product = $this->productRepository->findOneByCode($parentCode);
            if ($product === null) {
                $product = $this->createNewProductFromAkeneoProduct($productVariantResponse);
            }

            return $product;
        }

        $product = $this->productRepository->findOneByCode($identifier);
        if ($product === null) {
            $product = $this->productFactory->createNew();
        }
        Assert::isInstanceOf($product, ProductInterface::class);
        $product->setCode($identifier);

        return $product;
    }

    private function dispatchPreEvent(ResourceInterface $product, string $eventName): ResourceControllerEvent
    {
        $event = new ResourceControllerEvent($product);
        $event->setArgument(self::EVENT_AKENEO_IMPORT, true);
        $this->eventDispatcher->dispatch($event, sprintf('sylius.product.pre_%s', $eventName));

        return $event;
    }

    private function dispatchPostEvent(ResourceInterface $product, string $eventName): ResourceControllerEvent
    {
        $event = new ResourceControllerEvent($product);
        $event->setArgument(self::EVENT_AKENEO_IMPORT, true);
        /** @psalm-suppress InvalidArgument */
        $this->eventDispatcher->dispatch($event, sprintf('sylius.product.post_%s', $eventName));

        return $event;
    }

    private function handleChannels(ProductInterface $product, array $productVariantResponse): void
    {
        foreach ($product->getChannels() as $channel) {
            $product->removeChannel($channel);
        }
        $channels = $this->channelsResolver->resolve($productVariantResponse);
        foreach ($channels as $channel) {
            if (!$product->hasChannel($channel)) {
                $product->addChannel($channel);
            }
        }
    }

    private function handleTaxons(ProductInterface $product, array $akeneoProduct): void
    {
        $akeneoTaxons = new ArrayCollection($this->taxonsResolver->resolve($akeneoProduct));
        $syliusTaxons = $product->getTaxons();
        $akeneoTaxonsCodes = $akeneoTaxons->map(fn (TaxonInterface $taxon): ?string => $taxon->getCode())->toArray();
        $syliusTaxonsCodes = $syliusTaxons->map(fn (TaxonInterface $taxon): ?string => $taxon->getCode())->toArray();
        $toAddTaxons = $akeneoTaxons->filter(
            fn (TaxonInterface $taxon): bool => !in_array($taxon->getCode(), $syliusTaxonsCodes, true),
        );
        $toRemoveTaxonsCodes = array_diff($syliusTaxonsCodes, $akeneoTaxonsCodes);

        foreach ($product->getProductTaxons() as $productTaxon) {
            $taxon = $productTaxon->getTaxon();
            Assert::isInstanceOf($taxon, TaxonInterface::class);
            if (in_array($taxon->getCode(), $toRemoveTaxonsCodes, true)) {
                $product->getProductTaxons()->removeElement($productTaxon);
            }
        }
        foreach ($toAddTaxons as $toAddTaxon) {
            /** @var ProductTaxonInterface $productTaxon */
            $productTaxon = $this->productTaxonFactory->createNew();
            $productTaxon->setProduct($product);
            $productTaxon->setTaxon($toAddTaxon);
            $productTaxon->setPosition(0);
            $product->addProductTaxon($productTaxon);
        }
    }

    private function createNewProductFromAkeneoProduct(array $productVariantResponse): ProductInterface
    {
        $parentCode = $productVariantResponse['parent'];
        $product = $this->productFactory->createNew();
        Assert::isInstanceOf($product, ProductInterface::class);
        $product->setCode($parentCode);
        foreach ($this->productOptionsResolver->resolve($productVariantResponse) as $productOption) {
            $product->addOption($productOption);
        }

        return $product;
    }

    /**
     * @param array<string, array> $attributesValues
     *
     * @return array<string, array>
     */
    private function addMissingAttributes(array $attributesValues, string $familyCode): array
    {
        $family = $this->apiClient->getFamilyApi()->get($familyCode);

        /** @var string[] $allFamilyAttributes */
        $allFamilyAttributes = $family['attributes'] ?? [];
        /** @var string[] $productAttributes */
        $productAttributes = array_keys($attributesValues);
        $missingAttributes = array_diff($allFamilyAttributes, $productAttributes);
        $emptyAttributeValue = [
            ['locale' => null, 'scope' => null, 'data' => null],
        ];
        foreach ($missingAttributes as $missingAttribute) {
            $attributesValues[$missingAttribute] = $emptyAttributeValue;
        }

        return $attributesValues;
    }

    public function reconcile(array $identifiersToReconcileWith): void
    {
        /** @var ProductVariantInterface[] $productVariantsToReconcile */
        $productVariantsToReconcile = $this->productVariantRepository->findAll();
        /** @var string[] $identifiersToReconcile */
        $identifiersToReconcile = array_map(static fn ($productVariant): ?string => $productVariant->getCode(), $productVariantsToReconcile);

        $identifiersToDisable = array_diff($identifiersToReconcile, $identifiersToReconcileWith);

        foreach ($identifiersToDisable as $productVariantIdentifierToDisable) {
            /** @var ?ProductVariantInterface $productVariantToDisable */
            $productVariantToDisable = $this->productVariantRepository->findOneBy(['code' => $productVariantIdentifierToDisable]);
            if ($productVariantToDisable === null || !$productVariantToDisable->isEnabled()) {
                continue;
            }
            $productVariantToDisable->setEnabled(false);

            $this->productVariantRepository->add($productVariantToDisable);

            /** @var ?ProductInterface $product */
            $product = $productVariantToDisable->getProduct();
            if ($product === null || !$product->isEnabled() || count($product->getEnabledVariants()) > 0) {
                continue;
            }

            $product->setEnabled(false);

            $this->dispatchPreEvent($product, 'update');
            // TODO We should handle $event->isStopped() where $event is the return value of the dispatchPreEvent method.
            //      See \Sylius\Bundle\ResourceBundle\Controller\ResourceController.
            $this->productRepository->add($product);
            $this->dispatchPostEvent($product, 'update');
        }
    }
}
