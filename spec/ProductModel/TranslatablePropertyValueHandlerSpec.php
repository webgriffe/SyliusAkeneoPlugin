<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\ProductModel;

use PhpSpec\ObjectBehavior;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Webgriffe\SyliusAkeneoPlugin\ProductModel\ValueHandlerInterface;

class TranslatablePropertyValueHandlerSpec extends ObjectBehavior
{
    private const AKENEO_ATTRIBUTE_CODE = 'akeneo_attribute_code';

    private const TRANSLATION_PROPERTY_PATH = 'translation_property_path';

    function let(
        PropertyAccessorInterface $propertyAccessor,
        FactoryInterface $productTranslationFactory,
        TranslationLocaleProviderInterface $localeProvider
    ) {
        $localeProvider->getDefinedLocalesCodes()->willReturn(['en_US', 'it_IT']);
        $this->beConstructedWith(
            $propertyAccessor,
            $productTranslationFactory,
            $localeProvider,
            self::AKENEO_ATTRIBUTE_CODE,
            self::TRANSLATION_PROPERTY_PATH
        );
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
        ProductTranslationInterface $englishProductTranslation,
        ProductTranslationInterface $italianProductTranslation,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $englishProductTranslation->getLocale()->willReturn('en_US');
        $italianProductTranslation->getLocale()->willReturn('it_IT');
        $product->getTranslation('en_US')->willReturn($englishProductTranslation);
        $product->getTranslation('it_IT')->willReturn($italianProductTranslation);

        $this->handle($product, self::AKENEO_ATTRIBUTE_CODE, [['locale' => null, 'scope' => null, 'data' => 'New value']]);

        $propertyAccessor->setValue($englishProductTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($italianProductTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
    }

    function it_creates_all_product_translations_when_not_existing_and_locale_not_specified(
        ProductInterface $product,
        ProductTranslationInterface $existentEnglishProductTranslation,
        PropertyAccessorInterface $propertyAccessor,
        FactoryInterface $productTranslationFactory,
        ProductTranslationInterface $newProductTranslation
    ) {
        $existentEnglishProductTranslation->getLocale()->willReturn('en_US');
        $product->getTranslation('en_US')->willReturn($existentEnglishProductTranslation);
        $product->getTranslation('it_IT')->willReturn($existentEnglishProductTranslation);
        $productTranslationFactory->createNew()->willReturn($newProductTranslation);

        $this->handle($product, self::AKENEO_ATTRIBUTE_CODE, [['locale' => null, 'scope' => null, 'data' => 'New value']]);

        $propertyAccessor->setValue($existentEnglishProductTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($newProductTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
        $newProductTranslation->setLocale('it_IT')->shouldHaveBeenCalled();
        $product->addTranslation($newProductTranslation)->shouldHaveBeenCalled();
    }
}
