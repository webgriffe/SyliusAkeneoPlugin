<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Sylius\Component\Channel\Model\ChannelInterface;
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
    public function __construct(private FactoryInterface $productImageFactory, private RepositoryInterface $productImageRepository, private ApiClientInterface $apiClient, private string $akeneoAttributeCode, private string $syliusImageType)
    {
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
                    get_debug_type($subject)
                )
            );
        }

        /** @var ProductInterface|null $product */
        $product = $subject->getProduct();
        Assert::isInstanceOf($product, ProductInterface::class);
        $mediaCode = $this->getValue($value, $product);
        if ($mediaCode === null) {
            $this->removeAlreadyExistentVariantImages($subject, $product);

            return;
        }

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

    private function getValue(array $value, ProductInterface $product): ?string
    {
        $productChannelCodes = array_map(static fn (ChannelInterface $channel): ?string => $channel->getCode(), $product->getChannels()->toArray());
        foreach ($value as $valueData) {
            if (!is_array($valueData)) {
                throw new \InvalidArgumentException(sprintf('Invalid Akeneo value data: expected an array, "%s" given.', gettype($valueData)));
            }
            // todo: we should throw here? it seeme that API won't never return an empty array
            if (!array_key_exists('data', $valueData)) {
                continue;
            }

            if (!array_key_exists('scope', $valueData)) {
                throw new \InvalidArgumentException('Invalid Akeneo value data: required "scope" information was not found.');
            }
            if ($valueData['scope'] !== null && !in_array($valueData['scope'], $productChannelCodes, true)) {
                continue;
            }

            /** @psalm-suppress MixedAssignment */
            $data = $valueData['data'];
            if (!is_string($data) && null !== $data) {
                throw new \InvalidArgumentException(sprintf('Invalid Akeneo value data: expected a string or null value, got "%s".', gettype($data)));
            }

            return $data;
        }

        throw new \InvalidArgumentException('Invalid Akeneo value data: cannot find the media code.');
    }
}
