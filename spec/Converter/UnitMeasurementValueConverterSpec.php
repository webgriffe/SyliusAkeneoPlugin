<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\Converter;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\MeasurementFamilyApiInterface;
use PhpSpec\ObjectBehavior;
use Webgriffe\SyliusAkeneoPlugin\Converter\UnitMeasurementValueConverter;
use Webgriffe\SyliusAkeneoPlugin\Converter\UnitMeasurementValueConverterInterface;

class UnitMeasurementValueConverterSpec extends ObjectBehavior
{
    public function let(
        AkeneoPimClientInterface $apiClient,
        MeasurementFamilyApiInterface $measurementFamilyApi
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
      "FAKE_UM": {
        "code": "FAKE_UM",
        "labels": {
          "en_US": "Fake",
          "it_IT": "Falsa"
        },
        "convert_from_standard": [
          {
            "operator": "log",
            "value": "4"
          }
        ],
        "symbol": "fake"
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

        $apiClient->getMeasurementFamilyApi()->willReturn($measurementFamilyApi);
        $measurementFamilyApi->all()->willReturn(json_decode($unitMeasurementsFamilies, true));
        $this->beConstructedWith($apiClient);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(UnitMeasurementValueConverter::class);
    }

    function it_implements_unit_measurement_value_converter_interface()
    {
        $this->shouldHaveType(UnitMeasurementValueConverterInterface::class);
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

    function it_throws_exception_during_convert_when_operator_is_not_recognized()
    {
        $this->shouldThrow(
            new \LogicException(sprintf(
                'Unable to convert value, unrecognized operator. Found "%s", expected: "%s"',
                'log',
                implode(', ', ['add', 'sub', 'mul', 'div'])
            ))
        )->during('convert', [
            '23.0000',
            'FAKE_UM',
            null,
        ]);
    }

    function it_throws_exception_during_convert_when_unit_measurement_code_family_is_not_the_same_of_unit_code()
    {
        $this->shouldThrow(
            new \LogicException(sprintf(
                'The "%s" destination unit measurement family (%s) is not the same of the provided "%s" source unit measurement (%s)',
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
