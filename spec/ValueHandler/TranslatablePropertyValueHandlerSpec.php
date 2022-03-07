<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use RuntimeException;
use stdClass;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductVariantTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;

class TranslatablePropertyValueHandlerSpec extends ObjectBehavior
{
    private const AKENEO_ATTRIBUTE_CODE = 'akeneo_attribute_code';

    private const TRANSLATION_PROPERTY_PATH = 'translation_property_path';

    public function let(
        PropertyAccessorInterface $propertyAccessor,
        FactoryInterface $productTranslationFactory,
        FactoryInterface $productVariantTranslationFactory,
        TranslationLocaleProviderInterface $localeProvider,
        ProductVariantInterface $productVariant,
        ProductVariantTranslationInterface $englishProductVariantTranslation,
        ProductVariantTranslationInterface $italianProductVariantTranslation,
        ProductInterface $product,
        ProductTranslationInterface $englishProductTranslation,
        ProductTranslationInterface $italianProductTranslation
    ): void {
        $propertyAccessor->isWritable($englishProductVariantTranslation, self::TRANSLATION_PROPERTY_PATH)->willReturn(true);
        $propertyAccessor->isWritable($italianProductVariantTranslation, self::TRANSLATION_PROPERTY_PATH)->willReturn(true);
        $propertyAccessor->isWritable($englishProductTranslation, self::TRANSLATION_PROPERTY_PATH)->willReturn(true);
        $propertyAccessor->isWritable($italianProductTranslation, self::TRANSLATION_PROPERTY_PATH)->willReturn(true);

        $localeProvider->getDefinedLocalesCodes()->willReturn(['en_US', 'it_IT']);

        $productVariant->getProduct()->willReturn($product);
        $commerceChannel = new Channel();
        $commerceChannel->setCode('ecommerce');
        $supportChannel = new Channel();
        $supportChannel->setCode('support');
        $product->getChannels()->willReturn(new ArrayCollection([$commerceChannel, $supportChannel]));
        $localeProvider->getDefinedLocalesCodes()->willReturn(['en_US', 'it_IT']);

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

        $this->beConstructedWith(
            $propertyAccessor,
            $productTranslationFactory,
            $productVariantTranslationFactory,
            $localeProvider,
            self::AKENEO_ATTRIBUTE_CODE,
            self::TRANSLATION_PROPERTY_PATH
        );
    }

    public function it_implements_value_handler_interface(): void
    {
        $this->shouldHaveType(ValueHandlerInterface::class);
    }

    public function it_supports_provided_akeneo_attribute_code(ProductVariantInterface $productVariant): void
    {
        $this->supports($productVariant, self::AKENEO_ATTRIBUTE_CODE, [])->shouldReturn(true);
    }

    public function it_supports_any_product_variant_subject(ProductVariantInterface $productVariant): void
    {
        $this->supports($productVariant, self::AKENEO_ATTRIBUTE_CODE, [])->shouldReturn(true);
    }

    public function it_does_not_support_any_other_attribute_except_provided_akeneo_attribute_code(ProductVariantInterface $productVariant): void
    {
        $this->supports($productVariant, 'another_attribute', [])->shouldReturn(false);
    }

    public function it_does_not_support_any_other_type_of_subject(): void
    {
        $this->supports(new stdClass(), self::AKENEO_ATTRIBUTE_CODE, [])->shouldReturn(false);
    }

    public function it_throws_when_handling_not_product_variant_subject(): void
    {
        $this
            ->shouldThrow(
                new InvalidArgumentException(
                    sprintf(
                        'This translatable property value handler only support instances of %s, %s given.',
                        ProductVariantInterface::class,
                        stdClass::class
                    )
                )
            )
            ->during('handle', [new stdClass(), self::AKENEO_ATTRIBUTE_CODE, []]);
    }

    public function it_sets_value_on_both_product_and_product_variant_translation(
        ProductVariantInterface $productVariant,
        ProductVariantTranslationInterface $englishProductVariantTranslation,
        ProductTranslationInterface $englishProductTranslation,
        PropertyAccessorInterface $propertyAccessor
    ): void {
        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, [['locale' => 'en_US', 'scope' => null, 'data' => 'New value']]);

