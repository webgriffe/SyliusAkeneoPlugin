<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\AttributeOptionApiInterface;
use Akeneo\Pim\ApiClient\Exception\HttpException;
use Doctrine\Common\Collections\ArrayCollection;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PhpSpec\ObjectBehavior;
use RuntimeException;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslationInterface;
use Sylius\Component\Product\Repository\ProductOptionRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\ProductOptionValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;

class ProductOptionValueHandlerSpec extends ObjectBehavior
{
    private const VARIANT_CODE = 'variant-code';

    private const PRODUCT_CODE = 'product-code';

    private const OPTION_CODE = 'option-code';

    private const VALUE_CODE = 'value-code';

    private const EN_LABEL = 'EN Label';

    private const IT_LABEL = 'IT Label';

    public function let(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductOptionInterface $productOption,
        AkeneoPimClientInterface $apiClient,
        AttributeOptionApiInterface $attributeOptionApi,
        ProductOptionRepositoryInterface $productOptionRepository,
        FactoryInterface $productOptionValueFactory,
        FactoryInterface $productOptionValueTranslationFactory,
        RepositoryInterface $productOptionValueRepository,
        ProductOptionValueInterface $productOptionValue,
        ProductOptionValueTranslationInterface $englishProductOptionValueTranslation,
        ProductOptionValueTranslationInterface $italianProductOptionValueTranslation,
        TranslationLocaleProviderInterface $translationLocaleProvider
    ): void {
        $productVariant->getCode()->willReturn(self::VARIANT_CODE);
        $productVariant->getProduct()->willReturn($product);
        $product->getCode()->willReturn(self::PRODUCT_CODE);
        $product->getOptions()->willReturn(new ArrayCollection([$productOption->getWrappedObject()]));
        $productOption->getCode()->willReturn(self::OPTION_CODE);
        $apiClient->getAttributeOptionApi()->willReturn($attributeOptionApi);
        $attributeOptionApi
            ->get(self::OPTION_CODE, self::VALUE_CODE)
            ->willReturn(
                [
                    'code' => self::VALUE_CODE,
                    'attribute' => self::OPTION_CODE,
                    'sort_order' => 4,
                    'labels' => ['en_US' => self::EN_LABEL, 'it_IT' => self::IT_LABEL],
                ]
            );
        $productOptionRepository->findOneBy(['code' => self::OPTION_CODE])->willReturn($productOption);
        $productOptionValueFactory->createNew()->willReturn($productOptionValue);
        $productOptionValue->getTranslation('en_US')->willReturn($englishProductOptionValueTranslation);
        $productOptionValue->getTranslation('it_IT')->willReturn($englishProductOptionValueTranslation);
        $productOptionValueTranslationFactory->createNew()->willReturn($italianProductOptionValueTranslation);
        $productOptionValue->hasTranslation($englishProductOptionValueTranslation)->willReturn(false);
        $productOptionValue->hasTranslation($italianProductOptionValueTranslation)->willReturn(false);
        $englishProductOptionValueTranslation->getLocale()->willReturn('en_US');
        $translationLocaleProvider->getDefinedLocalesCodes()->willReturn(['en_US', 'it_IT']);

        $this->beConstructedWith(
            $apiClient,
            $productOptionRepository,
            $productOptionValueFactory,
            $productOptionValueTranslationFactory,
            $productOptionValueRepository,
            $translationLocaleProvider
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(ProductOptionValueHandler::class);
    }

    public function it_implements_value_handler_interface(): void
    {
        $this->shouldHaveType(ValueHandlerInterface::class);
    }

    public function it_supports_product_variant_as_subject(ProductVariantInterface $productVariant): void
    {
        $this->supports($productVariant, self::OPTION_CODE, [])->shouldReturn(true);
    }

    public function it_does_not_support_other_type_of_subject(): void
    {
        $this->supports(new \stdClass(), self::OPTION_CODE, [])->shouldReturn(false);
    }

    public function it_supports_option_code_of_parent_product(ProductVariantInterface $productVariant): void
    {
        $this->supports($productVariant, self::OPTION_CODE, [])->shouldReturn(true);
    }

    public function it_does_not_support_different_attribute_than_option_code_of_parent_product(
        ProductVariantInterface $productVariant
    ): void {
        $this->supports($productVariant, 'other-attribute', [])->shouldReturn(false);
    }

    public function it_throws_exception_during_handle_when_subject_is_not_product_variant(): void
    {
        $this
            ->shouldThrow(
                new \InvalidArgumentException(
                    sprintf(
                        'This option value handler only supports instances of %s, %s given.',
                        ProductVariantInterface::class,
                        \stdClass::class
                    )
                )
            )
            ->during('handle', [new \stdClass(), self::OPTION_CODE, []]);
    }

    public function it_throws_exception_during_handle_when_value_has_an_invalid_number_of_values(
        ProductVariantInterface $productVariant
    ): void {
        $value = [
            [
                'scope' => null,
                'locale' => 'it_IT',
                'data' => 'IT-value',
            ],
            [
                'scope' => null,
                'locale' => 'en_US',
                'data' => 'EN-value',
            ],
        ];

        $this->shouldThrow(
            new RuntimeException(
                sprintf(
                    'Cannot handle option value on Akeneo product "%s", the option of the parent product "%s" is ' .
                    '"%s". More than one value is set for this attribute on Akeneo but this handler only supports ' .
                    'single value for product options.',
                    self::VARIANT_CODE,
                    self::PRODUCT_CODE,
                    self::OPTION_CODE
                )
            )
        )->during('handle', [$productVariant, self::OPTION_CODE, $value]);
    }

    public function it_throws_an_exception_during_handle_if_attribute_option_does_not_exists_on_akeneo(
        ProductVariantInterface $productVariant,
        AttributeOptionApiInterface $attributeOptionApi
    ): void {
        $value = [
            [
                'scope' => null,
                'locale' => null,
                'data' => self::VALUE_CODE,
            ],
        ];
        $attributeOptionApi->get(self::OPTION_CODE, self::VALUE_CODE)->willThrow(
            new HttpException('Not found', new Request('GET', '/'), new Response(404))
        );

        $this->shouldThrow(
            new RuntimeException(
                sprintf(
                    'Cannot handle option value on Akeneo product "%s", the option of the parent product "%s" is ' .
                    '"%s". The option value for this variant is "%s" but there is no such option on Akeneo.',
                    self::VARIANT_CODE,
                    self::PRODUCT_CODE,
                    self::OPTION_CODE,
                    self::VALUE_CODE
                )
            )
        )->during('handle', [$productVariant, self::OPTION_CODE, $value]);
    }

    public function it_throws_an_exception_if_product_option_does_not_exists_on_sylius(
        ProductVariantInterface $productVariant,
        ProductOptionRepositoryInterface $productOptionRepository
    ): void {
        $value = [
            [
                'scope' => null,
                'locale' => null,
                'data' => self::VALUE_CODE,
            ],
        ];
        $productOptionRepository->findOneBy(['code' => self::OPTION_CODE])->willReturn(null);

        $this->shouldThrow(
            new RuntimeException(
                sprintf(
                    'Cannot import Akeneo product "%s", the option "%s" is not set on the parent product "%s".',
                    self::VARIANT_CODE,
                    self::OPTION_CODE,
                    self::PRODUCT_CODE
                )
            )
        )->during('handle', [$productVariant, self::OPTION_CODE, $value]);
    }

    public function it_creates_product_option_value_from_factory_with_all_translations_if_does_not_already_exists(
        ProductVariantInterface $productVariant,
        ProductOptionValueInterface $productOptionValue,
        ProductOptionValueTranslationInterface $englishProductOptionValueTranslation,
        ProductOptionValueTranslationInterface $italianProductOptionValueTranslation,
        ProductOptionInterface $productOption
    ): void {
        $value = [
            [
                'scope' => null,
                'locale' => null,
                'data' => self::VALUE_CODE,
            ],
        ];
        $productVariant->hasOptionValue($productOptionValue)->willReturn(false);

        $this->handle($productVariant, self::OPTION_CODE, $value);

        $productOptionValue->setCode('option-code_value-code')->shouldHaveBeenCalled();
        $productOptionValue->setOption($productOption)->shouldHaveBeenCalled();
        $productOption->addValue($productOptionValue)->shouldHaveBeenCalled();
        $englishProductOptionValueTranslation->setValue(self::EN_LABEL)->shouldHaveBeenCalled();
        $italianProductOptionValueTranslation->setLocale('it_IT')->shouldHaveBeenCalled();
        $italianProductOptionValueTranslation->setValue(self::IT_LABEL)->shouldHaveBeenCalled();
        $productOptionValue->addTranslation($englishProductOptionValueTranslation)->shouldHaveBeenCalled();
        $productOptionValue->addTranslation($italianProductOptionValueTranslation)->shouldHaveBeenCalled();
        $productVariant->addOptionValue($productOptionValue)->shouldHaveBeenCalled();
    }

    public function it_updates_existing_product_option_value_and_all_translations(
        ProductVariantInterface $productVariant,
        RepositoryInterface $productOptionValueRepository,
        ProductOptionValueInterface $existentProductOptionValue,
        ProductOptionValueTranslationInterface $englishProductOptionValue,
        ProductOptionValueTranslationInterface $italianProductOptionValue
    ): void {
        $value = [
            [
                'scope' => null,
                'locale' => null,
                'data' => self::VALUE_CODE,
            ],
        ];
        $productOptionValueRepository->findOneBy(['code' => self::OPTION_CODE . '_' . self::VALUE_CODE])->willReturn($existentProductOptionValue);
        $englishProductOptionValue->getLocale()->willReturn('en_US');
        $italianProductOptionValue->getLocale()->willReturn('it_IT');
        $existentProductOptionValue->getTranslation('en_US')->willReturn($englishProductOptionValue);
        $existentProductOptionValue->getTranslation('it_IT')->willReturn($italianProductOptionValue);
        $existentProductOptionValue->hasTranslation($englishProductOptionValue)->willReturn(true);
        $existentProductOptionValue->hasTranslation($italianProductOptionValue)->willReturn(true);
        $productVariant->hasOptionValue($existentProductOptionValue)->willReturn(true);

        $this->handle($productVariant, self::OPTION_CODE, $value);

        $englishProductOptionValue->setValue(self::EN_LABEL)->shouldHaveBeenCalled();
        $italianProductOptionValue->setValue(self::IT_LABEL)->shouldHaveBeenCalled();
    }

    public function it_skips_locale_not_defined_on_sylius(
        ProductVariantInterface $productVariant,
        ProductOptionValueInterface $productOptionValue,
        ProductOptionValueTranslationInterface $englishProductOptionValueTranslation,
        ProductOptionValueTranslationInterface $italianProductOptionValueTranslation,
        ProductOptionInterface $productOption,
        AttributeOptionApiInterface $attributeOptionApi,
        FactoryInterface $productOptionValueTranslationFactory
    ): void {
        $value = [
            [
                'scope' => null,
                'locale' => null,
                'data' => self::VALUE_CODE,
            ],
        ];
        $productVariant->hasOptionValue($productOptionValue)->willReturn(false);
        $attributeOptionApi
            ->get(self::OPTION_CODE, self::VALUE_CODE)
            ->willReturn(
                [
                    'code' => self::VALUE_CODE,
                    'attribute' => self::OPTION_CODE,
                    'sort_order' => 4,
                    'labels' => ['en_US' => self::EN_LABEL, 'it_IT' => self::IT_LABEL, 'de_DE' => 'German Label'],
                ]
            )
        ;
        $productOptionValue->getTranslation('de_DE')->willReturn($englishProductOptionValueTranslation);

        $this->handle($productVariant, self::OPTION_CODE, $value);

        $productOptionValue->setCode('option-code_value-code')->shouldHaveBeenCalled();
        $productOptionValue->setOption($productOption)->shouldHaveBeenCalled();
        $productOption->addValue($productOptionValue)->shouldHaveBeenCalled();
        $englishProductOptionValueTranslation->setValue(self::EN_LABEL)->shouldHaveBeenCalled();
        $italianProductOptionValueTranslation->setLocale('it_IT')->shouldHaveBeenCalled();
        $italianProductOptionValueTranslation->setValue(self::IT_LABEL)->shouldHaveBeenCalled();
        $productOptionValue->addTranslation($englishProductOptionValueTranslation)->shouldHaveBeenCalled();
        $productOptionValue->addTranslation($italianProductOptionValueTranslation)->shouldHaveBeenCalled();
        $productVariant->addOptionValue($productOptionValue)->shouldHaveBeenCalled();
        $productOptionValueTranslationFactory->createNew()->shouldHaveBeenCalledOnce();
    }
}
