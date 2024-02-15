<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\Converter;

use InvalidArgumentException;
use LogicException;
use PhpSpec\ObjectBehavior;
use Sylius\Component\Attribute\AttributeType\CheckboxAttributeType;
use Sylius\Component\Attribute\AttributeType\DatetimeAttributeType;
use Sylius\Component\Attribute\AttributeType\IntegerAttributeType;
use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Sylius\Component\Attribute\AttributeType\TextareaAttributeType;
use Sylius\Component\Attribute\AttributeType\TextAttributeType;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverter;
use Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverterInterface;

class ValueConverterSpec extends ObjectBehavior
{
    private const IT_LOCALE_CODE = 'it_IT';

    public function let(
        TranslatorInterface $translator,
        AttributeInterface $textAttribute,
        AttributeInterface $checkboxAttribute,
        AttributeInterface $textareaAttribute,
        AttributeInterface $integerAttribute,
        AttributeInterface $selectAttribute,
        AttributeInterface $datetimeAttribute
    ): void {
        $translator->trans('webgriffe_sylius_akeneo.ui.metric_amount_unit', ['unit' => 'INCH', 'amount' => '23'], null, 'it')->willReturn('23"');

        $textAttribute->getType()->willReturn(TextAttributeType::TYPE);
        $integerAttribute->getType()->willReturn(IntegerAttributeType::TYPE);
        $textareaAttribute->getType()->willReturn(TextareaAttributeType::TYPE);
        $checkboxAttribute->getType()->willReturn(CheckboxAttributeType::TYPE);
        $selectAttribute->getCode()->willReturn('brand');
        $selectAttribute->getType()->willReturn(SelectAttributeType::TYPE);
        $datetimeAttribute->getType()->willReturn(DatetimeAttributeType::TYPE);

        $selectAttribute->getConfiguration()->willReturn(
            [
                'choices' => [
                    'brand_agape_IT' => ['it_IT' => 'Agape Italia', 'en_US' => 'Agape Italy'],
                    'brand_agape_US' => ['it_IT' => 'Agape USA', 'en_US' => 'Agape US'],
                    'brand_agape' => ['it_IT' => 'Agape', 'en_US' => 'Agape'],
                    'brand_agape_plus' => ['it_IT' => 'Agape Pus', 'en_US' => 'Agape Plus'],
                ],
            ]
        );

        $this->beConstructedWith($translator);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(ValueConverter::class);
    }

    public function it_implements_value_converter_interface(): void
    {
        $this->shouldHaveType(ValueConverterInterface::class);
    }

    public function it_throws_exception_during_convert_when_value_not_contains_amount_key(
        AttributeInterface $textAttribute
    ): void {
        $this->shouldThrow(
            new LogicException('Amount key not found')
        )->during('convert', [
            $textAttribute,
            [
                'key' => 'value',
            ],
            'it',
        ]);
    }

    public function it_throws_exception_during_convert_when_value_contains_amount_key_and_not_contains_unit_key(
        AttributeInterface $textAttribute
    ): void {
        $this->shouldThrow(
            new LogicException('Unit key not found')
        )->during('convert', [
            $textAttribute,
            [
                'amount' => 23.0000,
                'key' => 'value',
            ],
            'it',
        ]);
    }

    public function it_converts_metric_value_from_akeneo_to_text_value_translated_when_translator_is_injected(
        AttributeInterface $textAttribute
    ): void {
        $this->convert(
            $textAttribute,
            [
                'amount' => 23.0000,
                'unit' => 'INCH',
            ],
            'it'
        )->shouldReturn('23"');
    }

    public function it_converts_text_value_from_akeneo_to_text_value(
        AttributeInterface $textAttribute
    ): void {
        $value = 'Agape';
        $this->convert($textAttribute, $value, self::IT_LOCALE_CODE)->shouldReturn($value);
    }

    public function it_converts_integer_value_from_akeneo_to_integer_value(
        AttributeInterface $integerAttribute
    ): void {
        $value = 123;
        $this->convert($integerAttribute, $value, self::IT_LOCALE_CODE)->shouldReturn($value);
    }

    public function it_converts_textarea_value_from_akeneo_to_textarea_value(
        AttributeInterface $textareaAttribute
    ): void {
        $value = 'Lorem ipsum dolor sit amet';
        $this->convert($textareaAttribute, $value, self::IT_LOCALE_CODE)->shouldReturn($value);
    }

    public function it_converts_boolean_value_from_akeneo_to_checkbox_value(
        AttributeInterface $checkboxAttribute
    ): void {
        $value = true;
        $this->convert($checkboxAttribute, $value, self::IT_LOCALE_CODE)->shouldReturn($value);
    }

    public function it_converts_select_value_from_akeneo_to_select_value(
        AttributeInterface $selectAttribute
    ): void {
        $value = ['brand_agape_IT'];
        $this->convert($selectAttribute, $value, self::IT_LOCALE_CODE)->shouldReturn($value);
    }

    public function it_throws_error_when_select_value_is_not_an_existing_option(
        AttributeInterface $selectAttribute
    ): void {
        $value = ['brand_not_existing'];

        $this
            ->shouldThrow(
                new InvalidArgumentException(
                    'This select attribute can only save existing attribute options. ' .
                    'Attribute option codes [brand_not_existing] for attribute "brand" does not exist.',
                )
            )
            ->during('convert', [$selectAttribute, $value, self::IT_LOCALE_CODE]);
    }

    public function it_throws_error_when_select_values_are_not_existing_options(
        AttributeInterface $selectAttribute
    ): void {
        $value = ['brand_not_existing', 'brand_not_existing_2'];

        $this
            ->shouldThrow(
                new InvalidArgumentException(
                    'This select attribute can only save existing attribute options. ' .
                    'Attribute option codes [brand_not_existing, brand_not_existing_2] for attribute "brand" does not exist.',
                )
            )
            ->during('convert', [$selectAttribute, $value, self::IT_LOCALE_CODE]);
    }

    public function it_throws_error_when_select_values_are_not_all_existing_options(
        AttributeInterface $selectAttribute
    ): void {
        $value = ['brand_agape', 'brand_not_existing'];

        $this
            ->shouldThrow(
                new InvalidArgumentException(
                    'This select attribute can only save existing attribute options. ' .
                    'Attribute option codes [brand_agape, brand_not_existing] for attribute "brand" does not exist.',
                )
            )
            ->during('convert', [$selectAttribute, $value, self::IT_LOCALE_CODE]);
    }

    public function it_converts_datetime_value_from_akeneo_to_datetime_value(
        AttributeInterface $datetimeAttribute
    ): void {
        $value = '2020-01-01 11:12:32';
        $this->convert($datetimeAttribute, $value, self::IT_LOCALE_CODE)->shouldReturn($value);
    }
}
