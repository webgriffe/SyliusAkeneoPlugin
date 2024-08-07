<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Doctrine\Common\Collections\ArrayCollection;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverterInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\AttributeValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;
use Webmozart\Assert\InvalidArgumentException;

class AttributeValueHandlerSpec extends ObjectBehavior
{
    private const TEXT_ATTRIBUTE_CODE = 'brand';

    private const CHECKBOX_ATTRIBUTE_CODE = 'outlet';

    private const TEXTAREA_ATTRIBUTE_CODE = 'causale';

    private const INTEGER_ATTRIBUTE_CODE = 'position';

    private const SELECT_ATTRIBUTE_CODE = 'select';

    private const DATETIME_ATTRIBUTE_CODE = 'created_at';

    private const NOT_EXISTING_ATTRIBUTE_CODE = 'not-existing';

    private const PRODUCT_OPTION_CODE = 'finitura';

    public function let(
        ProductAttributeInterface $checkboxProductAttribute,
        ProductAttributeInterface $textProductAttribute,
        ProductAttributeInterface $textareaProductAttribute,
        ProductAttributeInterface $integerProductAttribute,
        ProductAttributeInterface $selectProductAttribute,
        ProductAttributeInterface $datetimeProductAttribute,
        ProductAttributeValueInterface $attributeValue,
        RepositoryInterface $attributeRepository,
        FactoryInterface $factory,
        TranslationLocaleProviderInterface $localeProvider,
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductOptionInterface $productOption,
        ValueConverterInterface $valueConverter
    ): void {
        $checkboxProductAttribute->getCode()->willReturn(self::CHECKBOX_ATTRIBUTE_CODE);
        $textProductAttribute->getCode()->willReturn(self::TEXT_ATTRIBUTE_CODE);
        $textareaProductAttribute->getCode()->willReturn(self::TEXTAREA_ATTRIBUTE_CODE);
        $integerProductAttribute->getCode()->willReturn(self::INTEGER_ATTRIBUTE_CODE);
        $selectProductAttribute->getCode()->willReturn(self::SELECT_ATTRIBUTE_CODE);
        $checkboxProductAttribute->getType()->willReturn('checkbox');
        $textProductAttribute->getType()->willReturn('text');
        $textareaProductAttribute->getType()->willReturn('textarea');
        $integerProductAttribute->getType()->willReturn('integer');
        $selectProductAttribute->getType()->willReturn('select');
        $datetimeProductAttribute->getType()->willReturn('datetime');
        $attributeRepository->findOneBy(['code' => self::CHECKBOX_ATTRIBUTE_CODE])->willReturn($checkboxProductAttribute);
        $attributeRepository->findOneBy(['code' => self::SELECT_ATTRIBUTE_CODE])->willReturn($selectProductAttribute);
        $attributeRepository->findOneBy(['code' => self::TEXT_ATTRIBUTE_CODE])->willReturn($textProductAttribute);
        $attributeRepository->findOneBy(['code' => self::TEXTAREA_ATTRIBUTE_CODE])->willReturn(
            $textareaProductAttribute
        );
        $attributeRepository->findOneBy(['code' => self::PRODUCT_OPTION_CODE])->willReturn(null);
        $attributeRepository->findOneBy(['code' => self::INTEGER_ATTRIBUTE_CODE])->willReturn($integerProductAttribute);
        $attributeRepository->findOneBy(['code' => self::DATETIME_ATTRIBUTE_CODE])->willReturn($datetimeProductAttribute);
        $attributeRepository->findOneBy(['code' => self::NOT_EXISTING_ATTRIBUTE_CODE])->willReturn(null);
        $factory->createNew()->willReturn($attributeValue);
        $localeProvider->getDefinedLocalesCodes()->willReturn(['en_US', 'it_IT', 'de_DE']);
        $productVariant->getProduct()->willReturn($product);
        $productOption->getCode()->willReturn(self::PRODUCT_OPTION_CODE);
        $product->getOptions()->willReturn(new ArrayCollection([$productOption->getWrappedObject()]));
        $commerceChannel = new Channel();
        $commerceChannel->setCode('ecommerce');
        $supportChannel = new Channel();
        $supportChannel->setCode('support');
        $product->getChannels()->willReturn(new ArrayCollection([$commerceChannel, $supportChannel]));
        $selectProductAttribute->getConfiguration()->willReturn(
            [
                'choices' => [
                    'brand_agape_IT' => ['it_IT' => 'Agape Italia', 'en_US' => 'Agape Italy'],
                    'brand_agape_US' => ['it_IT' => 'Agape USA', 'en_US' => 'Agape US'],
                    'brand_agape' => ['it_IT' => 'Agape', 'en_US' => 'Agape'],
                    'brand_agape_plus' => ['it_IT' => 'Agape Pus', 'en_US' => 'Agape Plus'],
                ],
            ]
        );
        $this->beConstructedWith($attributeRepository, $factory, $localeProvider, $valueConverter);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(AttributeValueHandler::class);
    }

