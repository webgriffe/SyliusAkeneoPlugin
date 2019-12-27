<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ProductModel;

use Sylius\Component\Core\Model\ImageInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
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

    public function supports(ProductInterface $product, string $attribute, array $value): bool
    {
        return $this->akeneoAttributeCode === $attribute;
    }

    public function handle(ProductInterface $product, string $attribute, array $value)
    {
        $downloadUrl = $value[0]['_links']['download']['href'] ?? null;
        if (!is_string($downloadUrl)) {
            throw new \InvalidArgumentException('Invalid Akeneo image data. Cannot find download URL.');
        }
        $imageFile = $this->apiClient->downloadFile($downloadUrl);
        $productImage = $this->productImageFactory->createNew();
        Assert::isInstanceOf($productImage, ImageInterface::class);
        /** @var ImageInterface $productImage */
        $productImage->setType($this->syliusImageType);
        $productImage->setFile($imageFile);
        foreach ($product->getImagesByType($this->syliusImageType) as $existentImage) {
            $product->removeImage($existentImage);
        }
        $product->addImage($productImage);
    }
}
