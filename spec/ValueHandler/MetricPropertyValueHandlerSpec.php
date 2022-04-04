<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use PhpSpec\ObjectBehavior;
use RuntimeException;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Webgriffe\SyliusAkeneoPlugin\Converter\UnitMeasurementValueConverterInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\MetricPropertyValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;

class MetricPropertyValueHandlerSpec extends ObjectBehavior
{
    private const AKENEO_ATTRIBUTE_CODE = 'akeneo_attribute_code';

    private const PROPERTY_PATH = 'property_path';

    private const KG_23_VALUE = ['scope' => null, 'locale' => null, 'data' => ['amount' => '23.0000', 'unit' => 'KILOGRAM']];

    public function let(
        PropertyAccessorInterface $propertyAccessor,
        UnitMeasurementValueConverterInterface $unitMeasurementValueConverter,
        ProductVariantInterface $productVariant,
        ProductInterface $product
    ): void {
        $unitMeasurementValueConverter->convert('23', 'KILOGRAM', null)->willReturn(23.0);

        $commerceChannel = new Channel();
        $commerceChannel->setCode('ecommerce');
        $supportChannel = new Channel();
        $supportChannel->setCode('support');
        $product->getChannels()->willReturn(new ArrayCollection([$commerceChannel, $supportChannel]));

        $productVariant->getProduct()->willReturn($product);
        $propertyAccessor->isWritable($productVariant, self::PROPERTY_PATH)->willReturn(true);
        $propertyAccessor->isWritable($product, self::PROPERTY_PATH)->willReturn(true);

        $this->beConstructedWith($propertyAccessor, $unitMeasurementValueConverter, self::AKENEO_ATTRIBUTE_CODE, self::PROPERTY_PATH);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(MetricPropertyValueHandler::class);
    }

    public function it_implements_value_handler_interface(): void
    {
        $this->shouldHaveType(ValueHandlerInterface::class);
    }

    public function it_supports_provided_akeneo_attribute_code_with_metrical_value(): void
    {
        $this->supports(new ProductVariant(), self::AKENEO_ATTRIBUTE_CODE, [self::KG_23_VALUE])->shouldReturn(true);
    }

    public function it_supports_provided_akeneo_attribute_code_with_metrical_values_related_to_different_channels(): void
    {
        $value = [
            [
                'scope' => 'print',
                'locale' => 'en_US',
                'data' => ['amount' => '21.0000', 'unit' => 'KILOGRAM'],
            ],
            [
                'scope' => 'ecommerce',
                'locale' => 'en_US',
                'data' => ['amount' => '23.0000', 'unit' => 'KILOGRAM'],
            ],
            [
                'scope' => 'paper_catalog',
                'locale' => 'en_US',
                'data' => ['amount' => '21.0000', 'unit' => 'KILOGRAM'],
            ],
        ];

        $this->supports(new ProductVariant(), self::AKENEO_ATTRIBUTE_CODE, $value)->shouldReturn(true);
    }

    public function it_does_not_support_any_other_attribute_except_provided_akeneo_attribute_code(): void
    {
        $this->supports(new ProductVariant(), 'another_attribute', [self::KG_23_VALUE])->shouldReturn(false);
    }

    public function it_does_not_support_data_with_one_metrical_value_and_one_non_metrical(): void
    {
        $this->supports(new ProductVariant(), self::AKENEO_ATTRIBUTE_CODE, [self::KG_23_VALUE, ['data' => 'altissimo']])->shouldReturn(false);
    }

    public function it_throws_trying_to_handle_not_supported_property(): void
    {
        $this
            ->shouldThrow(
                new \InvalidArgumentException(
                    sprintf(
                        'Cannot handle Akeneo attribute "%s". %s only supports Akeneo attribute "%s".',
                        'not_supported_property',
                        MetricPropertyValueHandler::class,
                        self::AKENEO_ATTRIBUTE_CODE
                    )
                )
            )
            ->during('handle', [new ProductVariant(), 'not_supported_property', []]);
    }

    public function it_sets_value_on_provided_property_path_on_both_product_and_product_variant(
        PropertyAccessorInterface $propertyAccessor,
        ProductVariantInterface $productVariant,
        ProductInterface $product
    ): void {
        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, [self::KG_23_VALUE]);