        $propertyAccessor->setValue($englishProductVariantTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($englishProductTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
    }

    public function it_creates_product_variant_translation_if_it_does_not_exists_but_product_translation_exists(
        ProductVariantInterface $productVariant,
        ProductVariantTranslationInterface $fallbackProductVariantTranslation,
        ProductVariantTranslationInterface $italianProductVariantTranslation,
        ProductTranslationInterface $italianProductTranslation,
        PropertyAccessorInterface $propertyAccessor,
        FactoryInterface $productVariantTranslationFactory
    ): void {
        $productVariant->getTranslation('it_IT')->willReturn($fallbackProductVariantTranslation);
        $fallbackProductVariantTranslation->getLocale()->willReturn('en_US');
        $productVariantTranslationFactory->createNew()->willReturn($italianProductVariantTranslation);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, [['locale' => 'it_IT', 'scope' => null, 'data' => 'New value']]);

        $productVariantTranslationFactory->createNew()->shouldHaveBeenCalled();
        $italianProductVariantTranslation->setLocale('it_IT')->shouldHaveBeenCalled();
        $productVariant->addTranslation($italianProductVariantTranslation)->shouldHaveBeenCalled();
        $propertyAccessor->setValue($italianProductVariantTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($italianProductTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
    }

    public function it_creates_product_translation_if_it_does_not_exists_but_product_variant_translation_exists(
        ProductVariantInterface $productVariant,
        ProductVariantTranslationInterface $italianProductVariantTranslation,
        ProductInterface $product,
        ProductTranslationInterface $fallbackProductTranslation,
        ProductTranslationInterface $italianProductTranslation,
        PropertyAccessorInterface $propertyAccessor,
        FactoryInterface $productTranslationFactory
    ): void {
        $product->getTranslation('it_IT')->willReturn($fallbackProductTranslation);
        $fallbackProductTranslation->getLocale()->willReturn('en_US');
        $productTranslationFactory->createNew()->willReturn($italianProductTranslation);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, [['locale' => 'it_IT', 'scope' => null, 'data' => 'New value']]);

        $productTranslationFactory->createNew()->shouldHaveBeenCalled();
        $italianProductTranslation->setLocale('it_IT')->shouldHaveBeenCalled();
        $product->addTranslation($italianProductTranslation)->shouldHaveBeenCalled();
        $propertyAccessor->setValue($italianProductVariantTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($italianProductTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
    }

    public function it_skips_locales_not_specified_in_sylius(
        ProductVariantInterface $productVariant,
        ProductVariantTranslationInterface $productVariantTranslation,
        ProductTranslationInterface $productTranslation,
        PropertyAccessorInterface $propertyAccessor
    ): void {
        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, [['locale' => 'es_ES', 'scope' => null, 'data' => 'New value']]);

        $propertyAccessor->setValue($productVariantTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldNotHaveBeenCalled();
        $propertyAccessor->setValue($productTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldNotHaveBeenCalled();
    }

    public function it_sets_value_on_all_product_translations_when_locale_not_specified(
        ProductVariantInterface $productVariant,
        ProductVariantTranslationInterface $englishProductVariantTranslation,
        ProductVariantTranslationInterface $italianProductVariantTranslation,
        ProductTranslationInterface $englishProductTranslation,
        ProductTranslationInterface $italianProductTranslation,
        PropertyAccessorInterface $propertyAccessor
    ): void {
        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, [['locale' => null, 'scope' => null, 'data' => 'New value']]);

        $propertyAccessor->setValue($englishProductVariantTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($italianProductVariantTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($englishProductTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($italianProductTranslation, self::TRANSLATION_PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
    }

    public function it_throws_exception_when_property_path_is_not_writable_on_both_product_and_variant_translation(
        ProductVariantInterface $productVariant,
        ProductVariantTranslationInterface $englishProductVariantTranslation,
        ProductInterface $product,
        ProductTranslationInterface $englishProductTranslation,
        PropertyAccessorInterface $propertyAccessor
    ): void {
        $productVariant->getTranslation('en_US')->shouldBeCalled()->willReturn($englishProductVariantTranslation);
        $propertyAccessor->isWritable($englishProductVariantTranslation, self::TRANSLATION_PROPERTY_PATH)->willReturn(false);
        $product->getTranslation('en_US')->shouldBeCalled()->willReturn($englishProductTranslation);
        $propertyAccessor->isWritable($englishProductTranslation, self::TRANSLATION_PROPERTY_PATH)->willReturn(false);

        $this
            ->shouldThrow(
                new RuntimeException(
                    sprintf(
                        'Property path "%s" is not writable on both %s and %s but it should be for at least once.',
                        self::TRANSLATION_PROPERTY_PATH,
                        ProductVariantTranslationInterface::class,
                        ProductTranslationInterface::class
                    )
                )
            )
            ->during(
                'handle',
                [
                    $productVariant,
                    self::AKENEO_ATTRIBUTE_CODE,
                    [['locale' => 'en_US', 'scope' => null, 'data' => 'New value']],
                ]
            );
    }

    public function it_does_not_create_translations_for_null_values_but_it_sets_null_on_existing_ones(
        ProductVariantInterface $productVariant,
        ProductVariantTranslationInterface $englishProductVariantTranslation,
        ProductVariantTranslationInterface $englishProductTranslation,
        ProductInterface $product,
        PropertyAccessorInterface $propertyAccessor,
        FactoryInterface $productTranslationFactory,
        FactoryInterface $productVariantTranslationFactory
    ): void {
        $productVariant->getTranslations()->willReturn(new ArrayCollection(['en_US' => $englishProductVariantTranslation->getWrappedObject()]));
        $product->getTranslations()->willReturn(new ArrayCollection(['en_US' => $englishProductTranslation->getWrappedObject()]));

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, [['locale' => null, 'scope' => null, 'data' => null]]);

        $propertyAccessor->setValue($englishProductVariantTranslation, self::TRANSLATION_PROPERTY_PATH, null)->shouldHaveBeenCalled();
        $propertyAccessor->setValue($englishProductTranslation, self::TRANSLATION_PROPERTY_PATH, null)->shouldHaveBeenCalled();

        $productVariant->addTranslation(Argument::any())->shouldNotHaveBeenCalled();
        $product->addTranslation(Argument::any())->shouldNotHaveBeenCalled();
        $productTranslationFactory->createNew()->shouldNotHaveBeenCalled();
        $productVariantTranslationFactory->createNew()->shouldNotHaveBeenCalled();
    }

    public function it_does_not_create_translations_for_null_values_when_there_arent_any_translations_yet(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        PropertyAccessorInterface $propertyAccessor,
        FactoryInterface $productTranslationFactory,
        FactoryInterface $productVariantTranslationFactory
    ): void {
        $productVariant->getTranslations()->willReturn(new ArrayCollection());
        $product->getTranslations()->willReturn(new ArrayCollection());

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, [['locale' => null, 'scope' => null, 'data' => null]]);

        $propertyAccessor->setValue(Argument::any(), self::TRANSLATION_PROPERTY_PATH, null)->shouldNotHaveBeenCalled();
        $propertyAccessor->setValue(Argument::any(), self::TRANSLATION_PROPERTY_PATH, null)->shouldNotHaveBeenCalled();
        $productVariant->addTranslation(Argument::any())->shouldNotHaveBeenCalled();
        $product->addTranslation(Argument::any())->shouldNotHaveBeenCalled();
        $productTranslationFactory->createNew()->shouldNotHaveBeenCalled();
        $productVariantTranslationFactory->createNew()->shouldNotHaveBeenCalled();
    }

    public function it_skips_values_related_to_channels_that_are_not_associated_to_the_product(
        ProductVariantInterface $productVariant,
        ProductVariantTranslationInterface $italianProductVariantTranslation,
        ProductVariantTranslationInterface $englishProductVariantTranslation,
        ProductTranslationInterface $italianProductTranslation,
        ProductTranslationInterface $englishProductTranslation,
        PropertyAccessorInterface $propertyAccessor
    ): void {
        $value = [
            [
                'scope' => 'ecommerce',
                'locale' => 'en_US',
                'data' => 'Wood',
            ],
            [
                'scope' => 'ecommerce',
                'locale' => 'it_IT',
                'data' => 'Legno',
            ],
            [
                'scope' => 'paper_catalog',
                'locale' => 'en_US',
                'data' => 'Woody',
            ],
            [
                'scope' => 'paper_catalog',
                'locale' => 'it_IT',
                'data' => 'Legnoso',
            ],
        ];

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, $value);

        $propertyAccessor->setValue($englishProductVariantTranslation, self::TRANSLATION_PROPERTY_PATH, 'Wood')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($italianProductVariantTranslation, self::TRANSLATION_PROPERTY_PATH, 'Legno')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($englishProductTranslation, self::TRANSLATION_PROPERTY_PATH, 'Wood')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($italianProductTranslation, self::TRANSLATION_PROPERTY_PATH, 'Legno')->shouldHaveBeenCalled();

        $propertyAccessor->setValue($englishProductVariantTranslation, self::TRANSLATION_PROPERTY_PATH, 'Woody')->shouldNotHaveBeenCalled();
        $propertyAccessor->setValue($italianProductVariantTranslation, self::TRANSLATION_PROPERTY_PATH, 'Legnoso')->shouldNotHaveBeenCalled();
        $propertyAccessor->setValue($englishProductTranslation, self::TRANSLATION_PROPERTY_PATH, 'Woody')->shouldNotHaveBeenCalled();
        $propertyAccessor->setValue($italianProductTranslation, self::TRANSLATION_PROPERTY_PATH, 'Legnoso')->shouldNotHaveBeenCalled();
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
                    [['locale' => 'en_US', 'data' => 'New value']],
                ]
            );
    }
}
