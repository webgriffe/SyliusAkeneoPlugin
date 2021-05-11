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
      "ca_ES": "Àrea",
      "da_DK": "Areal",
      "de_DE": "Fläche",
      "en_GB": "Area",
      "en_NZ": "Area",
      "en_US": "Area",
      "es_ES": "Superficie",
      "fi_FI": "Alue",
      "fr_FR": "Surface",
      "it_IT": "Area",
      "ja_JP": "エリア",
      "pt_BR": "Área",
      "ru_RU": "Площадь",
      "sv_SE": "Område"
    },
    "standard_unit_code": "SQUARE_METER",
    "units": {
      "SQUARE_MILLIMETER": {
        "code": "SQUARE_MILLIMETER",
        "labels": {
          "ca_ES": "Mil·límetre quadrat",
          "da_DK": "Kvadrat millimeter",
          "de_DE": "Quadratmillimeter",
          "en_GB": "Square millimetre",
          "en_NZ": "Square millimetre",
          "en_US": "Square millimeter",
          "es_ES": "Milímetro cuadrado",
          "fi_FI": "Neliömillimetri",
          "fr_FR": "Millimètre carré",
          "it_IT": "Millimetro quadrato",
          "ja_JP": "平方ミリメートル",
          "pt_BR": "Milímetro quadrado",
          "ru_RU": "Квадратный миллиметр",
          "sv_SE": "Kvadratmillimeter"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "0.000001"
          }
        ],
        "symbol": "mm²"
      },
      "SQUARE_CENTIMETER": {
        "code": "SQUARE_CENTIMETER",
        "labels": {
          "ca_ES": "Centímetre quadrat",
          "da_DK": "Kvadratcentimeter",
          "de_DE": "Quadratzentimeter",
          "en_GB": "Square centimetre",
          "en_NZ": "Square centimetre",
          "en_US": "Square centimeter",
          "es_ES": "Centímetro cuadrado",
          "fi_FI": "Neliösenttimetri",
          "fr_FR": "Centimètre carré",
          "it_IT": "Centimetro quadrato",
          "ja_JP": "平方センチメートル",
          "pt_BR": "Centímetro quadrado",
          "ru_RU": "Квадратный сантиметр",
          "sv_SE": "Kvadratcentimeter"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "0.0001"
          }
        ],
        "symbol": "cm²"
      },
      "SQUARE_DECIMETER": {
        "code": "SQUARE_DECIMETER",
        "labels": {
          "ca_ES": "Decímetre quadrat",
          "da_DK": "Kvadrat decimeter",
          "de_DE": "Quadratdezimeter",
          "en_GB": "Square decimetre",
          "en_NZ": "Square decimetre",
          "en_US": "Square decimeter",
          "es_ES": "Decímetro cuadrado",
          "fi_FI": "Neliödesimetri",
          "fr_FR": "Décimètre carré",
          "it_IT": "Decimetro quadrato",
          "ja_JP": "平方デシメートル",
          "pt_BR": "Decímetro quadrado",
          "ru_RU": "Квадратный дециметр",
          "sv_SE": "Kvadratdecimeter"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "0.01"
          }
        ],
        "symbol": "dm²"
      },
      "SQUARE_METER": {
        "code": "SQUARE_METER",
        "labels": {
          "ca_ES": "Metre quadrat",
          "da_DK": "Kvadratmeter",
          "de_DE": "Quadratmeter",
          "en_GB": "Square metre",
          "en_NZ": "Square metre",
          "en_US": "Square meter",
          "es_ES": "Metro cuadrado",
          "fi_FI": "Neliömetri",
          "fr_FR": "Mètre carré",
          "it_IT": "Metro quadrato",
          "ja_JP": "平方メートル",
          "pt_BR": "Metro quadrado",
          "ru_RU": "Квадратный метр",
          "sv_SE": "Kvadratmeter"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "1"
          }
        ],
        "symbol": "m²"
      },
      "CENTIARE": {
        "code": "CENTIARE",
        "labels": {
          "ca_ES": "Centiàrees",
          "da_DK": "Centiare",
          "de_DE": "Quadratmeter",
          "en_GB": "Centiare",
          "en_NZ": "Centiare",
          "en_US": "Centiare",
          "es_ES": "Centiáreas",
          "fi_FI": "Senttiaari",
          "fr_FR": "Centiare",
          "it_IT": "Centiara",
          "ja_JP": "センチアール",
          "pt_BR": "Centiare",
          "ru_RU": "Центнер",
          "sv_SE": "Kvadratmeter"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "1"
          }
        ],
        "symbol": "ca"
      },
      "SQUARE_DEKAMETER": {
        "code": "SQUARE_DEKAMETER",
        "labels": {
          "ca_ES": "Decàmetre quadrat",
          "da_DK": "Kvadrat dekameter",
          "de_DE": "Quadratdekameter",
          "en_GB": "Square decametre",
          "en_NZ": "Square dekametre",
          "en_US": "Square dekameter",
          "es_ES": "Dekametro cuadrado",
          "fi_FI": "Neliödekametri",
          "fr_FR": "Décamètre carré",
          "it_IT": "Decametro quadrato",
          "ja_JP": "平方デカメートル",
          "pt_BR": "Decametro quadrado",
          "ru_RU": "Квадратный декаметр",
          "sv_SE": "Kvadratdekameter"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "100"
          }
        ],
        "symbol": "dam²"
      },
      "ARE": {
        "code": "ARE",
        "labels": {
          "ca_ES": "Àrea",
          "da_DK": "Are",
          "de_DE": "Ar",
          "en_GB": "Sú",
          "en_NZ": "Are",
          "en_US": "Are",
          "es_ES": "Área",
          "fi_FI": "Aari",
          "fr_FR": "Are",
          "it_IT": "Ara",
          "ja_JP": "アール",
          "pt_BR": "Area",
          "ru_RU": "Ар",
          "sv_SE": "Hektar"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "100"
          }
        ],
        "symbol": "a"
      },
      "SQUARE_HECTOMETER": {
        "code": "SQUARE_HECTOMETER",
        "labels": {
          "ca_ES": "Hectòmetre quadrat",
          "da_DK": "Kvadrat hectometer",
          "de_DE": "Quadrathektometer",
          "en_GB": "Square hectometre",
          "en_NZ": "Square hectometre",
          "en_US": "Square hectometer",
          "es_ES": "Hectómetro cuadrado",
          "fi_FI": "Neliöhehtometri",
          "fr_FR": "Hectomètre carré",
          "it_IT": "Ettometro quadrato",
          "ja_JP": "平方ヘクトメートル",
          "pt_BR": "Hectómetro quadrado",
          "ru_RU": "Квадратный гектометр",
          "sv_SE": "Kvadrathektameter"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "10000"
          }
        ],
        "symbol": "hm²"
      },
      "HECTARE": {
        "code": "HECTARE",
        "labels": {
          "ca_ES": "Hectàrees",
          "da_DK": "Hektar",
          "de_DE": "Hektar",
          "en_GB": "Hectare",
          "en_NZ": "Hectare",
          "en_US": "Hectare",
          "es_ES": "Hectárea",
          "fi_FI": "Hehtaari",
          "fr_FR": "Hectare",
          "it_IT": "Ettaro",
          "ja_JP": "ヘクタール",
          "pt_BR": "Hectare",
          "ru_RU": "Гектар",
          "sv_SE": "Hektar"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "10000"
          }
        ],
        "symbol": "ha"
      },
      "SQUARE_KILOMETER": {
        "code": "SQUARE_KILOMETER",
        "labels": {
          "ca_ES": "Quilòmetre quadrat",
          "da_DK": "Kvadrat kilometer",
          "de_DE": "Quadratkilometer",
          "en_GB": "Square kilometre",
          "en_NZ": "Square kilometre",
          "en_US": "Square kilometer",
          "es_ES": "Kilómetro cuadrado",
          "fi_FI": "Neliökilometri",
          "fr_FR": "Kilomètre carré",
          "it_IT": "Chilometro quadrato",
          "ja_JP": "平方キロメートル",
          "pt_BR": "Quilômetro quadrado",
          "ru_RU": "Квадратный километр",
          "sv_SE": "Kvadratkilometer"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "1000000"
          }
        ],
        "symbol": "km²"
      },
      "SQUARE_MIL": {
        "code": "SQUARE_MIL",
        "labels": {
          "ca_ES": "Mil quadrat",
          "da_DK": "Kvadrat mil",
          "de_DE": "Quadratmil",
          "en_GB": "Square mil",
          "en_NZ": "Square mil",
          "en_US": "Square mil",
          "es_ES": "Mil cuadrado",
          "fi_FI": "Neliötuhannesosatuuma",
          "fr_FR": "Mil carré",
          "it_IT": "Mil quadrati",
          "ja_JP": "平方ミル",
          "pt_BR": "Mil quadrada",
          "ru_RU": "Квадратная миля",
          "sv_SE": "Kvadratmil"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "0.00000000064516"
          }
        ],
        "symbol": "sq mil"
      },
      "SQUARE_INCH": {
        "code": "SQUARE_INCH",
        "labels": {
          "ca_ES": "Polzada quadrada",
          "da_DK": "Kvadrattomme",
          "de_DE": "Quadratzoll",
          "en_GB": "Square inch",
          "en_NZ": "Square inch",
          "en_US": "Square inch",
          "es_ES": "Pulgada cuadrada",
          "fi_FI": "Neliötuuma",
          "fr_FR": "Pouce carré",
          "it_IT": "Pollice quadrato",
          "ja_JP": "平方インチ",
          "pt_BR": "Polegada quadrada",
          "ru_RU": "Квадратный дюйм",
          "sv_SE": "Kvadrattum"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "0.00064516"
          }
        ],
        "symbol": "in²"
      },
      "SQUARE_FOOT": {
        "code": "SQUARE_FOOT",
        "labels": {
          "ca_ES": "Peu quadrat",
          "da_DK": "Kvadratfod",
          "de_DE": "Quadratfuß",
          "en_GB": "Square foot",
          "en_NZ": "Square foot",
          "en_US": "Square foot",
          "es_ES": "Pies cuadrados",
          "fi_FI": "Neliöjalka",
          "fr_FR": "Pied carré",
          "it_IT": "Piede quadrato",
          "ja_JP": "平方フィート",
          "pt_BR": "Pé quadrado",
          "ru_RU": "Квадратный фут",
          "sv_SE": "Kvadratfot"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "0.09290304"
          }
        ],
        "symbol": "ft²"
      },
      "SQUARE_YARD": {
        "code": "SQUARE_YARD",
        "labels": {
          "ca_ES": "Iarda quadrada",
          "da_DK": "Kvadrat yard",
          "de_DE": "Quadratyard",
          "en_GB": "Square yard",
          "en_NZ": "Square yard",
          "en_US": "Square yard",
          "es_ES": "Yarda cuadrada",
          "fi_FI": "Neliöjaardi",
          "fr_FR": "Yard carré",
          "it_IT": "Yard quadrata",
          "ja_JP": "平方ヤード",
          "pt_BR": "Jarda quadrada",
          "ru_RU": "Квадратный ярд",
          "sv_SE": "Kvadratyard"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "0.83612736"
          }
        ],
        "symbol": "yd²"
      },
      "ARPENT": {
        "code": "ARPENT",
        "labels": {
          "ca_ES": "Arpent",
          "da_DK": "Arpent",
          "de_DE": "Arpent",
          "en_GB": "Arpent",
          "en_NZ": "Arpent",
          "en_US": "Arpent",
          "es_ES": "Arpende",
          "fi_FI": "Eekkeri",
          "fr_FR": "Arpent",
          "it_IT": "Arpenti",
          "ja_JP": "アルパン",
          "pt_BR": "Arpent",
          "ru_RU": "Арпан",
          "sv_SE": "Arpent"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "3418.89"
          }
        ],
        "symbol": "arpent"
      },
      "ACRE": {
        "code": "ACRE",
        "labels": {
          "ca_ES": "Acre",
          "da_DK": "Tønder",
          "de_DE": "Morgen",
          "en_GB": "Acre",
          "en_NZ": "Acre",
          "en_US": "Acre",
          "es_ES": "Acre",
          "fi_FI": "Eekkeri",
          "fr_FR": "Acre",
          "it_IT": "Acri",
          "ja_JP": "エーカー",
          "pt_BR": "Acre",
          "ru_RU": "Акр",
          "sv_SE": "Tunnland"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "4046.856422"
          }
        ],
        "symbol": "A"
      },
      "SQUARE_FURLONG": {
        "code": "SQUARE_FURLONG",
        "labels": {
          "ca_ES": "Furlong quadrat",
          "da_DK": "Kvadratisk furlong",
          "de_DE": "Quadrat-Achtelmeile",
          "en_GB": "Square furlong",
          "en_NZ": "Square furlong",
          "en_US": "Square furlong",
          "es_ES": "Estadio cuadrado",
          "fi_FI": "Vakomitta",
          "fr_FR": "Furlong carré",
          "it_IT": "Furlong quadrato",
          "ja_JP": "平方ハロン",
          "pt_BR": "Furlong quadrado",
          "ru_RU": "Квадратный фурлонг",
          "sv_SE": "Kvadratfurlong"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "40468.726"
          }
        ],
        "symbol": "fur²"
      },
      "SQUARE_MILE": {
        "code": "SQUARE_MILE",
        "labels": {
          "ca_ES": "Milla quadrada",
          "da_DK": "Kvadrat mil",
          "de_DE": "Quadratmeile",
          "en_GB": "Square mile",
          "en_NZ": "Square mile",
          "en_US": "Square mile",
          "es_ES": "Milla cuadrada",
          "fi_FI": "Neliömaili",
          "fr_FR": "Mile carré",
          "it_IT": "Miglio quadrato",
          "ja_JP": "平方マイル",
          "pt_BR": "Milha quadrada",
          "ru_RU": "Квадратная миля",
          "sv_SE": "Kvadratmile"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "2589988.110336"
          }
        ],
        "symbol": "mi²"
      }
    }
  },
  {
    "code": "Weight",
    "labels": {
      "ca_ES": "Pes",
      "da_DK": "Vægt",
      "de_DE": "Gewicht",
      "en_GB": "Weight",
      "en_NZ": "Weight",
      "en_US": "Weight",
      "es_ES": "Peso",
      "fi_FI": "Paino",
      "fr_FR": "Poids",
      "it_IT": "Peso",
      "ja_JP": "重量",
      "pt_BR": "Peso",
      "ru_RU": "Вес",
      "sv_SE": "Vikt"
    },
    "standard_unit_code": "KILOGRAM",
    "units": {
      "MICROGRAM": {
        "code": "MICROGRAM",
        "labels": {
          "en_US": "Microgram",
          "fr_FR": "Microgramme"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "0.000001"
          }
        ],
        "symbol": "μg"
      },
      "MILLIGRAM": {
        "code": "MILLIGRAM",
        "labels": {
          "ca_ES": "Mil·ligram",
          "da_DK": "Milligram",
          "de_DE": "Milligramm",
          "en_GB": "Milligram",
          "en_NZ": "Milligram",
          "en_US": "Milligram",
          "es_ES": "Miligramo",
          "fi_FI": "Milligramma",
          "fr_FR": "Milligramme",
          "it_IT": "Milligrammo",
          "ja_JP": "ミリグラム",
          "pt_BR": "Miligrama",
          "ru_RU": "Миллиграмм",
          "sv_SE": "Milligram"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "0.000001"
          }
        ],
        "symbol": "mg"
      },
      "GRAM": {
        "code": "GRAM",
        "labels": {
          "ca_ES": "Gram",
          "da_DK": "Gram",
          "de_DE": "Gramm",
          "en_GB": "Gram",
          "en_NZ": "Gram",
          "en_US": "Gram",
          "es_ES": "Gramo",
          "fi_FI": "Gramma",
          "fr_FR": "Gramme",
          "it_IT": "Gram",
          "ja_JP": "グラム",
          "pt_BR": "Grama",
          "ru_RU": "Грамм",
          "sv_SE": "Gram"
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
          "ca_ES": "Quilogram",
          "da_DK": "Kilogram",
          "de_DE": "Kilogramm",
          "en_GB": "Kilogram",
          "en_NZ": "Kilogram",
          "en_US": "Kilogram",
          "es_ES": "Kilogramos",
          "fi_FI": "Kilogramma",
          "fr_FR": "Kilogramme",
          "it_IT": "Chilogrammo",
          "ja_JP": "キログラム",
          "pt_BR": "Quilograma",
          "ru_RU": "Килограмм",
          "sv_SE": "Kilogram"
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
          "ca_ES": "Tona",
          "da_DK": "Ton",
          "de_DE": "Tonne",
          "en_GB": "Ton",
          "en_NZ": "Ton",
          "en_US": "Ton",
          "es_ES": "Tonelada",
          "fi_FI": "Tonni",
          "fr_FR": "Tonne",
          "it_IT": "Tonnellata",
          "ja_JP": "トン",
          "pt_BR": "Tonelada",
          "ru_RU": "Тонна",
          "sv_SE": "Ton"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "1000"
          }
        ],
        "symbol": "t"
      },
      "GRAIN": {
        "code": "GRAIN",
        "labels": {
          "ca_ES": "Gra",
          "da_DK": "Gran",
          "de_DE": "Korn",
          "en_GB": "Grain",
          "en_NZ": "Grain",
          "en_US": "Grain",
          "es_ES": "Granulado",
          "fi_FI": "Graani",
          "fr_FR": "Grain",
          "it_IT": "Grain",
          "ja_JP": "粒",
          "pt_BR": "Grão",
          "ru_RU": "Гран",
          "sv_SE": "Grain"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "0.00006479891"
          }
        ],
        "symbol": "gr"
      },
      "DENIER": {
        "code": "DENIER",
        "labels": {
          "ca_ES": "Denier",
          "da_DK": "Denier",
          "de_DE": "Denier",
          "en_GB": "Denier",
          "en_NZ": "Denier",
          "en_US": "Denier",
          "es_ES": "Denier",
          "fi_FI": "Denier",
          "fr_FR": "Denier",
          "it_IT": "Denier",
          "ja_JP": "デニール",
          "pt_BR": "Denier",
          "ru_RU": "Денье",
          "sv_SE": "Denier"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "0.001275"
          }
        ],
        "symbol": "denier"
      },
      "ONCE": {
        "code": "ONCE",
        "labels": {
          "ca_ES": "Unça",
          "da_DK": "Ounce",
          "de_DE": "Unze",
          "en_GB": "Once",
          "en_NZ": "Once",
          "en_US": "Once",
          "es_ES": "Onza",
          "fi_FI": "Unssi",
          "fr_FR": "Once française",
          "it_IT": "Once",
          "ja_JP": "オンス",
          "pt_BR": "Onça",
          "ru_RU": "Унция",
          "sv_SE": "Once"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "0.03059"
          }
        ],
        "symbol": "once"
      },
      "MARC": {
        "code": "MARC",
        "labels": {
          "ca_ES": "Marc",
          "da_DK": "Marc",
          "de_DE": "Mark",
          "en_GB": "Marc",
          "en_NZ": "Marc",
          "en_US": "Marc",
          "es_ES": "Marc",
          "fi_FI": "Marc",
          "fr_FR": "Marc",
          "it_IT": "Marc",
          "ja_JP": "マルク",
          "pt_BR": "Marc",
          "ru_RU": "Марка",
          "sv_SE": "Marc"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "0.24475"
          }
        ],
        "symbol": "marc"
      },
      "LIVRE": {
        "code": "LIVRE",
        "labels": {
          "ca_ES": "Lliura",
          "da_DK": "Pund",
          "de_DE": "Livre",
          "en_GB": "Livre",
          "en_NZ": "Livre",
          "en_US": "Livre",
          "es_ES": "Libra",
          "fi_FI": "Livre",
          "fr_FR": "Livre française",
          "it_IT": "Livre",
          "ja_JP": "リーブル",
          "pt_BR": "Livre",
          "ru_RU": "Фунт",
          "sv_SE": "Livre"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "0.4895"
          }
        ],
        "symbol": "livre"
      },
      "OUNCE": {
        "code": "OUNCE",
        "labels": {
          "ca_ES": "Unça",
          "da_DK": "Ounce",
          "de_DE": "Unze",
          "en_GB": "Ounce",
          "en_NZ": "Ounce",
          "en_US": "Ounce",
          "es_ES": "Onza",
          "fi_FI": "Unssi",
          "fr_FR": "Once",
          "it_IT": "Oncia",
          "ja_JP": "オンス",
          "pt_BR": "Onça",
          "ru_RU": "Унция",
          "sv_SE": "Ounce"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "0.45359237"
          },
          {
            "operator": "div",
            "value": "16"
          }
        ],
        "symbol": "oz"
      },
      "POUND": {
        "code": "POUND",
        "labels": {
          "ca_ES": "Lliura",
          "da_DK": "Pund",
          "de_DE": "Pfund",
          "en_GB": "Pound",
          "en_NZ": "Pound",
          "en_US": "Pound",
          "es_ES": "Libra",
          "fi_FI": "Pauna",
          "fr_FR": "Livre",
          "it_IT": "Pound",
          "ja_JP": "ポンド",
          "pt_BR": "Libra",
          "ru_RU": "Фунт",
          "sv_SE": "Pund"
        },
        "convert_from_standard": [
          {
            "operator": "mul",
            "value": "0.45359237"
          }
        ],
        "symbol": "lb"
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
