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
use Webgriffe\SyliusAkeneoPlugin\ProductModel\CategoriesHandlerInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerResolverInterface;
use Webmozart\Assert\Assert;

final class Importer implements ImporterInterface
{
    /** @var ProductVariantFactoryInterface */
    private $productVariantFactory;

    /** @var ProductVariantRepositoryInterface */
    private $productVariantRepository;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var ApiClientInterface */
    private $apiClient;

    /** @var ValueHandlerResolverInterface */
    private $valueHandlerResolver;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var ProductFactoryInterface */
    private $productFactory;

    /** @var CategoriesHandlerInterface */
    private $categoriesHandler;

    public function __construct(
        ProductVariantFactoryInterface $productVariantFactory,
        ProductVariantRepositoryInterface $productVariantRepository,
        ProductRepositoryInterface $productRepository,
        ApiClientInterface $apiClient,
        ValueHandlerResolverInterface $valueHandlerResolver,
        ProductFactoryInterface $productFactory,
        CategoriesHandlerInterface $categoriesHandler,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->productVariantFactory = $productVariantFactory;
        $this->productVariantRepository = $productVariantRepository;
        $this->productRepository = $productRepository;
        $this->apiClient = $apiClient;
        $this->valueHandlerResolver = $valueHandlerResolver;
        $this->productFactory = $productFactory;
        $this->categoriesHandler = $categoriesHandler;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function import(string $identifier): void
    {
        $productVariantResponse = $this->apiClient->findProduct($identifier);
        if (!$productVariantResponse) {
            throw new \RuntimeException(sprintf('Cannot find product "%s" on Akeneo.', $identifier));
        }

        $product = $this->getOrCreateProductFromVariantResponse($productVariantResponse);

        $productVariant = $this->productVariantRepository->findOneBy(['code' => $identifier]);
        if (!$productVariant instanceof ProductVariantInterface) {
            /** @var ProductVariantInterface $productVariant */
            $productVariant = $this->productVariantFactory->createNew();
            $productVariant->setCode($identifier);
        }
        $productVariant->setProduct($product);

        foreach ($productVariantResponse['values'] as $attribute => $value) {
            $valueHandler = $this->valueHandlerResolver->resolve($productVariant, $attribute, $value);
            if ($valueHandler === null) {
                // TODO no value handler for this attribute. Throw? Log?
                // throw new \RuntimeException(sprintf('No ValueHandler found for attribute "%s"', $attribute));
                continue;
            }
            $valueHandler->handle($productVariant, $attribute, $value);
        }

        $this->productVariantRepository->add($productVariant);
    }

    private function getOrCreateProductFromVariantResponse(array $productVariantResponse): ProductInterface
    {
        $identifier = $productVariantResponse['identifier'];
        $parentCode = $productVariantResponse['parent'];
        if ($parentCode !== null) {
            $product = $this->productRepository->findOneByCode($parentCode);
            if (!$product) {
                $identifier = $productVariantResponse['identifier'];

                throw new \RuntimeException(
                    sprintf(
                        'Cannot import Akeneo product "%s", the parent product "%s" does not exists on Sylius.',
                        $identifier,
                        $parentCode
                    )
                );
            }

            return $product;
        }

        $eventName = 'create';
        // todo: handle product exists case -> find($identifier)

        $product = $this->productFactory->createNew();
        Assert::isInstanceOf($product, ProductInterface::class);
        /** @var ProductInterface $product */
        $product->setCode($identifier);

        $this->categoriesHandler->handle($product, $productVariantResponse['categories']);

        foreach ($productVariantResponse['values'] as $attribute => $value) {
            $valueHandler = $this->valueHandlerResolver->resolve($product, $attribute, $value);
            if ($valueHandler === null) {
                // TODO no value handler for this attribute. Throw? Log?
                // throw new \RuntimeException(sprintf('No ValueHandler found for attribute "%s"', $attribute));
                continue;
            }
            $valueHandler->handle($product, $attribute, $value);
        }

        $this->dispatchPreEvent($product, $eventName);
        // TODO We should handle $event->isStopped() where $event is the return value of the dispatchPreEvent method.
        //      See \Sylius\Bundle\ResourceBundle\Controller\ResourceController.
        $this->productRepository->add($product);
        $this->dispatchPostEvent($product, $eventName);

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
}
