<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\AttributeApiInterface;
use Akeneo\Pim\ApiClient\Api\MediaFileApiInterface;
use Akeneo\Pim\ApiClient\Exception\HttpException;
use Doctrine\Common\Collections\ArrayCollection;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\Filesystem\Filesystem;
use Webgriffe\SyliusAkeneoPlugin\TemporaryFilesManagerInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\FileAttributeValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;
use Webmozart\Assert\InvalidArgumentException;

class FileAttributeValueHandlerSpec extends ObjectBehavior
{
    public const AKENEO_FILE_ATTRIBUTE_CODE = 'allegato_1';

    public function let(
        AkeneoPimClientInterface $apiClient,
        AttributeApiInterface $attributeApi,
        MediaFileApiInterface $productMediaFileApi,
        Filesystem $filesystem,
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        TemporaryFilesManagerInterface $temporaryFilesManager,
    ): void {
        $commerceChannel = new Channel();
        $commerceChannel->setCode('ecommerce');
        $supportChannel = new Channel();
        $supportChannel->setCode('support');
        $product->getChannels()->willReturn(new ArrayCollection([$commerceChannel, $supportChannel]));
        $productVariant->getProduct()->willReturn($product);
        $productVariant->getCode()->willReturn('VARIANT_1');
        $apiClient->getAttributeApi()->willReturn($attributeApi);
        $apiClient->getProductMediaFileApi()->willReturn($productMediaFileApi);
        $productMediaFileApi->download(Argument::type('string'))->willReturn(new Response(200, [], '__FILE_CONTENT__'));
        $attributeApi->get('allegato_1')->willReturn(['type' => 'pim_catalog_file']);
        $temporaryFilesManager->generateTemporaryFilePath('product-variant-VARIANT_1')->willReturn('tempfile');
        $this->beConstructedWith($apiClient, $filesystem, $temporaryFilesManager, 'allegato_1', 'public/media/attachment/product/');
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(FileAttributeValueHandler::class);
    }

    public function it_implements_value_handler_interface(): void
    {
        $this->shouldHaveType(ValueHandlerInterface::class);
    }

    public function it_supports_product_variant_as_subject(ProductVariantInterface $productVariant): void
    {
        $this->supports($productVariant, self::AKENEO_FILE_ATTRIBUTE_CODE, [])->shouldReturn(true);
    }

    public function it_supports_attribute_code_with_given_prefix(ProductVariantInterface $productVariant): void
    {
        $this->supports($productVariant, self::AKENEO_FILE_ATTRIBUTE_CODE, [])->shouldReturn(true);
    }

    public function it_does_not_support_attribute_with_wrong_prefix(
        ProductVariantInterface $productVariant
    ): void {
        $this->supports($productVariant, 'another_attribute', [])->shouldReturn(false);
    }

    public function it_does_not_support_attribute_that_is_not_file_attribute(
        ProductVariantInterface $productVariant,
        AttributeApiInterface $attributeApi
    ): void {
        $attributeApi->get('allegato_1')->willReturn(['type' => 'pim_catalog_simpleselect']);
        $this
            ->shouldThrow(
                new \InvalidArgumentException(
                    'This file value handler only supports akeneo file attributes. allegato_1 is not a file attribute',
                )
            )
            ->during('supports', [$productVariant, 'allegato_1', []]);
    }

    public function it_does_not_support_attribute_that_does_not_exist(
        ProductVariantInterface $productVariant,
        AttributeApiInterface $attributeApi
    ): void {
        $attributeApi->get('allegato_1')->willThrow(
            new HttpException('Not found', new Request('GET', '/'), new Response(404))
        );
        $this
            ->shouldThrow(
                new \InvalidArgumentException(
                    'The attribute "allegato_1" does not exists.',
                )
            )
            ->during('supports', [$productVariant, 'allegato_1', []]);
    }

    public function it_does_not_support_any_other_type_of_subject(): void
    {
        $this->supports(new \stdClass(), self::AKENEO_FILE_ATTRIBUTE_CODE, [])->shouldReturn(false);
    }

    public function it_throws_exception_during_handle_when_subject_is_not_product_variant(): void
    {
        $this
            ->shouldThrow(
                new \InvalidArgumentException(
                    sprintf(
                        'This file value handler only supports instances of %s, %s given.',
                        ProductVariantInterface::class,
                        \stdClass::class
                    )
                )
            )
            ->during('handle', [new \stdClass(), self::AKENEO_FILE_ATTRIBUTE_CODE, []]);
    }

    public function it_throws_exception_during_handle_when_product_variant_hasnt_an_associated_product(ProductVariantInterface $productVariant): void
    {
        $productVariant->getProduct()->willReturn(null);
        $this
            ->shouldThrow(InvalidArgumentException::class)
            ->during('handle', [$productVariant, self::AKENEO_FILE_ATTRIBUTE_CODE, []]);
    }

