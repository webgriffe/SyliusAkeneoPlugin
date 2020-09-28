<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\Filesystem\Filesystem;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\FileAttributeValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;

class FileAttributeValueHandlerSpec extends ObjectBehavior
{
    const AKENEO_FILE_ATTRIBUTE_CODE = 'allegato_1';

    function let(
        \SplFileInfo $attachmentFile,
        ApiClientInterface $apiClient,
        Filesystem $filesystem
    ) {
        $attachmentFile->getPathname()->willReturn('/private/var/folders/A/B/C/akeneo-ABC');
        $apiClient->downloadFile(Argument::type('string'))->willReturn($attachmentFile);
        $apiClient->findAttribute('allegato_1')->willReturn(['type' => 'pim_catalog_file']);
        $this->beConstructedWith($apiClient, $filesystem, 'allegato_1', 'public/media/attachment/product/');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(FileAttributeValueHandler::class);
    }

    function it_implements_value_handler_interface()
    {
        $this->shouldHaveType(ValueHandlerInterface::class);
    }

    function it_supports_product_variant_as_subject(ProductVariantInterface $productVariant)
    {
        $this->supports($productVariant, self::AKENEO_FILE_ATTRIBUTE_CODE, [])->shouldReturn(true);
    }

    function it_supports_attribute_code_with_given_prefix(ProductVariantInterface $productVariant)
    {
        $this->supports($productVariant, self::AKENEO_FILE_ATTRIBUTE_CODE, [])->shouldReturn(true);
    }

    function it_does_not_support_attribute_with_wrong_prefix(
        ProductVariantInterface $productVariant
    ) {
        $this->supports($productVariant, 'another_attribute', [])->shouldReturn(false);
    }

    function it_does_not_support_attribute_that_is_not_file_attribute(
        ProductVariantInterface $productVariant,
        ApiClientInterface $apiClient
    ) {
        $apiClient->findAttribute('allegato_1')->willReturn(['type' => 'pim_catalog_simpleselect']);
        $this
            ->shouldThrow(
                new \InvalidArgumentException(
                    'This file value handler only supports akeneo file attributes. allegato_1 is not a file attribute',
                )
            )
            ->during('supports', [$productVariant, 'allegato_1', []]);
    }

    function it_does_not_support_any_other_type_of_subject()
    {
        $this->supports(new \stdClass(), self::AKENEO_FILE_ATTRIBUTE_CODE, [])->shouldReturn(false);
    }

    function it_throws_an_exception_while_handling_subject_that_is_not_a_product()
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

    function it_throws_with_invalid_akeneo_file_data_during_handling(ProductVariantInterface $productVariant)
    {
        $this
            ->shouldThrow(new \InvalidArgumentException('Invalid Akeneo attachment data. Cannot find the media code.'))
            ->during('handle', [$productVariant, self::AKENEO_FILE_ATTRIBUTE_CODE, [['malformed' => 'data']]]);
    }

    function it_save_file_to_media_when_handling_product_variant(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ApiClientInterface $apiClient,
        Filesystem $filesystem
    ) {
        $productVariant->getProduct()->willReturn($product);

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

        $apiClient->downloadFile('path/to/a/file.jpg')->shouldHaveBeenCalled();
        $filesystem->exists('public/media/attachment/product/path/to/a')->shouldHaveBeenCalled();
        $filesystem->mkdir('public/media/attachment/product/path/to/a')->shouldHaveBeenCalled();
        $filesystem->rename('/private/var/folders/A/B/C/akeneo-ABC', 'public/media/attachment/product/path/to/a/file.jpg', true)->shouldHaveBeenCalled();
    }
}