    public function it_implements_value_handler_interface(): void
    {
        $this->shouldHaveType(ValueHandlerInterface::class);
    }

    function it_supports_product_variant_as_subject(ProductVariantInterface $productVariant): void
    {
        $this->supports($productVariant, self::TEXT_ATTRIBUTE_CODE, [])->shouldReturn(true);
    }

    public function it_does_not_support_other_type_of_subject(): void
    {
        $this->supports(new \stdClass(), self::TEXT_ATTRIBUTE_CODE, [])->shouldReturn(false);
    }

    public function it_support_existing_text_attributes(ProductVariantInterface $productVariant): void
    {
        $this->supports($productVariant, self::TEXT_ATTRIBUTE_CODE, [])->shouldReturn(true);
    }

    public function it_support_existing_textarea_attributes(ProductVariantInterface $productVariant): void
    {
        $this->supports($productVariant, self::TEXTAREA_ATTRIBUTE_CODE, [])->shouldReturn(true);
    }

    public function it_support_existing_checkbox_attributes(ProductVariantInterface $productVariant): void
    {
        $this->supports($productVariant, self::CHECKBOX_ATTRIBUTE_CODE, [])->shouldReturn(true);
    }

    public function it_support_existing_select_attributes(ProductVariantInterface $productVariant): void
    {
        $this->supports($productVariant, self::SELECT_ATTRIBUTE_CODE, [])->shouldReturn(true);
    }

    public function it_support_existing_integer_attributes(ProductVariantInterface $productVariant): void
    {
        $this->supports($productVariant, self::INTEGER_ATTRIBUTE_CODE, [])->shouldReturn(true);
    }

    public function it_does_not_support_existing_attributes_of_not_supported_type(ProductVariantInterface $productVariant): void
    {
        $this->supports($productVariant, self::DATETIME_ATTRIBUTE_CODE, [])->shouldReturn(false);
    }

    public function it_does_not_support_not_existing_attribute(ProductVariantInterface $productVariant): void
    {
        $this->supports($productVariant, self::NOT_EXISTING_ATTRIBUTE_CODE, [])->shouldReturn(false);
    }

    public function it_does_not_support_attributes_that_are_product_options(ProductVariantInterface $productVariant): void
    {
        $this->supports($productVariant, self::PRODUCT_OPTION_CODE, [])->shouldReturn(false);
    }

    public function it_throws_exception_during_handle_when_subject_is_not_product_variant(): void
    {
        $this
            ->shouldThrow(
                new \InvalidArgumentException(
                    sprintf(
                        'This attribute value handler only supports instances of %s, %s given.',
                        ProductVariantInterface::class,
                        \stdClass::class
                    )
                )
            )
            ->during('handle', [new \stdClass(), self::TEXT_ATTRIBUTE_CODE, []]);
    }

    public function it_throws_exception_during_handle_when_product_variant_hasnt_an_associated_product(ProductVariantInterface $productVariant): void
    {
        $productVariant->getProduct()->willReturn(null);
        $this
            ->shouldThrow(InvalidArgumentException::class)
            ->during('handle', [$productVariant, self::TEXT_ATTRIBUTE_CODE, []]);
    }

    public function it_throws_exception_during_handle_when_attribute_is_not_found(ProductVariantInterface $productVariant): void
    {
        $this
            ->shouldThrow(
                new \InvalidArgumentException(
                    'This attribute value handler only supports existing attributes. ' .
                    'Attribute with the given not-existing code does not exist.',
                )
            )
            ->during('handle', [$productVariant, self::NOT_EXISTING_ATTRIBUTE_CODE, []]);
    }