    public function it_throws_with_invalid_akeneo_file_data_during_handling(ProductVariantInterface $productVariant): void
    {
        $this
            ->shouldThrow(new \InvalidArgumentException('Invalid Akeneo attachment data: cannot find the media code.'))
            ->during('handle', [$productVariant, self::AKENEO_FILE_ATTRIBUTE_CODE, [['malformed' => 'data']]]);
    }

    public function it_save_file_to_media_when_handling_product_variant(
        ProductVariantInterface $productVariant,
        MediaFileApiInterface $productMediaFileApi,
        Filesystem $filesystem
    ): void {
        $filesystem->exists('public/media/attachment/product/path/to/a')->willReturn(false)->shouldBeCalledOnce();

        $this->handle(
            $productVariant,
            self::AKENEO_FILE_ATTRIBUTE_CODE,
            [
                [
                    'locale' => null,
                    'scope' => null,
                    'data' => 'path/to/a/file.jpg',
                    '_links' => ['download' => ['href' => 'download-url']],
                ],
            ]
        );

        $productMediaFileApi->download('path/to/a/file.jpg')->shouldHaveBeenCalled();
        $filesystem->mkdir('public/media/attachment/product/path/to/a')->shouldHaveBeenCalled();
        $filesystem->rename(Argument::type('string'), 'public/media/attachment/product/path/to/a/file.jpg', true)->shouldHaveBeenCalled();
    }

    public function it_skips_files_related_to_channels_that_are_not_associated_to_the_product_and_it_downloads_the_first_file_with_null_scope(
        ProductVariantInterface $productVariant,
        MediaFileApiInterface $productMediaFileApi,
        Filesystem $filesystem
    ): void {
        $filesystem->exists('public/media/attachment/product/path/to/a')->willReturn(false)->shouldBeCalledOnce();

        $this->handle(
            $productVariant,
            self::AKENEO_FILE_ATTRIBUTE_CODE,
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
        $filesystem->mkdir('public/media/attachment/product/path/to/a')->shouldHaveBeenCalled();
        $filesystem->rename(Argument::type('string'), 'public/media/attachment/product/path/to/a/file.jpg', true)->shouldHaveBeenCalled();
    }

    public function it_skips_files_related_to_channels_that_are_not_associated_to_the_product_and_it_downloads_the_first_file_with_channel_scope(
        ProductVariantInterface $productVariant,
        MediaFileApiInterface $productMediaFileApi,
        Filesystem $filesystem
    ): void {
        $filesystem->exists('public/media/attachment/product/path/to/a')->willReturn(false)->shouldBeCalledOnce();

        $this->handle(
            $productVariant,
            self::AKENEO_FILE_ATTRIBUTE_CODE,
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
        $filesystem->mkdir('public/media/attachment/product/path/to/a')->shouldHaveBeenCalled();
        $filesystem->rename(Argument::type('string'), 'public/media/attachment/product/path/to/a/file.jpg', true)->shouldHaveBeenCalled();
    }

    public function it_throws_when_data_doesnt_contain_scope_info(ProductVariantInterface $productVariant): void
    {
        $this
            ->shouldThrow(new \InvalidArgumentException('Invalid Akeneo value data: required "scope" information was not found.',))
            ->during(
                'handle',
                [
                    $productVariant,
                    self::AKENEO_FILE_ATTRIBUTE_CODE,
                    [
                        [
                            'locale' => null,
                            'data' => 'path/to/a/file.jpg',
                            '_links' => ['download' => ['href' => 'download-url']],
                        ],
                    ]
                ]
            );
    }

    public function it_throws_when_data_is_not_an_array(ProductVariantInterface $productVariant): void
    {
        $this
            ->shouldThrow(new \InvalidArgumentException('Invalid Akeneo value data: expected an array, "NULL" given.',))
            ->during('handle', [$productVariant, self::AKENEO_FILE_ATTRIBUTE_CODE, [null]]);
    }

    public function it_throws_when_data_is_not_string_nor_null(ProductVariantInterface $productVariant): void
    {
        $this
            ->shouldThrow(new \InvalidArgumentException('Invalid Akeneo value data: expected a string or null value, got "integer".',))
            ->during(
                'handle',
                [
                    $productVariant,
                    self::AKENEO_FILE_ATTRIBUTE_CODE,
                    [
                        [
                            'scope' => null,
                            'locale' => null,
                            'data' => 1,
                            '_links' => ['download' => ['href' => 'download-url']],
                        ],
                    ]
                ]
            );
    }
}
