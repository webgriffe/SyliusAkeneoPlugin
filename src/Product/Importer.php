<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Product;

if (!interface_exists(\Sylius\Resource\Factory\FactoryInterface::class)) {
    class_alias(\Sylius\Resource\Factory\FactoryInterface::class, \Sylius\Component\Resource\Factory\FactoryInterface::class);
}
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
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webgriffe\SyliusAkeneoPlugin\Event\IdentifiersModifiedSinceSearchBuilderBuiltEvent;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webgriffe\SyliusAkeneoPlugin\ReconcilerInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlersResolverInterface;
use Webmozart\Assert\Assert;

final class Importer implements ImporterInterface, ReconcilerInterface
{
    public const AKENEO_ENTITY = 'Product';

    /**
     * @param FactoryInterface<ProductTaxonInterface> $productTaxonFactory
     */
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
        private ValidatorInterface $validator,
    ) {
    }

    #[\Override]
    public function getAkeneoEntity(): string
    {
        return self::AKENEO_ENTITY;
    }

    #[\Override]
    public function import(string $identifier): void
    {
        $productVariantResponse = $this->apiClient->getProductApi()->get($identifier);

        $product = $this->getOrCreateProductFromVariantResponse($productVariantResponse);

        if ($identifier !== $product->getCode()) {
            $this->disableOldParentProductIfItHasNotAnyVariants($identifier, $product);
        }

        $this->handleChannels($product, $productVariantResponse);

        $this->handleTaxons($product, $productVariantResponse);

        /** @var ProductVariantInterface|null $productVariant */
        $productVariant = $this->productVariantRepository->findOneBy(['code' => $identifier]);
        if (!$productVariant instanceof ProductVariantInterface) {
            /** @var ProductVariantInterface $productVariant */
            $productVariant = $this->productVariantFactory->createNew();
            $productVariant->setCode($identifier);
            $productVariant->setPosition(0);
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

        $this->validator->validate($productVariant);

        $productEventName = $product->getId() ? 'update' : 'create';
        $productVariantEventName = $productVariant->getId() ? 'update' : 'create';
        $this->dispatchPreEvent($productVariant, 'product_variant', $productVariantEventName);
        $this->dispatchPreEvent($product, 'product', $productEventName);
        // TODO We should handle $event->isStopped() where $event is the return value of the dispatchPreEvent method.
        //      See \Sylius\Bundle\ResourceBundle\Controller\ResourceController.
        $this->productRepository->add($product);
        $this->dispatchPostEvent($productVariant, 'product_variant', $productVariantEventName);
        $this->dispatchPostEvent($product, 'product', $productEventName);
    }

    /** @psalm-return array<array-key, string> */
    #[\Override]
    public function getIdentifiersModifiedSince(DateTime $sinceDate): array
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder->addFilter('updated', '>', $sinceDate->format('Y-m-d H:i:s'));
        $this->eventDispatcher->dispatch(
            new IdentifiersModifiedSinceSearchBuilderBuiltEvent($this, $searchBuilder, $sinceDate),
        );
        $products = $this->apiClient->getProductApi()->all(50, ['search' => $searchBuilder->getFilters()]);
        $identifiers = [];
        foreach ($products as $product) {
            Assert::isArray($product);
            Assert::keyExists($product, 'identifier');
            $productIdentifier = (string) $product['identifier'];
            Assert::stringNotEmpty($productIdentifier);
            $identifiers[] = $productIdentifier;
        }

        return $identifiers;
    }

    /** @psalm-return array<array-key, string> */
    #[\Override]
    public function getAllIdentifiers(): array
    {
        return $this->getIdentifiersModifiedSince((new DateTime())->setTimestamp(0));
    }

    private function getOrCreateProductFromVariantResponse(array $productVariantResponse): ProductInterface
    {
        $identifier = $productVariantResponse['identifier'];
        Assert::string($identifier);
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

    private function dispatchPreEvent(
        ProductInterface|ProductVariantInterface $productOrVariant,
        string $resourceName,
        string $eventName,
    ): ResourceControllerEvent {
        $event = new ResourceControllerEvent($productOrVariant);
        $event->setArgument(self::EVENT_AKENEO_IMPORT, true);
        $this->eventDispatcher->dispatch($event, sprintf('sylius.%s.pre_%s', $resourceName, $eventName));

        return $event;
    }

    private function dispatchPostEvent(
        ProductInterface|ProductVariantInterface $productOrVariant,
        string $resourceName,
        string $eventName,
    ): ResourceControllerEvent {
        $event = new ResourceControllerEvent($productOrVariant);
        $event->setArgument(self::EVENT_AKENEO_IMPORT, true);
        /** @psalm-suppress InvalidArgument */
        $this->eventDispatcher->dispatch($event, sprintf('sylius.%s.post_%s', $resourceName, $eventName));

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

    #[\Override]
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

            $this->dispatchPreEvent($productVariantToDisable, 'product_variant', 'update');
            $this->dispatchPreEvent($product, 'product', 'update');
            // TODO We should handle $event->isStopped() where $event is the return value of the dispatchPreEvent method.
            //      See \Sylius\Bundle\ResourceBundle\Controller\ResourceController.
            $this->productRepository->add($product);
            $this->dispatchPostEvent($productVariantToDisable, 'product_variant', 'update');
            $this->dispatchPostEvent($product, 'product', 'update');
        }
    }

    private function disableOldParentProductIfItHasNotAnyVariants(string $identifier, ProductInterface $product): void
    {
        $oldParentProduct = $this->productRepository->findOneByCode($identifier);
        if ($oldParentProduct === null) {
            return;
        }
        if ($oldParentProduct === $product) {
            return;
        }
        if ($oldParentProduct->getVariants()->count() !== 1) {
            return;
        }
        $productVariant = $oldParentProduct->getVariants()->first();
        if (!$productVariant instanceof ProductVariantInterface) {
            return;
        }
        if ($productVariant->getCode() !== $identifier) {
            return;
        }

        $oldParentProduct->setEnabled(false);
    }
}
