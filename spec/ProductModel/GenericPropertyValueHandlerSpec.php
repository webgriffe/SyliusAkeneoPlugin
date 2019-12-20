<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\ProductModel;

use PhpSpec\ObjectBehavior;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Webgriffe\SyliusAkeneoPlugin\ProductModel\GenericPropertyValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ProductModel\ValueHandlerInterface;

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
        $this->supports(new Product(), self::AKENEO_ATTRIBUTE_CODE, [])->shouldReturn(true);
    }

    function it_does_not_support_any_other_attribute_except_provided_akeneo_attribute_code()
    {
        $this->supports(new Product(), 'another_attribute', [])->shouldReturn(false);
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
            ->during('handle', [new Product(), 'not_supported_property', []]);
    }

    function it_sets_value_on_provided_property_path(
        PropertyAccessorInterface $propertyAccessor,
        ProductInterface $product
    ) {
        $this->handle($product, self::AKENEO_ATTRIBUTE_CODE, [['locale' => null, 'scope' => null, 'data' => 'New value']]);
        $propertyAccessor->setValue($product, self::PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
    }
}