    public function it_creates_text_product_attribute_value_from_factory_with_all_locales_if_it_does_not_already_exist(
        ProductVariantInterface $productVariant,
        ProductAttributeValueInterface $itAttributeValue,
        ProductAttributeValueInterface $enAttributeValue,
        ProductAttributeValueInterface $deAttributeValue,
        FactoryInterface $factory,
        ProductInterface $product,
        ValueConverterInterface $valueConverter,
        ProductAttributeInterface $textProductAttribute
    ): void {
        $factory->createNew()->willReturn($enAttributeValue, $itAttributeValue, $deAttributeValue);
        $product->getAttributeByCodeAndLocale(Argument::type('string'), Argument::type('string'))->willReturn(null);

        $value = [
            [
                'scope' => null,
                'locale' => null,
                'data' => 'Agape',
            ],
        ];
        $valueConverter->convert($textProductAttribute, 'Agape', 'en_US')->willReturn('Agape');
        $valueConverter->convert($textProductAttribute, 'Agape', 'it_IT')->willReturn('Agape');
        $valueConverter->convert($textProductAttribute, 'Agape', 'de_DE')->willReturn('Agape');

        $this->handle($productVariant, self::TEXT_ATTRIBUTE_CODE, $value);

        $product->addAttribute($itAttributeValue)->shouldHaveBeenCalled();
        $product->addAttribute($enAttributeValue)->shouldHaveBeenCalled();
        $product->addAttribute($deAttributeValue)->shouldHaveBeenCalled();
        $itAttributeValue->setLocaleCode('it_IT')->shouldHaveBeenCalled();
        $itAttributeValue->setValue('Agape')->shouldHaveBeenCalled();
        $enAttributeValue->setLocaleCode('en_US')->shouldHaveBeenCalled();
        $enAttributeValue->setValue('Agape')->shouldHaveBeenCalled();
        $deAttributeValue->setLocaleCode('de_DE')->shouldHaveBeenCalled();
        $deAttributeValue->setValue('Agape')->shouldHaveBeenCalled();
    }

    public function it_creates_text_product_attribute_value_from_factory_with_the_given_locale_if_it_does_not_already_exist(
        ProductVariantInterface $productVariant,
        ProductAttributeValueInterface $itAttributeValue,
        ProductAttributeValueInterface $enAttributeValue,
        ProductAttributeValueInterface $deAttributeValue,
        FactoryInterface $factory,
        ProductInterface $product,
        ValueConverterInterface $valueConverter,
        ProductAttributeInterface $textProductAttribute
    ): void {
        $factory->createNew()->willReturn($enAttributeValue, $itAttributeValue);
        $product->getAttributeByCodeAndLocale(Argument::type('string'), Argument::type('string'))->willReturn(null);

        $value = [
            [
                'scope' => null,
                'locale' => 'en_US',
                'data' => 'Wood',
            ],
            [
                'scope' => null,
                'locale' => 'it_IT',
                'data' => 'Legno',
            ],
        ];

        $valueConverter->convert($textProductAttribute, 'Wood', 'en_US')->willReturn('Wood');
        $valueConverter->convert($textProductAttribute, 'Legno', 'it_IT')->willReturn('Legno');

        $this->handle($productVariant, self::TEXT_ATTRIBUTE_CODE, $value);

        $product->addAttribute($itAttributeValue)->shouldHaveBeenCalled();
        $product->addAttribute($enAttributeValue)->shouldHaveBeenCalled();
        $itAttributeValue->setLocaleCode('it_IT')->shouldHaveBeenCalled();
        $itAttributeValue->setValue('Legno')->shouldHaveBeenCalled();
        $enAttributeValue->setLocaleCode('en_US')->shouldHaveBeenCalled();
        $enAttributeValue->setValue('Wood')->shouldHaveBeenCalled();
        $deAttributeValue->setLocaleCode('de_DE')->shouldNotHaveBeenCalled();
        $deAttributeValue->setValue('Holz')->shouldNotHaveBeenCalled();
    }

