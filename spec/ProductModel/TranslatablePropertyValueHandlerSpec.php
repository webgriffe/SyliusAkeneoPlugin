<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\ProductModel;

use Doctrine\Common\Collections\ArrayCollection;
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

    function let(PropertyAccessorInterface $propertyAccessor, FactoryInterface $productTranslationFactory)
    {
        $this->beConstructedWith($propertyAccessor, $productTranslationFactory, self::AKENEO_ATTRIBUTE_CODE, self::TRANSLATION_PROPERTY_PATH);
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

    function it_sets_value_on_product_translation(
        ProductInterface $product,
        ProductTranslationInterface $productTranslation,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $productTranslation->getLocale()->willReturn('en_US');
        $product->getTranslation('en_US')->shouldBeCalled()->willReturn($productTranslation);

        $this->handle($product, self::AKENEO_ATTRIBUTE_CODE, [['locale' => 'en_US', 'scope' => null, 'data' => 'New value']]);

        $propertyAccessor->setValue($productTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
    }

    function it_creates_product_translation_if_it_not_exists(
        ProductInterface $product,
        ProductTranslationInterface $fallbackProductTranslation,
        PropertyAccessorInterface $propertyAccessor,
        FactoryInterface $productTranslationFactory,
        ProductTranslationInterface $newProductTranslation
    ) {
        $fallbackProductTranslation->getLocale()->willReturn('en_US');
        $product->getTranslation('it_IT')->shouldBeCalled()->willReturn($fallbackProductTranslation);
        $productTranslationFactory->createNew()->shouldBeCalled()->willReturn($newProductTranslation);
        $product->addTranslation($newProductTranslation)->shouldBeCalled();

        $this->handle($product, self::AKENEO_ATTRIBUTE_CODE, [['locale' => 'it_IT', 'scope' => null, 'data' => 'New value']]);

        $propertyAccessor->setValue($newProductTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
    }


    function it_sets_value_on_all_product_translations_when_locale_not_specified(
        ProductInterface $product,
        ProductTranslationInterface $productTranslation1,
        ProductTranslationInterface $productTranslation2,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $product->getTranslations()->willReturn(new ArrayCollection([$productTranslation1, $productTranslation2]));

        $this->handle($product, self::AKENEO_ATTRIBUTE_CODE, [['locale' => null, 'scope' => null, 'data' => 'New value']]);

        $propertyAccessor->setValue($productTranslation1, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($productTranslation2, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
    }
}
