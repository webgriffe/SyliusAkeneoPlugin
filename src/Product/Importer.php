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
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlersResolverInterface;
use Webmozart\Assert\Assert;

final class Importer implements ImporterInterface
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
     * {@inheritdoc}
     */
    public function getAkeneoEntity(): string
    {
        return self::AKENEO_ENTITY;
    }

    public function import(string $identifier): void
    {
        $productVariantResponse = $this->apiClient->findProduct($identifier);
        if (!$productVariantResponse) {
            throw new \RuntimeException(sprintf('Cannot find product "%s" on Akeneo.', $identifier));
        }

        $product = $this->getOrCreateProductFromVariantResponse($productVariantResponse);

        $this->handleChannels($product, $productVariantResponse);

        $this->handleTaxons($product, $productVariantResponse);

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

        foreach ($productVariantResponse['values'] as $attribute => $value) {
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

    /**
     * {@inheritdoc}
     */
    public function getIdentifiersModifiedSince(\DateTime $sinceDate): array
    {
        $products = $this->apiClient->findProductsModifiedSince($sinceDate);
        $identifiers = [];
        foreach ($products as $product) {
            $identifiers[] = $product['identifier'];
        }

        return $identifiers;
    }

    private function getOrCreateProductFromVariantResponse(array $productVariantResponse): ProductInterface
    {
        $identifier = $productVariantResponse['identifier'];
        $parentCode = $productVariantResponse['parent'];
        if ($parentCode !== null) {
            $product = $this->productRepository->findOneByCode($parentCode);
            if (!$product) {
                $product = $this->createNewProductFromAkeneoProduct($productVariantResponse);
            }

            return $product;
        }

        $product = $this->productRepository->findOneByCode($identifier);
        if (!$product) {
            $product = $this->productFactory->createNew();
        }
        Assert::isInstanceOf($product, ProductInterface::class);
        /** @var ProductInterface $product */
        $product->setCode($identifier);

        return $product;
    }

    private function dispatchPreEvent(ResourceInterface $product, string $eventName): ResourceControllerEvent
    {
        $event = new ResourceControllerEvent($product);
        $event->setArgument(self::EVENT_AKENEO_IMPORT, true);
        $this->eventDispatcher->dispatch(sprintf('sylius.product.pre_%s', $eventName), $event);

        return $event;
    }

    private function dispatchPostEvent(ResourceInterface $product, string $eventName): ResourceControllerEvent
    {
        $event = new ResourceControllerEvent($product);
        $event->setArgument(self::EVENT_AKENEO_IMPORT, true);
        $this->eventDispatcher->dispatch(sprintf('sylius.product.post_%s', $eventName), $event);

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
        $akeneoTaxonsCodes = $akeneoTaxons->map(function (TaxonInterface $taxon) {return $taxon->getCode(); })->toArray();
        $syliusTaxonsCodes = $syliusTaxons->map(function (TaxonInterface $taxon) {return $taxon->getCode(); })->toArray();
        $toAddTaxons = $akeneoTaxons->filter(
            function (TaxonInterface $taxon) use ($syliusTaxonsCodes) {
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
        /** @var ProductInterface $product */
        $product->setCode($parentCode);
        foreach ($this->productOptionsResolver->resolve($productVariantResponse) as $productOption) {
            $product->addOption($productOption);
        }

        return $product;
    }
}
