<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Webgriffe\SyliusAkeneoPlugin\Converter\DefaultUnitMeasurementValueConverterInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\MetricPropertyValueHandler;
use PhpSpec\ObjectBehavior;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;

class MetricPropertyValueHandlerSpec extends ObjectBehavior
{
    private const AKENEO_ATTRIBUTE_CODE = 'akeneo_attribute_code';

    private const PROPERTY_PATH = 'property_path';
    const KG_23_VALUE = [0 => ['data' => ['amount' => 23, 'unit' => 'KILOGRAM']]];

    public function let(
        PropertyAccessorInterface $propertyAccessor,
        DefaultUnitMeasurementValueConverterInterface $defaultUnitMeasurementValueConverter
    ) {
        $this->beConstructedWith($propertyAccessor, $defaultUnitMeasurementValueConverter, self::AKENEO_ATTRIBUTE_CODE, self::PROPERTY_PATH);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(MetricPropertyValueHandler::class);
    }

    function it_implements_value_handler_interface()
    {
        $this->shouldHaveType(ValueHandlerInterface::class);
    }

    function it_supports_provided_akeneo_attribute_code_with_metrical_value()
    {
        $this->supports(new ProductVariant(), self::AKENEO_ATTRIBUTE_CODE, self::KG_23_VALUE)->shouldReturn(true);
    }

    function it_does_not_support_any_other_attribute_except_provided_akeneo_attribute_code()
    {
        $this->supports(new ProductVariant(), 'another_attribute', self::KG_23_VALUE)->shouldReturn(false);
    }

    function it_does_not_support_any_other_attribute_value_except_metrical()
    {
        $this->supports(new ProductVariant(), self::AKENEO_ATTRIBUTE_CODE, [0 => ['data' => 2]])->shouldReturn(false);
    }

    function it_throws_trying_to_handle_not_supported_property()
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

    function it_sets_value_on_provided_property_path_on_both_product_and_product_variant(
        PropertyAccessorInterface $propertyAccessor,
        ProductVariantInterface $productVariant,
        DefaultUnitMeasurementValueConverterInterface $defaultUnitMeasurementValueConverter,
        ProductInterface $product
    ) {
        $productVariant->getProduct()->willReturn($product);
        $propertyAccessor->isWritable($productVariant, self::PROPERTY_PATH)->willReturn(true);
        $propertyAccessor->isWritable($product, self::PROPERTY_PATH)->willReturn(true);
        $defaultUnitMeasurementValueConverter->convert('23', 'KILOGRAM', null)->willReturn(23.0);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, self::KG_23_VALUE);

        $propertyAccessor->setValue($productVariant, self::PROPERTY_PATH, 23.0)->shouldHaveBeenCalled();
        $propertyAccessor->setValue($product, self::PROPERTY_PATH, 23.0)->shouldHaveBeenCalled();
    }

    function it_sets_value_on_provided_property_path_on_variant_only_if_product_is_not_writable(
        PropertyAccessorInterface $propertyAccessor,
        ProductVariantInterface $productVariant,
        DefaultUnitMeasurementValueConverterInterface $defaultUnitMeasurementValueConverter,
        ProductInterface $product
    ) {
        $productVariant->getProduct()->willReturn($product);
        $propertyAccessor->isWritable($productVariant, self::PROPERTY_PATH)->willReturn(true);
        $propertyAccessor->isWritable($product, self::PROPERTY_PATH)->willReturn(false);
        $defaultUnitMeasurementValueConverter->convert('23', 'KILOGRAM', null)->willReturn(23.0);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, self::KG_23_VALUE);

        $propertyAccessor->setValue($productVariant, self::PROPERTY_PATH, 23.0)->shouldHaveBeenCalled();
        $propertyAccessor->setValue($product, self::PROPERTY_PATH, 23.0)->shouldNotHaveBeenCalled();
    }

    function it_sets_value_on_provided_property_path_on_product_only_if_variant_is_not_writable(
        PropertyAccessorInterface $propertyAccessor,
        ProductVariantInterface $productVariant,
        DefaultUnitMeasurementValueConverterInterface $defaultUnitMeasurementValueConverter,
        ProductInterface $product
    ) {
        $productVariant->getProduct()->willReturn($product);
        $propertyAccessor->isWritable($productVariant, self::PROPERTY_PATH)->willReturn(false);
        $propertyAccessor->isWritable($product, self::PROPERTY_PATH)->willReturn(true);
        $defaultUnitMeasurementValueConverter->convert('23', 'KILOGRAM', null)->willReturn(23.0);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, self::KG_23_VALUE);

        $propertyAccessor->setValue($productVariant, self::PROPERTY_PATH, 23.0)->shouldNotHaveBeenCalled();
        $propertyAccessor->setValue($product, self::PROPERTY_PATH, 23.0)->shouldHaveBeenCalled();
    }

    function it_throws_if_provided_property_path_is_not_writeable_on_both_product_and_variant(
        PropertyAccessorInterface $propertyAccessor,
        ProductVariantInterface $productVariant,
        DefaultUnitMeasurementValueConverterInterface $defaultUnitMeasurementValueConverter,
        ProductInterface $product
    ) {
        $productVariant->getProduct()->willReturn($product);
        $propertyAccessor->isWritable($productVariant, self::PROPERTY_PATH)->willReturn(false);
        $propertyAccessor->isWritable($product, self::PROPERTY_PATH)->willReturn(false);
        $defaultUnitMeasurementValueConverter->convert('23', 'KILOGRAM', null)->willReturn(23.0);

        $this
            ->shouldThrow(
                new \RuntimeException(
                    sprintf(
                        'Property path "%s" is not writable on both %s and %s but it should be for at least once.',
                        self::PROPERTY_PATH,
                        get_class($productVariant->getWrappedObject()),
                        get_class($product->getWrappedObject())
                    )
                )
            )
            ->during(
                'handle',
                [
                    $productVariant,
                    self::AKENEO_ATTRIBUTE_CODE,
                    self::KG_23_VALUE
                ]
            )
        ;
    }
}
