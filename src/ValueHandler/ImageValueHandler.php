<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Sylius\Component\Core\Model\ProductImageInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;
use Webmozart\Assert\Assert;

final class ImageValueHandler implements ValueHandlerInterface
{
    /** @var FactoryInterface */
    private $productImageFactory;

    /** @var RepositoryInterface */
    private $productImageRepository;

    /** @var ApiClientInterface */
    private $apiClient;

    /** @var string */
    private $akeneoAttributeCode;

    /** @var string */
    private $syliusImageType;

    public function __construct(
        FactoryInterface $productImageFactory,
        RepositoryInterface $productImageRepository,
        ApiClientInterface $apiClient,
        string $akeneoAttributeCode,
        string $syliusImageType
    ) {
        $this->productImageFactory = $productImageFactory;
        $this->productImageRepository = $productImageRepository;
        $this->apiClient = $apiClient;
        $this->akeneoAttributeCode = $akeneoAttributeCode;
        $this->syliusImageType = $syliusImageType;
    }

    /**
     * @param mixed $subject
     */
    public function supports($subject, string $attribute, array $value): bool
    {
        return $subject instanceof ProductVariantInterface && $this->akeneoAttributeCode === $attribute;
    }

    /**
     * @param mixed $subject
     */
    public function handle($subject, string $attribute, array $value): void
    {
        if (!$subject instanceof ProductVariantInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'This image value handler only supports instances of %s, %s given.',
                    ProductVariantInterface::class,
                    is_object($subject) ? get_class($subject) : gettype($subject)
                )
            );
        }
        $mediaCode = $value[0]['data'] ?? null;
        if (!is_string($mediaCode)) {
            throw new \InvalidArgumentException('Invalid Akeneo image data. Cannot find the media code.');
        }
        $imageFile = $this->apiClient->downloadFile($mediaCode);

        $product = $subject->getProduct();
        /** @var ProductInterface $product */
        Assert::isInstanceOf($product, ProductInterface::class);

        $productImage = $this->getExistentProductVariantImage($subject, $product);
        if (!$productImage) {
            $productImage = $this->productImageFactory->createNew();
            Assert::isInstanceOf($productImage, ProductImageInterface::class);
            /** @var ProductImageInterface $productImage */
            $productImage->setType($this->syliusImageType);
            $productImage->addProductVariant($subject);
            $product->addImage($productImage);
        }
        $productImage->setFile($imageFile);
    }

    private function getExistentProductVariantImage(
        ProductVariantInterface $subject,
        ProductInterface $product
    ): ?ProductImageInterface {
        $existentProductImages = $this->productImageRepository->findBy(
            ['owner' => $product, 'type' => $this->syliusImageType]
        );
        /** @var ProductImageInterface[] $existentProductImages */
        Assert::allIsInstanceOf($existentProductImages, ProductImageInterface::class);
        foreach ($existentProductImages as $existentProductImage) {
            if ($existentProductImage->hasProductVariant($subject)) {
                return $existentProductImage;
            }
        }

        return null;
    }
}
