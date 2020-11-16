<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Sylius\Component\Core\Model\ProductImageInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\HttpFoundation\File\File;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;
use Webmozart\Assert\Assert;

final class ImageValueHandler implements ValueHandlerInterface
{
    /** @var FactoryInterface */
    private $productImageFactory;

    /** @var RepositoryInterface */
    private $productImageRepository;

    /** @var AkeneoPimClientInterface */
    private $apiClient;

    /** @var string */
    private $akeneoAttributeCode;

    /** @var string */
    private $syliusImageType;

    public function __construct(
        FactoryInterface $productImageFactory,
        RepositoryInterface $productImageRepository,
        AkeneoPimClientInterface $apiClient,
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
        $imageFile = $this->downloadFile($mediaCode);

        $product = $subject->getProduct();
        Assert::isInstanceOf($product, ProductInterface::class);
        /** @var ProductInterface $product */
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
        Assert::allIsInstanceOf($existentProductImages, ProductImageInterface::class);
        /** @var ProductImageInterface $existentProductImage */
        foreach ($existentProductImages as $existentProductImage) {
            if ($existentProductImage->hasProductVariant($subject)) {
                return $existentProductImage;
            }
        }

        return null;
    }

    private function downloadFile(string $mediaCode): \SplFileInfo
    {
        $response = $this->apiClient->getProductMediaFileApi()->download($mediaCode);
        $statusClass = (int) ($response->getStatusCode() / 100);
        $bodyContents = $response->getBody()->getContents();
        if ($statusClass !== 2) {
            $responseResult = json_decode($bodyContents, true);

            throw new \HttpException($responseResult['message'], $responseResult['code']);
        }
        $tempName = tempnam(sys_get_temp_dir(), 'akeneo-');
        Assert::string($tempName);
        file_put_contents($tempName, $bodyContents);

        return new File($tempName);
    }
}