    public function it_creates_checkbox_product_attribute_value_from_factory_with_all_locales_if_it_does_not_already_exist(
        ProductVariantInterface $productVariant,
        ProductAttributeValueInterface $itAttributeValue,
        ProductAttributeValueInterface $enAttributeValue,
        ProductAttributeValueInterface $deAttributeValue,
        FactoryInterface $factory,
        ProductInterface $product,
        ValueConverterInterface $valueConverter,
        ProductAttributeInterface $checkboxProductAttribute
    ): void {
        $factory->createNew()->willReturn($enAttributeValue, $itAttributeValue, $deAttributeValue);
        $product->getAttributeByCodeAndLocale(Argument::type('string'), Argument::type('string'))->willReturn(null);

        $value = [
            [
                'scope' => null,
                'locale' => null,
                'data' => true,
            ],
        ];

        $valueConverter->convert($checkboxProductAttribute, true, 'en_US')->willReturn(true);
        $valueConverter->convert($checkboxProductAttribute, true, 'it_IT')->willReturn(true);
        $valueConverter->convert($checkboxProductAttribute, true, 'de_DE')->willReturn(true);

        $this->handle($productVariant, self::CHECKBOX_ATTRIBUTE_CODE, $value);

        $product->addAttribute($itAttributeValue)->shouldHaveBeenCalled();
        $product->addAttribute($enAttributeValue)->shouldHaveBeenCalled();
        $product->addAttribute($deAttributeValue)->shouldHaveBeenCalled();
        $itAttributeValue->setLocaleCode('it_IT')->shouldHaveBeenCalled();
        $itAttributeValue->setValue(true)->shouldHaveBeenCalled();
        $enAttributeValue->setLocaleCode('en_US')->shouldHaveBeenCalled();
        $enAttributeValue->setValue(true)->shouldHaveBeenCalled();
        $deAttributeValue->setLocaleCode('de_DE')->shouldHaveBeenCalled();
        $deAttributeValue->setValue(true)->shouldHaveBeenCalled();
    }

    public function it_creates_checkbox_product_attribute_value_from_factory_with_the_given_locale_if_it_does_not_already_exist(
        ProductVariantInterface $productVariant,
        ProductAttributeValueInterface $itAttributeValue,
        ProductAttributeValueInterface $enAttributeValue,
        ProductAttributeValueInterface $deAttributeValue,
        FactoryInterface $factory,
        ProductInterface $product,
        ValueConverterInterface $valueConverter,
        ProductAttributeInterface $checkboxProductAttribute
    ): void {
        $factory->createNew()->willReturn($enAttributeValue, $itAttributeValue);
        $product->getAttributeByCodeAndLocale(Argument::type('string'), Argument::type('string'))->willReturn(null);

        $value = [
            [
                'scope' => null,
                'locale' => 'en_US',
                'data' => false,
            ],
            [
                'scope' => null,
                'locale' => 'it_IT',
                'data' => false,
            ],
        ];

        $valueConverter->convert($checkboxProductAttribute, false, 'en_US')->willReturn(false);
        $valueConverter->convert($checkboxProductAttribute, false, 'it_IT')->willReturn(false);

        $this->handle($productVariant, self::CHECKBOX_ATTRIBUTE_CODE, $value);

        $product->addAttribute($itAttributeValue)->shouldHaveBeenCalled();
        $product->addAttribute($enAttributeValue)->shouldHaveBeenCalled();
        $itAttributeValue->setLocaleCode('it_IT')->shouldHaveBeenCalled();
        $itAttributeValue->setValue(false)->shouldHaveBeenCalled();
        $enAttributeValue->setLocaleCode('en_US')->shouldHaveBeenCalled();
        $enAttributeValue->setValue(false)->shouldHaveBeenCalled();
        $deAttributeValue->setLocaleCode('de_DE')->shouldNotHaveBeenCalled();
        $deAttributeValue->setValue(false)->shouldNotHaveBeenCalled();
    }

