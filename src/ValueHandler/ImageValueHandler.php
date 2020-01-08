<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Sylius\Component\Core\Model\ImageInterface;
use Sylius\Component\Core\Model\ProductInterface;
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

    public function supports($subject, string $attribute, array $value): bool
    {
        return $subject instanceof ProductInterface && $this->akeneoAttributeCode === $attribute;
    }

    public function handle($subject, string $attribute, array $value)
    {
        if (!$subject instanceof ProductInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'This image value handler only supports instances of %s, %s given.',
                    ProductInterface::class,
                    is_object($subject) ? get_class($subject) : gettype($subject)
                )
            );
        }
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
        foreach ($subject->getImagesByType($this->syliusImageType) as $existentImage) {
            $subject->removeImage($existentImage);
        }
        $subject->addImage($productImage);
    }
}
