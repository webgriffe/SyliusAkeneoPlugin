<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Product;

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
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
use Webgriffe\SyliusAkeneoPlugin\FamilyAwareApiClientInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webgriffe\SyliusAkeneoPlugin\ReconcilerInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlersResolverInterface;
use Webmozart\Assert\Assert;

final class Importer implements ImporterInterface, ReconcilerInterface
{
    private const AKENEO_ENTITY = 'Product';

    /** @var ProductVariantFactoryInterface */
    private $productVariantFactory;

    /** @var ProductVariantRepositoryInterface */
    private $productVariantRepository;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var ApiClientInterface */
    private $apiClient;

    /** @var ValueHandlersResolverInterface */
    private $valueHandlersResolver;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var ProductFactoryInterface */
    private $productFactory;

    /** @var TaxonsResolverInterface */
    private $taxonsResolver;

    /** @var ProductOptionsResolverInterface */
    private $productOptionsResolver;

    /** @var ChannelsResolverInterface */
    private $channelsResolver;

    /** @var StatusResolverInterface */
    private $statusResolver;

    /** @var FactoryInterface */
    private $productTaxonFactory;

    /** @var StatusResolverInterface */
    private $variantStatusResolver;

    public function __construct(
        ProductVariantFactoryInterface $productVariantFactory,
        ProductVariantRepositoryInterface $productVariantRepository,
        ProductRepositoryInterface $productRepository,
        ApiClientInterface $apiClient,
        ValueHandlersResolverInterface $valueHandlerResolver,
        ProductFactoryInterface $productFactory,
        TaxonsResolverInterface $taxonsResolver,
        ProductOptionsResolverInterface $productOptionsResolver,
        EventDispatcherInterface $eventDispatcher,
        ChannelsResolverInterface $channelsResolver,
        StatusResolverInterface $statusResolver,
        FactoryInterface $productTaxonFactory,
        StatusResolverInterface $variantStatusResolver = null
    ) {
        $this->productVariantFactory = $productVariantFactory;
        $this->productVariantRepository = $productVariantRepository;
        $this->productRepository = $productRepository;
        $this->apiClient = $apiClient;
        $this->valueHandlersResolver = $valueHandlerResolver;
        $this->productFactory = $productFactory;
        $this->taxonsResolver = $taxonsResolver;
        $this->productOptionsResolver = $productOptionsResolver;
        $this->eventDispatcher = $eventDispatcher;
        $this->channelsResolver = $channelsResolver;
        $this->statusResolver = $statusResolver;
        $this->productTaxonFactory = $productTaxonFactory;
        if (null === $variantStatusResolver) {
            trigger_deprecation(
                'webgriffe/sylius-akeneo-plugin',
                '1.2',
                'Not passing a variant status resolver to "%s" is deprecated and will be removed in %s.',
                __CLASS__,
                '2.0'
            );
            $variantStatusResolver = new VariantStatusResolver();
        }
        $this->variantStatusResolver = $variantStatusResolver;
    }

    /**
     * @inheritdoc
     */
    public function getAkeneoEntity(): string
    {
        return self::AKENEO_ENTITY;
    }

    public function import(string $identifier): void
    {
        $productVariantResponse = $this->apiClient->findProduct($identifier);
        if ($productVariantResponse === null) {
            throw new \RuntimeException(sprintf('Cannot find product "%s" on Akeneo.', $identifier));
        }

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

    /**
     * @inheritdoc
     * @psalm-return array<array-key, string>
     */
    public function getIdentifiersModifiedSince(\DateTime $sinceDate): array
    {
        $products = $this->apiClient->findProductsModifiedSince($sinceDate);
        $identifiers = [];
        foreach ($products as $product) {
            Assert::string($product['identifier']);
            $identifiers[] = $product['identifier'];
        }

        return $identifiers;
    }

    /**
     * @inheritdoc
     * @psalm-return array<array-key, string>
     */
    public function getAllIdentifiers(): array
    {
        return $this->getIdentifiersModifiedSince((new \DateTime())->setTimestamp(0));
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

    private function dispatchPreEvent(ResourceInterface $product, string $resourceName, string $eventName): ResourceControllerEvent
    {
        $event = new ResourceControllerEvent($product);
        $event->setArgument(self::EVENT_AKENEO_IMPORT, true);
        $this->eventDispatcher->dispatch($event, sprintf('sylius.%s.pre_%s', $resourceName, $eventName));

        return $event;
    }

    private function dispatchPostEvent(ResourceInterface $product, string $resourceName, string $eventName): ResourceControllerEvent
    {
        $event = new ResourceControllerEvent($product);
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
        $akeneoTaxonsCodes = $akeneoTaxons->map(function (TaxonInterface $taxon): ?string {return $taxon->getCode(); })->toArray();
        $syliusTaxonsCodes = $syliusTaxons->map(function (TaxonInterface $taxon): ?string {return $taxon->getCode(); })->toArray();
        $toAddTaxons = $akeneoTaxons->filter(
            function (TaxonInterface $taxon) use ($syliusTaxonsCodes): bool {
                return !in_array($taxon->getCode(), $syliusTaxonsCodes, true);
            }
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
        if (!$this->apiClient instanceof FamilyAwareApiClientInterface) {
            return $attributesValues;
        }

        $family = $this->apiClient->findFamily($familyCode);

        if (null === $family) {
            throw new \RuntimeException(sprintf('Cannot find "%s" family on Akeneo.', $familyCode));
        }

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
        $identifiersToReconcile = array_map(static function ($productVariant): ?string {
            return $productVariant->getCode();
        }, $productVariantsToReconcile);

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

            $this->dispatchPreEvent($productVariantIdentifierToDisable, 'product_variant', 'update');
            $this->dispatchPreEvent($product, 'product', 'update');
            // TODO We should handle $event->isStopped() where $event is the return value of the dispatchPreEvent method.
            //      See \Sylius\Bundle\ResourceBundle\Controller\ResourceController.
            $this->productRepository->add($product);
            $this->dispatchPostEvent($productVariantIdentifierToDisable, 'product_variant', 'update');
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
