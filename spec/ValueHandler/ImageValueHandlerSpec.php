<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Doctrine\Common\Collections\ArrayCollection;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Component\Core\Model\ProductImageInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\ImageValueHandler;

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
        ProductImageInterface $productImage,
        ApiClientInterface $apiClient,
        \SplFileInfo $imageFile,
        ProductInterface $product
    ) {
        $productImageFactory->createNew()->willReturn($productImage);
        $apiClient->downloadFile(Argument::type('string'))->willReturn($imageFile);
        $product->getImagesByType(self::SYLIUS_IMAGE_TYPE)->willReturn(new ArrayCollection([]));
        $product->addImage($productImage)->hasReturnVoid();
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
        $this->shouldHaveType(\Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface::class);
    }

    function it_supports_product_variant_as_subject(ProductVariantInterface $productVariant)
    {
        $this->supports($productVariant, self::AKENEO_ATTRIBUTE_CODE, [])->shouldReturn(true);
    }

    function it_supports_provided_akeneo_attribute_code(ProductVariantInterface $productVariant)
    {
        $this->supports($productVariant, self::AKENEO_ATTRIBUTE_CODE, [])->shouldReturn(true);
    }

    function it_does_not_support_another_attribute_code(ProductVariantInterface $productVariant)
    {
        $this->supports($productVariant, 'another_attribute_code', [])->shouldReturn(false);
    }

    function it_does_not_support_other_types_of_subject_than_product_variant()
    {
        $this->supports(new \stdClass(), self::AKENEO_ATTRIBUTE_CODE, [])->shouldReturn(false);
    }

    function it_throws_an_exception_while_handling_subject_that_is_not_a_product()
    {
        $this
            ->shouldThrow(
                new \InvalidArgumentException(
                    sprintf(
                        'This image value handler only supports instances of %s, %s given.',
                        ProductVariantInterface::class,
                        \stdClass::class
                    )
                )
            )
            ->during('handle', [new \stdClass(), self::AKENEO_ATTRIBUTE_CODE, []]);
    }

    function it_adds_image_to_product_when_handling_product_variant(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductImageInterface $productImage
    ) {
        $productVariant->getProduct()->willReturn($product);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, self::AKENEO_IMAGE_ATTRIBUTE_DATA);

        $product->addImage($productImage)->shouldHaveBeenCalled();
    }

    function it_adds_product_variant_association_to_image_when_handling_product_variant(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductImageInterface $productImage
    ) {
        $productVariant->getProduct()->willReturn($product);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, self::AKENEO_IMAGE_ATTRIBUTE_DATA);

        $productImage->addProductVariant($productVariant)->shouldHaveBeenCalled();
    }

    function it_should_download_image_from_akeneo_when_handling(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ApiClientInterface $apiClient
    ) {
        $productVariant->getProduct()->willReturn($product);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, self::AKENEO_IMAGE_ATTRIBUTE_DATA);

        $apiClient->downloadFile('download-url')->shouldHaveBeenCalled();
    }

    function it_sets_downloaded_image_to_product_image_when_handling(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductImageInterface $productImage,
        \SplFileInfo $imageFile
    ) {
        $productVariant->getProduct()->willReturn($product);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, self::AKENEO_IMAGE_ATTRIBUTE_DATA);

        $productImage->setFile($imageFile)->shouldHaveBeenCalled();
    }

    function it_sets_provided_product_image_type_when_handling(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductImageInterface $productImage
    ) {
        $productVariant->getProduct()->willReturn($product);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, self::AKENEO_IMAGE_ATTRIBUTE_DATA);

        $productImage->setType(self::SYLIUS_IMAGE_TYPE)->shouldHaveBeenCalled();
    }

    function it_removes_already_existent_product_image_of_the_provided_type_when_handling(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductImageInterface $existentProductImage
    ) {
        $productVariant->getProduct()->willReturn($product);
        $product->getImagesByType(self::SYLIUS_IMAGE_TYPE)->willReturn(
            new ArrayCollection([$existentProductImage->getWrappedObject()])
        );

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, self::AKENEO_IMAGE_ATTRIBUTE_DATA);

        $product->removeImage($existentProductImage)->shouldHaveBeenCalled();
    }

    function it_throws_with_invalid_akeneo_image_data_during_handling(ProductVariantInterface $productVariant)
    {
        $this
            ->shouldThrow(new \InvalidArgumentException('Invalid Akeneo image data. Cannot find download URL.'))
            ->during('handle', [$productVariant, self::AKENEO_ATTRIBUTE_CODE, [['malformed' => 'data']]]);
    }
}
