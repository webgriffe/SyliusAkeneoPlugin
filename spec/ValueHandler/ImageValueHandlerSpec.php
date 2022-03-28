<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\MediaFileApiInterface;
use Doctrine\Common\Collections\ArrayCollection;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use SplFileInfo;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ProductImageInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
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

    public function let(
        FactoryInterface $productImageFactory,
        RepositoryInterface $productImageRepository,
        ProductImageInterface $productImage,
        AkeneoPimClientInterface $apiClient,
        MediaFileApiInterface $productMediaFileApi,
        ResponseInterface $downloadResponse,
        StreamInterface $responseBody,
        ProductVariantInterface $productVariant,
        ProductInterface $product
    ): void {
        $productImageFactory->createNew()->willReturn($productImage);
        $apiClient->getProductMediaFileApi()->willReturn($productMediaFileApi);
        $productMediaFileApi->download(Argument::type('string'))->willReturn($downloadResponse);
        $downloadResponse->getStatusCode()->willReturn(200);
        $downloadResponse->getBody()->willReturn($responseBody);
        $responseBody->getContents()->willReturn('__FILE_CONTENT__');
        $product->getImagesByType(self::SYLIUS_IMAGE_TYPE)->willReturn(new ArrayCollection([]));
        $product->addImage($productImage)->hasReturnVoid();
        $product->isSimple()->willReturn(true);
        $commerceChannel = new Channel();
        $commerceChannel->setCode('ecommerce');
        $supportChannel = new Channel();
        $supportChannel->setCode('support');
        $product->getChannels()->willReturn(new ArrayCollection([$commerceChannel, $supportChannel]));
        $productVariant->addImage($productImage)->hasReturnVoid();
        $productVariant->getProduct()->willReturn($product);
        $productImageRepository
            ->findBy(['owner' => $product, 'type' => self::SYLIUS_IMAGE_TYPE])
            ->willReturn(new ArrayCollection([]));
        $this->beConstructedWith(
            $productImageFactory,
            $productImageRepository,
            $apiClient,
            self::AKENEO_ATTRIBUTE_CODE,
            self::SYLIUS_IMAGE_TYPE
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(ImageValueHandler::class);
    }

    public function it_implements_value_handler_interface(): void
    {
        $this->shouldHaveType(\Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface::class);
    }

    public function it_supports_product_variant_as_subject(ProductVariantInterface $productVariant): void
    {
        $this->supports($productVariant, self::AKENEO_ATTRIBUTE_CODE, [])->shouldReturn(true);
    }

    public function it_supports_provided_akeneo_attribute_code(ProductVariantInterface $productVariant): void
    {
        $this->supports($productVariant, self::AKENEO_ATTRIBUTE_CODE, [])->shouldReturn(true);
    }

    public function it_does_not_support_another_attribute_code(ProductVariantInterface $productVariant): void
    {
        $this->supports($productVariant, 'another_attribute_code', [])->shouldReturn(false);
    }

    public function it_does_not_support_other_types_of_subject_than_product_variant(): void
    {
        $this->supports(new \stdClass(), self::AKENEO_ATTRIBUTE_CODE, [])->shouldReturn(false);
    }

    public function it_throws_an_exception_while_handling_subject_that_is_not_a_product(): void
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

    public function it_adds_image_to_product_when_handling_product_variant(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductImageInterface $productImage
    ): void {
        $product->isSimple()->willReturn(true);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, self::AKENEO_IMAGE_ATTRIBUTE_DATA);

        $productVariant->addImage($productImage)->shouldNotHaveBeenCalled();
        $product->addImage($productImage)->shouldHaveBeenCalled();
    }

    public function it_adds_product_variant_association_to_image_when_handling_product_variant_from_configurable(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductImageInterface $productImage
    ): void {
        $product->isSimple()->willReturn(false);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, self::AKENEO_IMAGE_ATTRIBUTE_DATA);

        $productVariant->addImage($productImage)->shouldHaveBeenCalled();
        $product->addImage($productImage)->shouldHaveBeenCalled();
    }

    public function it_should_download_image_from_akeneo_when_handling(
        ProductVariantInterface $productVariant,
        MediaFileApiInterface $productMediaFileApi
    ): void {
        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, self::AKENEO_IMAGE_ATTRIBUTE_DATA);

        $productMediaFileApi->download('path/to/a/file.jpg')->shouldHaveBeenCalled();
    }

    public function it_sets_downloaded_image_to_product_image_when_handling(
        ProductVariantInterface $productVariant,
        ProductImageInterface $productImage
    ): void {
        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, self::AKENEO_IMAGE_ATTRIBUTE_DATA);

        $productImage->setFile(Argument::type(SplFileInfo::class))->shouldHaveBeenCalled();
    }

    public function it_sets_provided_product_image_type_when_handling(
        ProductVariantInterface $productVariant,
        ProductImageInterface $productImage
    ): void {
        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, self::AKENEO_IMAGE_ATTRIBUTE_DATA);

        $productImage->setType(self::SYLIUS_IMAGE_TYPE)->shouldHaveBeenCalled();
    }

    public function it_updates_already_existent_product_image_of_the_provided_type_when_handling(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductImageInterface $existentProductImage,
        RepositoryInterface $productImageRepository,
        FactoryInterface $productImageFactory
    ): void {
        $productImageRepository
            ->findBy(['owner' => $product, 'type' => self::SYLIUS_IMAGE_TYPE])
            ->willReturn(new ArrayCollection([$existentProductImage->getWrappedObject()]));
        $existentProductImage->hasProductVariant($productVariant)->willReturn(true);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, self::AKENEO_IMAGE_ATTRIBUTE_DATA);

        $productImageFactory->createNew()->shouldNotHaveBeenCalled();
        $existentProductImage->setFile(Argument::type(SplFileInfo::class))->shouldHaveBeenCalled();
    }

    public function it_does_not_overwrite_already_existent_product_image_of_another_product_variant(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductImageInterface $existentProductImage,
        RepositoryInterface $productImageRepository,
        FactoryInterface $productImageFactory,
        ProductImageInterface $newProductImage
    ): void {
        $product->isSimple()->willReturn(false);
        $productImageRepository
            ->findBy(['owner' => $product, 'type' => self::SYLIUS_IMAGE_TYPE])
            ->willReturn(new ArrayCollection([$existentProductImage->getWrappedObject()]));
        $existentProductImage->hasProductVariant($productVariant)->willReturn(false);
        $productImageFactory->createNew()->willReturn($newProductImage);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, self::AKENEO_IMAGE_ATTRIBUTE_DATA);

        $productImageFactory->createNew()->shouldHaveBeenCalled();
        $newProductImage->setType(self::SYLIUS_IMAGE_TYPE)->shouldHaveBeenCalled();
        $productVariant->addImage($newProductImage)->shouldHaveBeenCalled();
        $product->addImage($newProductImage)->shouldHaveBeenCalled();
        $newProductImage->setFile(Argument::type(SplFileInfo::class))->shouldHaveBeenCalled();
    }

    public function it_throws_with_invalid_akeneo_image_data_during_handling(ProductVariantInterface $productVariant): void
    {
        $this
            ->shouldThrow(new \InvalidArgumentException('Invalid Akeneo value data: cannot find the media code.'))
            ->during('handle', [$productVariant, self::AKENEO_ATTRIBUTE_CODE, [['malformed' => 'data']]]);
    }

    public function it_removes_existing_image_on_sylius_if_empty_on_akeneo(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductImageInterface $existentProductImage,
        RepositoryInterface $productImageRepository
    ): void {
        $existentProductImage->hasProductVariant($productVariant)->willReturn(true);
        $productImageRepository
            ->findBy(['owner' => $product, 'type' => self::SYLIUS_IMAGE_TYPE])
            ->willReturn(new ArrayCollection([$existentProductImage->getWrappedObject()]));

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, [['locale' => null, 'scope' => null, 'data' => null]]);

        $product->removeImage($existentProductImage)->shouldHaveBeenCalled();
        $productVariant->removeImage($existentProductImage)->shouldHaveBeenCalled();
    }

    public function it_does_not_remove_image_of_other_variant_on_sylius_if_empty_on_akeneo(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductImageInterface $existentProductImage,
        RepositoryInterface $productImageRepository
    ): void {
        $existentProductImage->hasProductVariant($productVariant)->willReturn(false);
        $productImageRepository
            ->findBy(['owner' => $product, 'type' => self::SYLIUS_IMAGE_TYPE])
            ->willReturn(new ArrayCollection([$existentProductImage->getWrappedObject()]));

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, [['locale' => null, 'scope' => null, 'data' => null]]);

        $product->removeImage($existentProductImage)->shouldNotHaveBeenCalled();
        $productVariant->removeImage($existentProductImage)->shouldNotHaveBeenCalled();
    }

    public function it_skips_images_related_to_channels_that_are_not_associated_to_the_product_and_it_downloads_the_first_file_with_null_scope(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductImageInterface $productImage,
        MediaFileApiInterface $productMediaFileApi
    ): void {
        $productVariant->getProduct()->willReturn($product);

        $this->handle(
            $productVariant,
            self::AKENEO_ATTRIBUTE_CODE,
            [
                [
                    'locale' => null,
                    'scope' => 'paper_catalog',
                    'data' => 'path/to/a/file-not-to-download.jpg',
                    '_links' => ['download' => ['href' => 'download-url']],
                ],
                [
                    'locale' => null,
                    'scope' => null,
                    'data' => 'path/to/a/file.jpg',
                    '_links' => ['download' => ['href' => 'download-url']],
                ],
            ]
        );

        $productMediaFileApi->download('path/to/a/file-not-to-download.jpg')->shouldNotHaveBeenCalled();

        $productMediaFileApi->download('path/to/a/file.jpg')->shouldHaveBeenCalled();
        $productVariant->addImage($productImage)->shouldNotHaveBeenCalled();
        $product->addImage($productImage)->shouldHaveBeenCalled();
        $productImage->setFile(Argument::type(SplFileInfo::class))->shouldHaveBeenCalled();
    }

    public function it_skips_images_related_to_channels_that_are_not_associated_to_the_product_and_it_downloads_the_first_file_with_channel_scope(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductImageInterface $productImage,
        MediaFileApiInterface $productMediaFileApi
    ): void {
        $productVariant->getProduct()->willReturn($product);

        $this->handle(
            $productVariant,
            self::AKENEO_ATTRIBUTE_CODE,
            [
                [
                    'locale' => null,
                    'scope' => 'paper_catalog',
                    'data' => 'path/to/a/file-not-to-download.jpg',
                    '_links' => ['download' => ['href' => 'download-url']],
                ],
                [
                    'locale' => null,
                    'scope' => 'ecommerce',
                    'data' => 'path/to/a/file.jpg',
                    '_links' => ['download' => ['href' => 'download-url']],
                ],
                [
                    'locale' => null,
                    'scope' => null,
                    'data' => 'path/to/a/file-not-to-download.jpg',
                    '_links' => ['download' => ['href' => 'download-url']],
                ],
            ]
        );

        $productMediaFileApi->download('path/to/a/file-not-to-download.jpg')->shouldNotHaveBeenCalled();

        $productMediaFileApi->download('path/to/a/file.jpg')->shouldHaveBeenCalled();
        $productVariant->addImage($productImage)->shouldNotHaveBeenCalled();
        $product->addImage($productImage)->shouldHaveBeenCalled();
        $productImage->setFile(Argument::type(SplFileInfo::class))->shouldHaveBeenCalled();
    }

    public function it_throws_when_data_doesnt_contain_scope_info(
        ApiClientInterface $apiClient,
        ProductVariantInterface $productVariant,
        ProductImageInterface $productImage
    ): void {
        $this
            ->shouldThrow(new \InvalidArgumentException('Invalid Akeneo value data: required "scope" information was not found.'))
            ->during('handle', [
                $productVariant,
                self::AKENEO_ATTRIBUTE_CODE,
                [
                    [
                        'locale' => null,
                        'data' => 'path/to/a/file.jpg',
                        '_links' => ['download' => ['href' => 'download-url']],
                    ],
                ],
            ]);

        $apiClient->downloadFile('path/to/a/file.jpg')->shouldNotHaveBeenCalled();
        $productVariant->addImage($productImage)->shouldNotHaveBeenCalled();
    }

    public function it_throws_when_value_data_is_not_an_array(
        ApiClientInterface $apiClient,
        ProductVariantInterface $productVariant,
        ProductImageInterface $productImage
    ): void {
        $this
            ->shouldThrow(new \InvalidArgumentException('Invalid Akeneo value data: expected an array, "NULL" given.'))
            ->during('handle', [$productVariant, self::AKENEO_ATTRIBUTE_CODE, [null]]);

        $apiClient->downloadFile('path/to/a/file.jpg')->shouldNotHaveBeenCalled();
        $productVariant->addImage($productImage)->shouldNotHaveBeenCalled();
    }

    public function it_throws_when_data_is_not_string_nor_null(
        ApiClientInterface $apiClient,
        ProductVariantInterface $productVariant,
        ProductImageInterface $productImage
    ): void {
        $this
            ->shouldThrow(new \InvalidArgumentException('Invalid Akeneo value data: expected a string or null value, got "integer".'))
            ->during('handle', [
                $productVariant,
                self::AKENEO_ATTRIBUTE_CODE,
                [
                    [
                        'scope' => null,
                        'locale' => null,
                        'data' => 1,
                        '_links' => ['download' => ['href' => 'download-url']],
                    ],
                ],
            ]);

        $apiClient->downloadFile('path/to/a/file.jpg')->shouldNotHaveBeenCalled();
        $productVariant->addImage($productImage)->shouldNotHaveBeenCalled();
    }
}
