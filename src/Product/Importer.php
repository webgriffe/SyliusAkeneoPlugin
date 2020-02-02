<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Product;

use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
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

    /** @var CategoriesHandlerInterface */
    private $categoriesHandler;

    /** @var FamilyVariantHandlerInterface */
    private $familyVariantHandler;

    /** @var ChannelsResolverInterface */
    private $channelsResolver;

    /** @var StatusResolverInterface */
    private $statusResolver;

    public function __construct(
        ProductVariantFactoryInterface $productVariantFactory,
        ProductVariantRepositoryInterface $productVariantRepository,
        ProductRepositoryInterface $productRepository,
        ApiClientInterface $apiClient,
        ValueHandlersResolverInterface $valueHandlerResolver,
        ProductFactoryInterface $productFactory,
        CategoriesHandlerInterface $categoriesHandler,
        FamilyVariantHandlerInterface $familyVariantHandler,
        EventDispatcherInterface $eventDispatcher,
        ChannelsResolverInterface $channelsResolver,
        StatusResolverInterface $statusResolver
    ) {
        $this->productVariantFactory = $productVariantFactory;
        $this->productVariantRepository = $productVariantRepository;
        $this->productRepository = $productRepository;
        $this->apiClient = $apiClient;
        $this->valueHandlersResolver = $valueHandlerResolver;
        $this->productFactory = $productFactory;
        $this->categoriesHandler = $categoriesHandler;
        $this->familyVariantHandler = $familyVariantHandler;
        $this->eventDispatcher = $eventDispatcher;
        $this->channelsResolver = $channelsResolver;
        $this->statusResolver = $statusResolver;
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

        $this->categoriesHandler->handle($product, $productVariantResponse['categories']);

        $productVariant = $this->productVariantRepository->findOneBy(['code' => $identifier]);
        if (!$productVariant instanceof ProductVariantInterface) {
            /** @var ProductVariantInterface $productVariant */
            $productVariant = $this->productVariantFactory->createNew();
            $productVariant->setCode($identifier);
        }
        $product->addVariant($productVariant);
        $productVariant->setProduct($product);

        $product->setEnabled($this->statusResolver->resolve($productVariantResponse));

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
                $product = $this->productFactory->createNew();
                Assert::isInstanceOf($product, ProductInterface::class);
                /** @var ProductInterface $product */
                $productResponse = $this->apiClient->findProductModel($parentCode);
                if (!$productResponse) {
                    throw new \RuntimeException(sprintf('Cannot find product model "%s" on Akeneo.', $parentCode));
                }
                $familyCode = $productResponse['family'];
                $familyVariantCode = $productResponse['family_variant'];
                $familyVariantResponse = $this->apiClient->findFamilyVariant($familyCode, $familyVariantCode);
                $this->familyVariantHandler->handle($product, $familyVariantResponse);
            }
            Assert::isInstanceOf($product, ProductInterface::class);
            /** @var ProductInterface $product */
            $product->setCode($parentCode);

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
        $this->eventDispatcher->dispatch(sprintf('sylius.product.pre_%s', $eventName), $event);

        return $event;
    }

    private function dispatchPostEvent(ResourceInterface $product, string $eventName): ResourceControllerEvent
    {
        $event = new ResourceControllerEvent($product);
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
}