    public function it_creates_select_product_attribute_value_with_the_given_locale_if_it_does_not_already_exist(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductAttributeValueInterface $enAttributeValue,
        ProductAttributeValueInterface $itAttributeValue,
        FactoryInterface $factory,
        ValueConverterInterface $valueConverter,
        ProductAttributeInterface $selectProductAttribute
    ): void {
        $factory->createNew()->willReturn($enAttributeValue, $itAttributeValue);
        $product->getAttributeByCodeAndLocale(Argument::type('string'), Argument::type('string'))->willReturn(null);
        $value = [
            [
                'scope' => null,
                'locale' => 'en_US',
                'data' => 'brand_agape_US',
            ],
            [
                'scope' => null,
                'locale' => 'it_IT',
                'data' => 'brand_agape_IT',
            ],
        ];

        $valueConverter->convert($selectProductAttribute, 'brand_agape_US', 'en_US')->willReturn('brand_agape_US');
        $valueConverter->convert($selectProductAttribute, 'brand_agape_IT', 'it_IT')->willReturn('brand_agape_IT');

        $this->handle($productVariant, self::SELECT_ATTRIBUTE_CODE, $value);

        $product->addAttribute($enAttributeValue)->shouldHaveBeenCalled();
        $product->addAttribute($itAttributeValue)->shouldHaveBeenCalled();
        $enAttributeValue->setLocaleCode('en_US')->shouldHaveBeenCalled();
        $enAttributeValue->setValue('brand_agape_US')->shouldHaveBeenCalled();
        $itAttributeValue->setLocaleCode('it_IT')->shouldHaveBeenCalled();
        $itAttributeValue->setValue('brand_agape_IT')->shouldHaveBeenCalled();
    }

    public function it_creates_select_product_attribute_value_with_all_locales_if_it_does_not_already_exist(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductAttributeValueInterface $enAttributeValue,
        ProductAttributeValueInterface $itAttributeValue,
        ProductAttributeValueInterface $deAttributeValue,
        FactoryInterface $factory,
        ValueConverterInterface $valueConverter,
        ProductAttributeInterface $selectProductAttribute
    ): void {
        $factory->createNew()->willReturn($enAttributeValue, $itAttributeValue, $deAttributeValue);
        $product->getAttributeByCodeAndLocale(Argument::type('string'), Argument::type('string'))->willReturn(null);
        $value = [
            [
                'scope' => null,
                'locale' => null,
                'data' => 'brand_agape',
            ],
        ];

        $valueConverter->convert($selectProductAttribute, 'brand_agape', 'en_US')->willReturn('brand_agape');
        $valueConverter->convert($selectProductAttribute, 'brand_agape', 'it_IT')->willReturn('brand_agape');
        $valueConverter->convert($selectProductAttribute, 'brand_agape', 'de_DE')->willReturn('brand_agape');

        $this->handle($productVariant, self::SELECT_ATTRIBUTE_CODE, $value);

        $enAttributeValue->setLocaleCode('en_US')->shouldHaveBeenCalled();
        $enAttributeValue->setValue('brand_agape')->shouldHaveBeenCalled();
        $itAttributeValue->setLocaleCode('it_IT')->shouldHaveBeenCalled();
        $itAttributeValue->setValue('brand_agape')->shouldHaveBeenCalled();
        $deAttributeValue->setLocaleCode('de_DE')->shouldHaveBeenCalled();
        $deAttributeValue->setValue('brand_agape')->shouldHaveBeenCalled();
        $product->addAttribute($enAttributeValue)->shouldHaveBeenCalled();
        $product->addAttribute($itAttributeValue)->shouldHaveBeenCalled();
        $product->addAttribute($deAttributeValue)->shouldHaveBeenCalled();
    }

    public function it_creates_select_product_attribute_value_with_all_options_and_the_given_locale_if_it_does_not_already_exist(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductAttributeValueInterface $enAttributeValue,
        ProductAttributeValueInterface $itAttributeValue,
        FactoryInterface $factory,
        ValueConverterInterface $valueConverter,
        ProductAttributeInterface $selectProductAttribute
    ): void {
        $factory->createNew()->willReturn($enAttributeValue, $itAttributeValue);
        $product->getAttributeByCodeAndLocale(Argument::type('string'), Argument::type('string'))->willReturn(null);
        $value = [
            [
                'scope' => null,
                'locale' => 'en_US',
                'data' => ['brand_agape_US', 'brand_agape', 'brand_agape_plus'],
            ],
            [
                'scope' => null,
                'locale' => 'it_IT',
                'data' => ['brand_agape_IT', 'brand_agape', 'brand_agape_plus'],
            ],
        ];
        $valueConverter->convert($selectProductAttribute, ['brand_agape_US', 'brand_agape', 'brand_agape_plus'], 'en_US')->willReturn(['brand_agape_US', 'brand_agape', 'brand_agape_plus']);
        $valueConverter->convert($selectProductAttribute, ['brand_agape_IT', 'brand_agape', 'brand_agape_plus'], 'it_IT')->willReturn(['brand_agape_IT', 'brand_agape', 'brand_agape_plus']);

        $this->handle($productVariant, self::SELECT_ATTRIBUTE_CODE, $value);

        $product->addAttribute($enAttributeValue)->shouldHaveBeenCalled();
        $product->addAttribute($itAttributeValue)->shouldHaveBeenCalled();
        $enAttributeValue->setLocaleCode('en_US')->shouldHaveBeenCalled();
        $enAttributeValue->setValue(['brand_agape_US', 'brand_agape', 'brand_agape_plus'])->shouldHaveBeenCalled();
        $itAttributeValue->setLocaleCode('it_IT')->shouldHaveBeenCalled();
        $itAttributeValue->setValue(['brand_agape_IT', 'brand_agape', 'brand_agape_plus'])->shouldHaveBeenCalled();
    }

