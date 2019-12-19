<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\ProductModel;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslation;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Webgriffe\SyliusAkeneoPlugin\ProductModel\TranslatablePropertyValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ProductModel\ValueHandlerInterface;

class TranslatablePropertyValueHandlerSpec extends ObjectBehavior
{
    private const AKENEO_ATTRIBUTE_CODE = 'akeneo_attribute_code';
    private const TRANSLATION_PROPERTY_PATH = 'translation_property_path';

    function let(FactoryInterface $productTranslationFactory, PropertyAccessorInterface $propertyAccessor)
    {
        $this->beConstructedWith($productTranslationFactory, $propertyAccessor, self::AKENEO_ATTRIBUTE_CODE, self::TRANSLATION_PROPERTY_PATH);
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

    function it_throws_when_handling_not_supported_attribute()
    {
        $this->shouldThrow(new \InvalidArgumentException('Cannot handle'))->during('handle', [new Product(), 'not_supported_attribute', []]);
    }

    function it_sets_value_on_an_already_existent_product_translation(
        FactoryInterface $productTranslationFactory,
        ProductTranslationInterface $existingProductTranslation,
        ProductTranslationInterface $newProductTranslation,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $productTranslationFactory->createNew()->willReturn($newProductTranslation);
        $product = new Product();
        $product->addTranslation($existingProductTranslation->getWrappedObject());

        $this->handle($product, self::AKENEO_ATTRIBUTE_CODE, [['locale' => 'en_US', 'scope' => null, 'data' => 'New value']]);

        $propertyAccessor->setValue($existingProductTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
    }

    function it_sets_value_on_a_not_existent_product_translation(
        FactoryInterface $productTranslationFactory,
        ProductTranslationInterface $newProductTranslation,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $productTranslationFactory->createNew()->willReturn($newProductTranslation);
        $product = new Product();

        $this->handle($product, self::AKENEO_ATTRIBUTE_CODE, [['locale' => 'en_US', 'scope' => null, 'data' => 'New value']]);

        $propertyAccessor->setValue($newProductTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
    }
}
