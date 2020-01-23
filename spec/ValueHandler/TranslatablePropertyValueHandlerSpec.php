<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use PhpSpec\ObjectBehavior;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductVariantTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class TranslatablePropertyValueHandlerSpec extends ObjectBehavior
{
    private const AKENEO_ATTRIBUTE_CODE = 'akeneo_attribute_code';

    private const TRANSLATION_PROPERTY_PATH = 'translation_property_path';

    function let(
        PropertyAccessorInterface $propertyAccessor,
        FactoryInterface $productTranslationFactory,
        FactoryInterface $productVariantTranslationFactory,
        TranslationLocaleProviderInterface $localeProvider
    ) {
        $localeProvider->getDefinedLocalesCodes()->willReturn(['en_US', 'it_IT']);
        $this->beConstructedWith(
            $propertyAccessor,
            $productTranslationFactory,
            $productVariantTranslationFactory,
            $localeProvider,
            self::AKENEO_ATTRIBUTE_CODE,
            self::TRANSLATION_PROPERTY_PATH
        );
    }

    function it_implements_value_handler_interface()
    {
        $this->shouldHaveType(\Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface::class);
    }

    function it_supports_provided_akeneo_attribute_code(ProductVariantInterface $productVariant)
    {
        $this->supports($productVariant, self::AKENEO_ATTRIBUTE_CODE, [])->shouldReturn(true);
    }

    function it_supports_any_product_variant_subject(ProductVariantInterface $productVariant)
    {
        $this->supports($productVariant, self::AKENEO_ATTRIBUTE_CODE, [])->shouldReturn(true);
    }

    function it_does_not_support_any_other_attribute_except_provided_akeneo_attribute_code(ProductVariantInterface $productVariant)
    {
        $this->supports($productVariant, 'another_attribute', [])->shouldReturn(false);
    }

    function it_does_not_support_any_other_type_of_subject()
    {
        $this->supports(new \stdClass(), self::AKENEO_ATTRIBUTE_CODE, [])->shouldReturn(false);
    }

    function it_throws_when_handling_not_product_variant_subject()
    {
        $this
            ->shouldThrow(
                new \InvalidArgumentException(
                    sprintf(
                        'This translatable property value handler only support instances of %s, %s given.',
                        ProductVariantInterface::class,
                        \stdClass::class
                    )
                )
            )
            ->during('handle', [new \stdClass(), self::AKENEO_ATTRIBUTE_CODE, []]);
    }

    function it_sets_value_on_both_product_and_product_variant_translation(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductTranslationInterface $productTranslation,
        ProductVariantTranslationInterface $productVariantTranslation,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $productVariant->getProduct()->willReturn($product);
        $productVariant->getTranslation('en_US')->willReturn($productVariantTranslation);
        $productVariantTranslation->getLocale()->willReturn('en_US');
        $productVariantTranslation->getTranslatable()->willReturn($productVariant);
        $product->getTranslation('en_US')->shouldBeCalled()->willReturn($productTranslation);
        $productTranslation->getLocale()->willReturn('en_US');
        $propertyAccessor->isWritable($productTranslation, self::TRANSLATION_PROPERTY_PATH)->willReturn(true);
        $propertyAccessor->isWritable($productVariantTranslation, self::TRANSLATION_PROPERTY_PATH)->willReturn(true);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, [['locale' => 'en_US', 'scope' => null, 'data' => 'New value']]);

        $propertyAccessor->setValue($productVariantTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($productTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
    }

    function it_creates_product_variant_translation_if_it_does_not_exists(
        ProductVariantInterface $productVariant,
        ProductVariantTranslationInterface $fallbackProductVariantTranslation,
        ProductVariantTranslationInterface $newProductVariantTranslation,
        ProductInterface $product,
        ProductTranslationInterface $productTranslation,
        PropertyAccessorInterface $propertyAccessor,
        FactoryInterface $productVariantTranslationFactory
    ) {
        $productVariant->getProduct()->willReturn($product);
        $productVariant->getTranslation('it_IT')->willReturn($fallbackProductVariantTranslation);
        $fallbackProductVariantTranslation->getLocale()->willReturn('en_US');
        $productVariantTranslationFactory->createNew()->willReturn($newProductVariantTranslation);
        $newProductVariantTranslation->getTranslatable()->willReturn($productVariant);
        $newProductVariantTranslation->getLocale()->willReturn('it_IT');
        $product->getTranslation('it_IT')->willReturn($productTranslation);
        $productTranslation->getLocale()->willReturn('it_IT');
        $propertyAccessor->isWritable($newProductVariantTranslation, self::TRANSLATION_PROPERTY_PATH)->willReturn(true);
        $propertyAccessor->isWritable($productTranslation, self::TRANSLATION_PROPERTY_PATH)->willReturn(true);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, [['locale' => 'it_IT', 'scope' => null, 'data' => 'New value']]);

        $productVariantTranslationFactory->createNew()->shouldHaveBeenCalled();
        $newProductVariantTranslation->setLocale('it_IT')->shouldHaveBeenCalled();
        $productVariant->addTranslation($newProductVariantTranslation)->shouldHaveBeenCalled();
        $propertyAccessor->setValue($newProductVariantTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($productTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
    }

    function it_creates_product_translation_if_it_does_not_exists(
        ProductVariantInterface $productVariant,
        ProductVariantTranslationInterface $productVariantTranslation,
        ProductInterface $product,
        ProductTranslationInterface $fallbackProductTranslation,
        PropertyAccessorInterface $propertyAccessor,
        FactoryInterface $productTranslationFactory,
        ProductTranslationInterface $newProductTranslation
    ) {
        $productVariant->getProduct()->willReturn($product);
        $productVariant->getTranslation('it_IT')->willReturn($productVariantTranslation);
        $productVariantTranslation->getLocale()->willReturn('it_IT');
        $productVariantTranslation->getTranslatable()->willReturn($productVariant);
        $fallbackProductTranslation->getLocale()->willReturn('en_US');
        $product->getTranslation('it_IT')->willReturn($fallbackProductTranslation);
        $productTranslationFactory->createNew()->willReturn($newProductTranslation);
        $propertyAccessor->isWritable($productVariantTranslation, self::TRANSLATION_PROPERTY_PATH)->willReturn(true);
        $propertyAccessor->isWritable($newProductTranslation, self::TRANSLATION_PROPERTY_PATH)->willReturn(true);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, [['locale' => 'it_IT', 'scope' => null, 'data' => 'New value']]);

        $productTranslationFactory->createNew()->shouldHaveBeenCalled();
        $newProductTranslation->setLocale('it_IT')->shouldHaveBeenCalled();
        $product->addTranslation($newProductTranslation)->shouldHaveBeenCalled();
        $propertyAccessor->setValue($productVariantTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($newProductTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
    }

    function it_sets_value_on_all_product_translations_when_locale_not_specified(
        ProductVariantInterface $productVariant,
        ProductVariantTranslationInterface $englishProductVariantTranslation,
        ProductVariantTranslationInterface $italianProductVariantTranslation,
        ProductInterface $product,
        ProductTranslationInterface $englishProductTranslation,
        ProductTranslationInterface $italianProductTranslation,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $productVariant->getProduct()->willReturn($product);
        $englishProductVariantTranslation->getLocale()->willReturn('en_US');
        $italianProductVariantTranslation->getLocale()->willReturn('it_IT');
        $englishProductVariantTranslation->getTranslatable()->willReturn($productVariant);
        $italianProductVariantTranslation->getTranslatable()->willReturn($productVariant);
        $productVariant->getTranslation('en_US')->willReturn($englishProductVariantTranslation);
        $productVariant->getTranslation('it_IT')->willReturn($italianProductVariantTranslation);
        $englishProductTranslation->getLocale()->willReturn('en_US');
        $italianProductTranslation->getLocale()->willReturn('it_IT');
        $product->getTranslation('en_US')->willReturn($englishProductTranslation);
        $product->getTranslation('it_IT')->willReturn($italianProductTranslation);
        $propertyAccessor->isWritable($englishProductVariantTranslation, self::TRANSLATION_PROPERTY_PATH)->willReturn(true);
        $propertyAccessor->isWritable($italianProductVariantTranslation, self::TRANSLATION_PROPERTY_PATH)->willReturn(true);
        $propertyAccessor->isWritable($englishProductTranslation, self::TRANSLATION_PROPERTY_PATH)->willReturn(true);
        $propertyAccessor->isWritable($italianProductTranslation, self::TRANSLATION_PROPERTY_PATH)->willReturn(true);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, [['locale' => null, 'scope' => null, 'data' => 'New value']]);

        $propertyAccessor->setValue($englishProductVariantTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($italianProductVariantTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($englishProductTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($italianProductTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
    }

    function it_throws_exception_when_property_path_is_not_writable_on_both_product_and_variant_translation(
        ProductVariantInterface $productVariant,
        ProductVariantTranslationInterface $productVariantTranslation,
        PropertyAccessorInterface $propertyAccessor,
        ProductInterface $product,
        ProductTranslationInterface $productTranslation
    ) {
        $productVariantTranslation->getLocale()->willReturn('en_US');
        $productVariant->getTranslation('en_US')->shouldBeCalled()->willReturn($productVariantTranslation);
        $propertyAccessor->isWritable($productVariantTranslation, self::TRANSLATION_PROPERTY_PATH)->willReturn(false);
        $productVariantTranslation->getTranslatable()->willReturn($productVariant);
        $productVariant->getProduct()->willReturn($product);
        $product->getTranslation('en_US')->willReturn($productTranslation);
        $productTranslation->getLocale()->willReturn('en_US');
        $propertyAccessor->isWritable($productTranslation, self::TRANSLATION_PROPERTY_PATH)->willReturn(false);

        $this
            ->shouldThrow(
                new \RuntimeException(
                    sprintf(
                        'Property path "%s" is not writable on both %s and %s but it should be for at least once.',
                        self::TRANSLATION_PROPERTY_PATH,
                        ProductVariantTranslationInterface::class,
                        ProductTranslationInterface::class
                    )
                )
            )
            ->during('handle',
                [
                    $productVariant,
                    self::AKENEO_ATTRIBUTE_CODE,
                    [['locale' => 'en_US', 'scope' => null, 'data' => 'New value']],
                ]
            );
    }
}