    public function it_creates_select_product_attribute_value_with_all_options_and_all_locales_if_it_does_not_already_exist(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductAttributeValueInterface $enAttributeValue,
        ProductAttributeValueInterface $itAttributeValue,
        ProductAttributeValueInterface $deAttributeValue,
        FactoryInterface $factory,
        ValueConverterInterface $valueConverter,
        ProductAttributeInterface $selectProductAttribute
    ): void {
        $factory->createNew()->willReturn($enAttributeValue, $itAttributeValue, $deAttributeValue);
        $product->getAttributeByCodeAndLocale(Argument::type('string'), Argument::type('string'))->willReturn(null);
        $value = [
            [
                'scope' => null,
                'locale' => null,
                'data' => ['brand_agape', 'brand_agape_plus'],
            ],
        ];
        $valueConverter->convert($selectProductAttribute, ['brand_agape', 'brand_agape_plus'], 'en_US')->willReturn(['brand_agape', 'brand_agape_plus']);
        $valueConverter->convert($selectProductAttribute, ['brand_agape', 'brand_agape_plus'], 'it_IT')->willReturn(['brand_agape', 'brand_agape_plus']);
        $valueConverter->convert($selectProductAttribute, ['brand_agape', 'brand_agape_plus'], 'de_DE')->willReturn(['brand_agape', 'brand_agape_plus']);

        $this->handle($productVariant, self::SELECT_ATTRIBUTE_CODE, $value);

        $enAttributeValue->setLocaleCode('en_US')->shouldHaveBeenCalled();
        $enAttributeValue->setValue(['brand_agape', 'brand_agape_plus'])->shouldHaveBeenCalled();
        $itAttributeValue->setLocaleCode('it_IT')->shouldHaveBeenCalled();
        $itAttributeValue->setValue(['brand_agape', 'brand_agape_plus'])->shouldHaveBeenCalled();
        $deAttributeValue->setLocaleCode('de_DE')->shouldHaveBeenCalled();
        $deAttributeValue->setValue(['brand_agape', 'brand_agape_plus'])->shouldHaveBeenCalled();
        $product->addAttribute($enAttributeValue)->shouldHaveBeenCalled();
        $product->addAttribute($itAttributeValue)->shouldHaveBeenCalled();
        $product->addAttribute($deAttributeValue)->shouldHaveBeenCalled();
    }

    public function it_creates_integer_product_attribute_value_with_all_locales_if_it_does_not_already_exist(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductAttributeValueInterface $enAttributeValue,
        ProductAttributeValueInterface $itAttributeValue,
        ProductAttributeValueInterface $deAttributeValue,
        FactoryInterface $factory,
        ValueConverterInterface $valueConverter,
        ProductAttributeInterface $integerProductAttribute
    ): void {
        $factory->createNew()->willReturn($enAttributeValue, $itAttributeValue, $deAttributeValue);
        $product->getAttributeByCodeAndLocale(Argument::type('string'), Argument::type('string'))->willReturn(null);
        $value = [
            [
                'scope' => null,
                'locale' => null,
                'data' => 123,
            ],
        ];

        $valueConverter->convert($integerProductAttribute, 123, 'en_US')->willReturn(123);
        $valueConverter->convert($integerProductAttribute, 123, 'it_IT')->willReturn(123);
        $valueConverter->convert($integerProductAttribute, 123, 'de_DE')->willReturn(123);

        $this->handle($productVariant, self::INTEGER_ATTRIBUTE_CODE, $value);

        $enAttributeValue->setLocaleCode('en_US')->shouldHaveBeenCalled();
        $enAttributeValue->setValue(123)->shouldHaveBeenCalled();
        $itAttributeValue->setLocaleCode('it_IT')->shouldHaveBeenCalled();
        $itAttributeValue->setValue(123)->shouldHaveBeenCalled();
        $deAttributeValue->setLocaleCode('de_DE')->shouldHaveBeenCalled();
        $deAttributeValue->setValue(123)->shouldHaveBeenCalled();
        $product->addAttribute($enAttributeValue)->shouldHaveBeenCalled();
        $product->addAttribute($itAttributeValue)->shouldHaveBeenCalled();
        $product->addAttribute($deAttributeValue)->shouldHaveBeenCalled();
    }

