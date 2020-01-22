<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Sylius\Component\Core\Model\ProductImageInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;
use Webmozart\Assert\Assert;

final class ImageValueHandler implements ValueHandlerInterface
{
    /** @var FactoryInterface */
    private $productImageFactory;

    /** @var ApiClientInterface */
    private $apiClient;

    /** @var string */
    private $akeneoAttributeCode;

    /** @var string */
    private $syliusImageType;

    public function __construct(
        FactoryInterface $productImageFactory,
        ApiClientInterface $apiClient,
        string $akeneoAttributeCode,
        string $syliusImageType
    ) {
        $this->productImageFactory = $productImageFactory;
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

        $productImage = $this->productImageFactory->createNew();
        Assert::isInstanceOf($productImage, ProductImageInterface::class);
        /** @var ProductImageInterface $productImage */
        $productImage->setType($this->syliusImageType);
        $productImage->setFile($imageFile);

        $productImage->addProductVariant($subject);
        $subject = $subject->getProduct();
        /** @var ProductInterface $subject */
        Assert::isInstanceOf($subject, ProductInterface::class);

        foreach ($subject->getImagesByType($this->syliusImageType) as $existentImage) {
            $subject->removeImage($existentImage);
        }
        $subject->addImage($productImage);
    }
}
