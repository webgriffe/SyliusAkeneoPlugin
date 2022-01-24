<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use PhpSpec\ObjectBehavior;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\GenericPropertyValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;

class GenericPropertyValueHandlerSpec extends ObjectBehavior
{
    private const AKENEO_ATTRIBUTE_CODE = 'akeneo_attribute_code';

    private const PROPERTY_PATH = 'property_path';

    function let(PropertyAccessorInterface $propertyAccessor)
    {
        $this->beConstructedWith($propertyAccessor, self::AKENEO_ATTRIBUTE_CODE, self::PROPERTY_PATH);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(GenericPropertyValueHandler::class);
    }

    function it_implements_value_handler_interface()
    {
        $this->shouldHaveType(ValueHandlerInterface::class);
    }

    function it_supports_provided_akeneo_attribute_code()
    {
        $this->supports(new ProductVariant(), self::AKENEO_ATTRIBUTE_CODE, [])->shouldReturn(true);
    }

    function it_does_not_support_any_other_attribute_except_provided_akeneo_attribute_code()
    {
        $this->supports(new ProductVariant(), 'another_attribute', [])->shouldReturn(false);
    }

    function it_throws_trying_to_handle_not_supported_property()
    {
        $this
            ->shouldThrow(
                new \InvalidArgumentException(
                    sprintf(
                        'Cannot handle Akeneo attribute "%s". %s only supports Akeneo attribute "%s".',
                        'not_supported_property',
                        GenericPropertyValueHandler::class,
                        self::AKENEO_ATTRIBUTE_CODE
                    )
                )
            )
            ->during('handle', [new ProductVariant(), 'not_supported_property', []]);
    }

    function it_sets_value_on_provided_property_path_on_both_product_and_product_variant(
        PropertyAccessorInterface $propertyAccessor,
        ProductVariantInterface $productVariant,
        ProductInterface $product
    ) {
        $productVariant->getProduct()->willReturn($product);
        $propertyAccessor->isWritable($productVariant, self::PROPERTY_PATH)->willReturn(true);
        $propertyAccessor->isWritable($product, self::PROPERTY_PATH)->willReturn(true);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, [['locale' => null, 'scope' => null, 'data' => 'New value']]);

        $propertyAccessor->setValue($productVariant, self::PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($product, self::PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
    }

    function it_sets_value_on_provided_property_path_on_variant_only_if_product_is_not_writable(
        PropertyAccessorInterface $propertyAccessor,
        ProductVariantInterface $productVariant,
        ProductInterface $product
    ) {
        $productVariant->getProduct()->willReturn($product);
        $propertyAccessor->isWritable($productVariant, self::PROPERTY_PATH)->willReturn(true);
        $propertyAccessor->isWritable($product, self::PROPERTY_PATH)->willReturn(false);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, [['locale' => null, 'scope' => null, 'data' => 'New value']]);

        $propertyAccessor->setValue($productVariant, self::PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($product, self::PROPERTY_PATH, 'New value')->shouldNotHaveBeenCalled();
    }

    function it_sets_value_on_provided_property_path_on_product_only_if_variant_is_not_writable(
        PropertyAccessorInterface $propertyAccessor,
        ProductVariantInterface $productVariant,
        ProductInterface $product
    ) {
        $productVariant->getProduct()->willReturn($product);
        $propertyAccessor->isWritable($productVariant, self::PROPERTY_PATH)->willReturn(false);
        $propertyAccessor->isWritable($product, self::PROPERTY_PATH)->willReturn(true);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, [['locale' => null, 'scope' => null, 'data' => 'New value']]);

        $propertyAccessor->setValue($productVariant, self::PROPERTY_PATH, 'New value')->shouldNotHaveBeenCalled();
        $propertyAccessor->setValue($product, self::PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
    }

    function it_throws_if_provided_property_path_is_not_writeable_on_both_product_and_variant(
        PropertyAccessorInterface $propertyAccessor,
        ProductVariantInterface $productVariant,
        ProductInterface $product
    ) {
        $productVariant->getProduct()->willReturn($product);
        $propertyAccessor->isWritable($productVariant, self::PROPERTY_PATH)->willReturn(false);
        $propertyAccessor->isWritable($product, self::PROPERTY_PATH)->willReturn(false);

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
                    [['locale' => null, 'scope' => null, 'data' => 'New value']],
                ]
            )
        ;
    }
}