    public function it_does_not_create_the_same_attribute_value_more_than_once(
        ProductVariantInterface $productVariant,
        ProductAttributeValueInterface $itAttributeValue,
        FactoryInterface $factory,
        ProductInterface $product,
        ProductAttributeInterface $textProductAttribute,
        ValueConverterInterface $valueConverter
    ): void {
        $factory->createNew()->willReturn($itAttributeValue);
        $product->getAttributeByCodeAndLocale(self::TEXT_ATTRIBUTE_CODE, 'it_IT')->willReturn(null, $itAttributeValue);
        $product->getAttributeByCodeAndLocale(self::TEXT_ATTRIBUTE_CODE, 'en_US')->willReturn(null, null);
        $product->getAttributeByCodeAndLocale(self::TEXT_ATTRIBUTE_CODE, 'de_DE')->willReturn(null, null);

        $firstValue = [
            [
                'scope' => null,
                'locale' => 'it_IT',
                'data' => 'Agape',
            ],
        ];

        $secondValue = [
            [
                'scope' => null,
                'locale' => 'it_IT',
                'data' => 'Agape Plus',
            ],
        ];

        $valueConverter->convert($textProductAttribute, 'Agape', 'it_IT')->willReturn('Agape');
        $valueConverter->convert($textProductAttribute, 'Agape Plus', 'it_IT')->willReturn('Agape Plus');

        $this->handle($productVariant, self::TEXT_ATTRIBUTE_CODE, $firstValue);
        $this->handle($productVariant, self::TEXT_ATTRIBUTE_CODE, $secondValue);

        $factory->createNew()->shouldHaveBeenCalledOnce();
        $product->addAttribute($itAttributeValue)->shouldHaveBeenCalledTimes(2);
        $itAttributeValue->setLocaleCode('it_IT')->shouldHaveBeenCalledTimes(2);
        $itAttributeValue->setValue('Agape')->shouldHaveBeenCalledOnce();
        $itAttributeValue->setValue('Agape Plus')->shouldHaveBeenCalledOnce();
    }

    public function it_removes_existing_product_attribute_value_if_value_is_null(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductAttributeValueInterface $itAttributeValue,
        ProductAttributeValueInterface $enAttributeValue
    ): void {
        $product->getAttributeByCodeAndLocale(self::TEXT_ATTRIBUTE_CODE, 'it_IT')->willReturn($itAttributeValue);
        $product->getAttributeByCodeAndLocale(self::TEXT_ATTRIBUTE_CODE, 'en_US')->willReturn($enAttributeValue);
        $product->getAttributeByCodeAndLocale(self::TEXT_ATTRIBUTE_CODE, 'de_DE')->willReturn(null);
        $value = [
            [
                'scope' => null,
                'locale' => null,
                'data' => null,
            ],
        ];

        $this->handle($productVariant, self::TEXT_ATTRIBUTE_CODE, $value);

        $product->removeAttribute($itAttributeValue)->shouldHaveBeenCalled();
        $product->removeAttribute($enAttributeValue)->shouldHaveBeenCalled();
        $product->addAttribute(Argument::any())->shouldNotHaveBeenCalled();
    }