        $propertyAccessor->setValue($productVariant, self::PROPERTY_PATH, 23.0)->shouldHaveBeenCalled();
        $propertyAccessor->setValue($product, self::PROPERTY_PATH, 23.0)->shouldHaveBeenCalled();
    }

    public function it_sets_value_on_provided_property_path_on_variant_only_if_product_is_not_writable(
        PropertyAccessorInterface $propertyAccessor,
        ProductVariantInterface $productVariant,
        ProductInterface $product
    ): void {
        $propertyAccessor->isWritable($product, self::PROPERTY_PATH)->willReturn(false);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, [self::KG_23_VALUE]);

        $propertyAccessor->setValue($productVariant, self::PROPERTY_PATH, 23.0)->shouldHaveBeenCalled();
        $propertyAccessor->setValue($product, self::PROPERTY_PATH, 23.0)->shouldNotHaveBeenCalled();
    }

    public function it_sets_value_on_provided_property_path_on_product_only_if_variant_is_not_writable(
        PropertyAccessorInterface $propertyAccessor,
        ProductVariantInterface $productVariant,
        ProductInterface $product
    ): void {
        $propertyAccessor->isWritable($productVariant, self::PROPERTY_PATH)->willReturn(false);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, [self::KG_23_VALUE]);

        $propertyAccessor->setValue($productVariant, self::PROPERTY_PATH, 23.0)->shouldNotHaveBeenCalled();
        $propertyAccessor->setValue($product, self::PROPERTY_PATH, 23.0)->shouldHaveBeenCalled();
    }

    public function it_unset_value_if_value_is_null(
        PropertyAccessorInterface $propertyAccessor,
        ProductVariantInterface $productVariant,
        ProductInterface $product
    ): void {
        $propertyAccessor->isWritable($productVariant, self::PROPERTY_PATH)->willReturn(false);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, [
            ['locale' => null, 'scope' => null, 'data' => null],
        ]);

        $propertyAccessor->setValue($productVariant, self::PROPERTY_PATH, null)->shouldNotHaveBeenCalled();
        $propertyAccessor->setValue($product, self::PROPERTY_PATH, null)->shouldHaveBeenCalled();
    }

    public function it_throws_if_provided_property_path_is_not_writeable_on_both_product_and_variant(
        PropertyAccessorInterface $propertyAccessor,
        ProductVariantInterface $productVariant,
        ProductInterface $product
    ): void {
        $propertyAccessor->isWritable($productVariant, self::PROPERTY_PATH)->willReturn(false);
        $propertyAccessor->isWritable($product, self::PROPERTY_PATH)->willReturn(false);

        $this
            ->shouldThrow(
                new RuntimeException(
                    sprintf(
                        'Property path "%s" is not writable on both %s and %s but it should be for at least once.',
                        self::PROPERTY_PATH,
                        $productVariant->getWrappedObject()::class,
                        $product->getWrappedObject()::class
                    )
                )
            )
            ->during(
                'handle',
                [
                    $productVariant,
                    self::AKENEO_ATTRIBUTE_CODE,
                    [self::KG_23_VALUE],
                ]
            );
    }

    public function it_skips_values_related_to_channels_that_are_not_associated_to_the_product(
        PropertyAccessorInterface $propertyAccessor,
        ProductVariantInterface $productVariant,
        ProductInterface $product
    ): void {
        $value = [
            [
                'scope' => 'print',
                'locale' => 'en_US',
                'data' => ['amount' => '21.0000', 'unit' => 'KILOGRAM'],
            ],
            [
                'scope' => 'ecommerce',
                'locale' => 'en_US',
                'data' => ['amount' => '23.0000', 'unit' => 'KILOGRAM'],
            ],
            [
                'scope' => 'paper_catalog',
                'locale' => 'en_US',
                'data' => ['amount' => '21.0000', 'unit' => 'KILOGRAM'],
            ],
        ];

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, $value);

        $propertyAccessor->setValue($productVariant, self::PROPERTY_PATH, 23.0)->shouldHaveBeenCalled();
        $propertyAccessor->setValue($product, self::PROPERTY_PATH, 23.0)->shouldHaveBeenCalled();
        $propertyAccessor->setValue($productVariant, self::PROPERTY_PATH, 21.0)->shouldNotHaveBeenCalled();
        $propertyAccessor->setValue($product, self::PROPERTY_PATH, 21.0)->shouldNotHaveBeenCalled();
    }

    public function it_skips_subsequent_values_after_that_one_has_already_been_set_successfully(
        PropertyAccessorInterface $propertyAccessor,
        ProductVariantInterface $productVariant,
        ProductInterface $product
    ): void {
        $value = [
            [
                'scope' => 'ecommerce',
                'locale' => 'en_US',
                'data' => ['amount' => '23.0000', 'unit' => 'KILOGRAM'],
            ],
            [
                'scope' => 'ecommerce',
                'locale' => 'it_IT',
                'data' => ['amount' => '21.0000', 'unit' => 'KILOGRAM'],
            ],
        ];

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, $value);

        $propertyAccessor->setValue($productVariant, self::PROPERTY_PATH, 23.0)->shouldHaveBeenCalled();
        $propertyAccessor->setValue($product, self::PROPERTY_PATH, 23.0)->shouldHaveBeenCalled();
        $propertyAccessor->setValue($productVariant, self::PROPERTY_PATH, 21.0)->shouldNotHaveBeenCalled();
        $propertyAccessor->setValue($product, self::PROPERTY_PATH, 21.0)->shouldNotHaveBeenCalled();
    }

    public function it_throws_when_data_is_not_an_array(ProductVariantInterface $productVariant): void
    {
        $this
            ->shouldThrow(\InvalidArgumentException::class)
            ->during('handle', [$productVariant, self::AKENEO_ATTRIBUTE_CODE, [null]]);
    }

    public function it_throws_when_data_doesnt_contain_scope_info(ProductVariantInterface $productVariant): void
    {
        $this
            ->shouldThrow(new \InvalidArgumentException('Invalid Akeneo value data: required "scope" information was not found.',))
            ->during(
                'handle',
                [
                    $productVariant,
                    self::AKENEO_ATTRIBUTE_CODE,
                    [
                        [
                            'locale' => 'en_US',
                            'data' => ['amount' => '23.0000', 'unit' => 'KILOGRAM'],
                        ],
                    ],
                ]
            );
    }
}
