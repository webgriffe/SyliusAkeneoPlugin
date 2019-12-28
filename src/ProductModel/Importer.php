<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ProductModel;

use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webmozart\Assert\Assert;

final class Importer implements ImporterInterface
{
    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var ProductFactoryInterface */
    private $productFactory;

    /** @var CategoriesHandlerInterface */
    private $categoriesHandler;

    /** @var FamilyVariantHandlerInterface */
    private $familyVariantHandler;

    /** @var ValueHandlerResolverInterface */
    private $valueHandlerResolver;

    /** @var ApiClientInterface */
    private $apiClient;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductFactoryInterface $productFactory,
        CategoriesHandlerInterface $categoriesHandler,
        FamilyVariantHandlerInterface $familyVariantHandler,
        ValueHandlerResolverInterface $valueHandlerResolver,
        ApiClientInterface $apiClient,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->categoriesHandler = $categoriesHandler;
        $this->familyVariantHandler = $familyVariantHandler;
        $this->valueHandlerResolver = $valueHandlerResolver;
        $this->apiClient = $apiClient;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function import(string $identifier): void
    {
        /** @var array $productModelResponse */
        $productModelResponse = $this->apiClient->findProductModel($identifier);
        $code = $productModelResponse['code'];
        $product = $this->productRepository->findOneByCode($code);
        $eventName = 'update';
        if (!$product) {
            $eventName = 'create';
            $product = $this->productFactory->createNew();
        }
        Assert::isInstanceOf($product, ProductInterface::class);
        /** @var ProductInterface $product */
        $product->setCode($code);
        $this->categoriesHandler->handle($product, $productModelResponse['categories']);

        foreach ($productModelResponse['values'] as $attribute => $value) {
            $valueHandler = $this->valueHandlerResolver->resolve($product, $attribute, $value);
            if ($valueHandler === null) {
                // TODO no value handler for this attribute. Throw? Log?
                // throw new \RuntimeException(sprintf('No ValueHandler found for attribute "%s"', $attribute));
                continue;
            }
            $valueHandler->handle($product, $attribute, $value);
        }

        $familyCode = $productModelResponse['family'];
        $familyVariantCode = $productModelResponse['family_variant'];
        $familyVariantResponse = $this->apiClient->findFamilyVariant($familyCode, $familyVariantCode);

        $this->familyVariantHandler->handle($product, $familyVariantResponse);

        $this->dispatchPreEvent($product, $eventName);
        // TODO We should handle $event->isStopped() where $event is the return value of the dispatchPreEvent method.
        //      See \Sylius\Bundle\ResourceBundle\Controller\ResourceController.
        $this->productRepository->add($product);
        $this->dispatchPostEvent($product, $eventName);
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
