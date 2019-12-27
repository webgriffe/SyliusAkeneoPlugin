<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\ProductModel;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Component\Core\Model\ImageInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
use Webgriffe\SyliusAkeneoPlugin\ProductModel\ImageValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ProductModel\ValueHandlerInterface;

class ImageValueHandlerSpec extends ObjectBehavior
{
    private const AKENEO_ATTRIBUTE_CODE = 'akeneo_attribute_code';

    private const SYLIUS_IMAGE_TYPE = 'sylius_image_type';

    private const AKENEO_IMAGE_ATTRIBUTE_DATA = [
        [
            'locale' => null,
            'scope' => null,
            'data' => 'path/to/a/file.jpg',
            '_links' => ['download' => ['href' => 'download-url']],
        ],
    ];

    function let(
        FactoryInterface $productImageFactory,
        ImageInterface $productImage,
        ApiClientInterface $apiClient,
        \SplFileInfo $imageFile
    ) {
        $productImageFactory->createNew()->willReturn($productImage);
        $apiClient->downloadFile(Argument::type('string'))->willReturn($imageFile);
        $this->beConstructedWith(
            $productImageFactory,
            $apiClient,
            self::AKENEO_ATTRIBUTE_CODE,
            self::SYLIUS_IMAGE_TYPE
        );
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(ImageValueHandler::class);
    }

    function it_implements_value_handler_interface()
    {
        $this->shouldHaveType(ValueHandlerInterface::class);
    }

    function it_supports_provided_akeneo_attribute_code(ProductInterface $product)
    {
        $this->supports($product, self::AKENEO_ATTRIBUTE_CODE, [])->shouldReturn(true);
    }

    function it_does_not_support_another_attribute_code(ProductInterface $product)
    {
        $this->supports($product, 'another_attribute_code', [])->shouldReturn(false);
    }

    function it_adds_image_to_product_when_handling(
        ProductInterface $product,
        ImageInterface $productImage
    ) {
        $this->handle($product, self::AKENEO_ATTRIBUTE_CODE, self::AKENEO_IMAGE_ATTRIBUTE_DATA);

        $product->addImage($productImage)->shouldHaveBeenCalled();
    }

    function it_should_download_image_from_akeneo_when_handling(
        ProductInterface $product,
        ApiClientInterface $apiClient
    ) {
        $this->handle($product, self::AKENEO_ATTRIBUTE_CODE, self::AKENEO_IMAGE_ATTRIBUTE_DATA);

        $apiClient->downloadFile('download-url')->shouldHaveBeenCalled();
    }

    function it_sets_downloaded_image_to_product_image_when_handling(
        ProductInterface $product,
        ImageInterface $productImage,
        \SplFileInfo $imageFile
    ) {
        $this->handle($product, self::AKENEO_ATTRIBUTE_CODE, self::AKENEO_IMAGE_ATTRIBUTE_DATA);

        $productImage->setFile($imageFile)->shouldHaveBeenCalled();
    }

    function it_sets_provided_product_image_type_when_handling(ProductInterface $product, ImageInterface $productImage)
    {
        $this->handle($product, self::AKENEO_ATTRIBUTE_CODE, self::AKENEO_IMAGE_ATTRIBUTE_DATA);

        $productImage->setType(self::SYLIUS_IMAGE_TYPE)->shouldHaveBeenCalled();
    }
}
