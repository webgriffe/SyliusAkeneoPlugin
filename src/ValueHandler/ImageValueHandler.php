<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Doctrine\Common\Collections\ArrayCollection;
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
        if (!array_key_exists(0, $value) || !is_array($value[0]) || !array_key_exists('data', $value[0])) {
            throw new \InvalidArgumentException('Invalid Akeneo image data. Cannot find the media code.');
        }

        $product = $subject->getProduct();
        Assert::isInstanceOf($product, ProductInterface::class);

        if ($value[0]['data'] === null) {
            $this->removeAlreadyExistentVariantImages($subject, $product);

            return;
        }

        /** @var string $mediaCode */
        $mediaCode = $value[0]['data'];
        $imageFile = $this->apiClient->downloadFile($mediaCode);

        $productImage = $this->getExistentProductVariantImage($subject, $product);
        if ($productImage === null) {
            $productImage = $this->productImageFactory->createNew();
            Assert::isInstanceOf($productImage, ProductImageInterface::class);
            $productImage->setType($this->syliusImageType);
            if (!$product->isSimple()) {
                $subject->addImage($productImage);
            }
            $product->addImage($productImage);
        }
        $productImage->setFile($imageFile);
    }

    private function getExistentProductVariantImage(
        ProductVariantInterface $subject,
        ProductInterface $product
    ): ?ProductImageInterface {
        $existentProductImages = $this->getExistentProductImages($product);
        foreach ($existentProductImages as $existentProductImage) {
            if ($product->isSimple() || $existentProductImage->hasProductVariant($subject)) {
                return $existentProductImage;
            }
        }

        return null;
    }

    /**
     * @return ProductImageInterface[]
     */
    private function getExistentProductVariantImages(
        ProductVariantInterface $subject,
        ProductInterface $product
    ): array {
        $images = [];
        $existentProductImages = $this->getExistentProductImages($product);

        foreach ($existentProductImages as $existentProductImage) {
            if ($existentProductImage->hasProductVariant($subject)) {
                $images[] = $existentProductImage;
            }
        }

        return $images;
    }

    /**
     * @return ProductImageInterface[]
     */
    private function getExistentProductImages(ProductInterface $product): iterable
    {
        $existentProductImages = $this->productImageRepository->findBy(
            ['owner' => $product, 'type' => $this->syliusImageType]
        );
        Assert::allIsInstanceOf($existentProductImages, ProductImageInterface::class);

        return $existentProductImages;
    }

    private function removeAlreadyExistentVariantImages(
        ProductVariantInterface $subject,
        ProductInterface $product
    ): void {
        $alreadyExistentImages = $this->getExistentProductVariantImages($subject, $product);
        foreach ($alreadyExistentImages as $alreadyExistentImage) {
            $product->removeImage($alreadyExistentImage);
            $subject->removeImage($alreadyExistentImage);
        }
    }
}