    public function it_skips_locales_not_specified_in_sylius(
        ProductVariantInterface $productVariant,
        ProductInterface $product
    ): void {
        $product->getAttributeByCodeAndLocale(self::TEXT_ATTRIBUTE_CODE, 'en_US')->willReturn(null);
        $product->getAttributeByCodeAndLocale(self::TEXT_ATTRIBUTE_CODE, 'it_IT')->willReturn(null);
        $product->getAttributeByCodeAndLocale(self::TEXT_ATTRIBUTE_CODE, 'de_DE')->willReturn(null);

        $this->handle($productVariant, self::TEXT_ATTRIBUTE_CODE, [['locale' => 'es_ES', 'scope' => null, 'data' => 'New value']]);

        $product->addAttribute(Argument::type(ProductAttributeValueInterface::class))->shouldNotHaveBeenCalled();
    }

    public function it_skips_values_related_to_channels_that_are_not_associated_to_the_product(
        ProductVariantInterface $productVariant,
        ProductAttributeValueInterface $itAttributeValue,
        ProductAttributeValueInterface $enAttributeValue,
        FactoryInterface $factory,
        ProductInterface $product,
        ValueConverterInterface $valueConverter,
        ProductAttributeInterface $textProductAttribute
    ): void {
        $factory->createNew()->willReturn($enAttributeValue, $itAttributeValue);
        $product->getAttributeByCodeAndLocale(Argument::type('string'), Argument::type('string'))->willReturn(null);

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

        $valueConverter->convert($textProductAttribute, 'Wood', 'en_US')->willReturn('Wood');
        $valueConverter->convert($textProductAttribute, 'Legno', 'it_IT')->willReturn('Legno');

        $this->handle($productVariant, self::TEXT_ATTRIBUTE_CODE, $value);

        $product->addAttribute($itAttributeValue)->shouldHaveBeenCalled();
        $product->addAttribute($enAttributeValue)->shouldHaveBeenCalled();
        $itAttributeValue->setLocaleCode('it_IT')->shouldHaveBeenCalled();
        $itAttributeValue->setValue('Legno')->shouldHaveBeenCalled();
        $enAttributeValue->setLocaleCode('en_US')->shouldHaveBeenCalled();
        $enAttributeValue->setValue('Wood')->shouldHaveBeenCalled();
        $itAttributeValue->setValue('Legnoso')->shouldNotHaveBeenCalled();
        $enAttributeValue->setValue('Woody')->shouldNotHaveBeenCalled();
    }

    public function it_throws_when_data_is_not_an_array(
        ProductInterface $product,
        ProductVariantInterface $productVariant,
    ): void {
        $this
            ->shouldThrow(new \InvalidArgumentException('Invalid Akeneo value data: expected an array, "NULL" given.',))
            ->during('handle', [$productVariant, self::TEXT_ATTRIBUTE_CODE, [null]]);
    }

    public function it_throws_when_data_doesnt_contain_scope_info(
        ProductInterface $product,
        ProductVariantInterface $productVariant,
    ): void {
        $this
            ->shouldThrow(new \InvalidArgumentException('Invalid Akeneo value data: required "scope" information was not found.',))
            ->during(
                'handle',
                [
                    $productVariant,
                    self::TEXT_ATTRIBUTE_CODE,
                    [
                        [
                            'locale' => 'en_US',
                            'data' => 'Wood',
                        ],
                    ],
                ]
            );
    }

    public function it_removes_no_more_existing_product_attribute_value_for_some_locale(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductAttributeValueInterface $itAttributeValue,
        ProductAttributeValueInterface $enAttributeValue,
        ValueConverterInterface $valueConverter,
        ProductAttributeInterface $selectProductAttribute,
    ): void {
        $product->getAttributeByCodeAndLocale(self::SELECT_ATTRIBUTE_CODE, 'it_IT')->willReturn($itAttributeValue);
        $product->getAttributeByCodeAndLocale(self::SELECT_ATTRIBUTE_CODE, 'en_US')->willReturn($enAttributeValue);
        $product->getAttributeByCodeAndLocale(self::SELECT_ATTRIBUTE_CODE, 'de_DE')->willReturn(null);
        $value = [
            [
                'scope' => null,
                'locale' => 'en_US',
                'data' => 'Agape',
            ],
        ];
        $valueConverter->convert($selectProductAttribute, 'Agape', 'en_US')->willReturn('Agape');

        $this->handle($productVariant, self::SELECT_ATTRIBUTE_CODE, $value);

        $product->removeAttribute($itAttributeValue)->shouldHaveBeenCalled();
        $product->addAttribute(Argument::any())->shouldHaveBeenCalled();
    }
}
