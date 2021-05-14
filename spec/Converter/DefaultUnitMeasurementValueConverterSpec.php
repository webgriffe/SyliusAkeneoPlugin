<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\Converter;

use Webgriffe\SyliusAkeneoPlugin\Converter\DefaultUnitMeasurementValueConverter;
use PhpSpec\ObjectBehavior;
use Webgriffe\SyliusAkeneoPlugin\Converter\DefaultUnitMeasurementValueConverterInterface;
use Webgriffe\SyliusAkeneoPlugin\MeasurementFamiliesApiClientInterface;

class DefaultUnitMeasurementValueConverterSpec extends ObjectBehavior
{
    public function let(
        MeasurementFamiliesApiClientInterface $apiClient
    ) {
        $unitMeasurementsFamilies = <<<JSON
[
  {
    "code": "Area",
    "labels": {
      "en_US": "Area",
      "it_IT": "Area"
    },
    "standard_unit_code": "SQUARE_METER",
    "units": {
      "SQUARE_METER": {
        "code": "SQUARE_METER",
        "labels": {
          "en_US": "Square meter",
          "it_IT": "Metro quadrato"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "1"
          }
        ],
        "symbol": "m²"
      }
    }
  },
  {
    "code": "Weight",
    "labels": {
      "en_US": "Weight",
      "it_IT": "Peso"
    },
    "standard_unit_code": "KILOGRAM",
    "units": {
      "GRAM": {
        "code": "GRAM",
        "labels": {
          "en_US": "Gram",
          "it_IT": "Gram"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "0.001"
          }
        ],
        "symbol": "g"
      },
      "KILOGRAM": {
        "code": "KILOGRAM",
        "labels": {
          "en_US": "Kilogram",
          "it_IT": "Chilogrammo"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "1"
          }
        ],
        "symbol": "kg"
      },
      "TON": {
        "code": "TON",
        "labels": {
          "en_US": "Ton",
          "it_IT": "Tonnellata"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "1000"
          }
        ],
        "symbol": "t"
      },
      "LIVRE": {
        "code": "LIVRE",
        "labels": {
          "en_US": "Livre",
          "it_IT": "Livre"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "0.4895"
          }
        ],
        "symbol": "livre"
      }
    }
  }
]
JSON;

        $apiClient->getMeasurementFamilies()->willReturn(json_decode($unitMeasurementsFamilies, true));
        $this->beConstructedWith($apiClient);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(DefaultUnitMeasurementValueConverter::class);
    }

    function it_implements_default_unit_measurement_value_converter_interface()
    {
        $this->shouldHaveType(DefaultUnitMeasurementValueConverterInterface::class);
    }

    function it_throws_exception_during_convert_when_unit_measurement_code_is_not_found_on_akeneo()
    {
        $this->shouldThrow(
            new \LogicException(sprintf(
                'Unable to retrieve unit measurement family for the "%s" unit measurement code',
                'NOT_EXISTING_UNIT_MEASUREMENT_CODE'
            ))
        )->during('convert', [
            '23.0000',
            'NOT_EXISTING_UNIT_MEASUREMENT_CODE',
            null,
        ]);
    }

    function it_throws_exception_during_convert_when_default_unit_measurement_code_is_not_found_on_akeneo()
    {
        $this->shouldThrow(
            new \LogicException(sprintf(
                'Unable to retrieve unit measurement family for the "%s" unit measurement code',
                'NOT_EXISTING_DEFAULT_UNIT_MEASUREMENT_CODE'
            ))
        )->during('convert', [
            '23.0000',
            'KILOGRAM',
            'NOT_EXISTING_DEFAULT_UNIT_MEASUREMENT_CODE',
        ]);
    }

    function it_throws_exception_during_convert_when_default_unit_measurement_code_family_is_not_the_same_of_unit_code()
    {
        $this->shouldThrow(
            new \LogicException(sprintf(
                'The "%s" unit measurement family (%s) is not the same of the provided "%s" unit measurement (%s)',
                'SQUARE_METER',
                'Area',
                'KILOGRAM',
                'Weight'
            ))
        )->during('convert', [
            '23.0000',
            'KILOGRAM',
            'SQUARE_METER',
        ]);
    }

    function it_returns_same_value_when_unit_is_standard_akeneo_unit_measurement()
    {
        $this->convert(
            '23.0000',
            'KILOGRAM',
            null
        )->shouldReturn((float) 23);
    }

    function it_returns_converted_value_when_unit_is_not_standard_akeneo_unit_measurement()
    {
        $this->convert(
            '0.023',
            'TON',
            null
        )->shouldReturn(23.0);
    }

    function it_returns_converted_value_when_unit_is_not_specified_unit_measurement()
    {
        $this->convert(
            '0.001',
            'TON',
            'GRAM'
        )->shouldReturn(1000.0);
    }
}
