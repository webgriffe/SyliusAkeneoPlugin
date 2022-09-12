<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\Converter;

use Sylius\Component\Product\Model\ProductAttribute;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverterInterface;

class ValueConverterTest extends KernelTestCase
{
    private ValueConverterInterface $valueConverter;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->valueConverter = self::getContainer()->get('webgriffe_sylius_akeneo.converter.value');
    }

    /**
     * @test
     */
    public function it_converts_metric_value_keeping_significant_decimal(): void
    {
        $attribute = new ProductAttribute();
        $valueConverted = $this->valueConverter->convert($attribute, [
            'amount' => 23.045000,
            'unit' => 'INCH',
        ], 'it');

        self::assertEquals('23,045 in', $valueConverted);
    }

    /**
     * @test
     */
    public function it_converts_metric_value_removing_not_significant_decimal(): void
    {
        $attribute = new ProductAttribute();
        $valueConverted = $this->valueConverter->convert($attribute, [
            'amount' => 54.0000,
            'unit' => 'METER',
        ], 'en');

        self::assertEquals('54 m', $valueConverted);
    }

    /**
     * @test
     */
    public function it_converts_metric_value_with_amount_zero_if_it_is_empty(): void
    {
        $attribute = new ProductAttribute();
        $valueConverted = $this->valueConverter->convert($attribute, [
            'amount' => null,
            'unit' => 'METER',
        ], 'en');

        self::assertEquals('0 m', $valueConverted);
    }
}
