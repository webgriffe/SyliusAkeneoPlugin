<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Converter;

use Webgriffe\SyliusAkeneoPlugin\MeasurementFamiliesApiClientInterface;
use Webmozart\Assert\Assert;

final class UnitMeasurementValueConverter implements UnitMeasurementValueConverterInterface
{
    private const RECOGNIZED_OPERATORS = ['add', 'sub', 'mul', 'div'];

    /**
     * UnitMeasurementValueConverter constructor.
     */
    public function __construct(private MeasurementFamiliesApiClientInterface $apiClient)
    {
    }

    public function convert(string $amount, string $sourceUnitMeasurementCode, ?string $destinationUnitMeasurementCode): float
    {
        $unitMeasurementFamily = $this->getUnitMeasurementFamilyByUnitMeasurementCode($sourceUnitMeasurementCode);
        if ($destinationUnitMeasurementCode !== null) {
            $unitMeasurementFamilyToUse = $this->getUnitMeasurementFamilyByUnitMeasurementCode($destinationUnitMeasurementCode);
            Assert::eq($unitMeasurementFamilyToUse, $unitMeasurementFamily, sprintf(
                'The "%s" destination unit measurement family (%s) is not the same of the provided "%s" source unit measurement (%s)',
                $destinationUnitMeasurementCode,
                $unitMeasurementFamilyToUse['code'],
                $sourceUnitMeasurementCode,
                $unitMeasurementFamily['code']
            ));
        }
        /** @var array{array{operator: string, value: string}} $operationsToDefaultUnitMeasurement */
        $operationsToDefaultUnitMeasurement = $this->getOperationsForDefaultFromUnitMeasurement($unitMeasurementFamily['units'], $sourceUnitMeasurementCode);

        $value = (float) $amount;
        $value = $this->doOperations($operationsToDefaultUnitMeasurement, $value);

        if ($destinationUnitMeasurementCode !== null && $unitMeasurementFamily['standard_unit_code'] !== $destinationUnitMeasurementCode) {
            /** @var array{array{operator: string, value: string}} $operationsToReverse */
            $operationsToReverse = $this->getOperationsForDefaultFromUnitMeasurement($unitMeasurementFamily['units'], $destinationUnitMeasurementCode);
            $value = $this->doReverseOperations($operationsToReverse, $value);
        }

        return $value;
    }

    /**
     * @return array{code: string, labels: array{localeCode: string}, standard_unit_code: string, units: array{unitCode: array{code: string, labels: array<string, string>, convert_from_standard: array{operator: string, value: string}, symbol: string}}}
     */
    private function getUnitMeasurementFamilyByUnitMeasurementCode(string $unitMeasurementCode): array
    {
        $unitMeasurementFamilies = $this->apiClient->getMeasurementFamilies();
        foreach ($unitMeasurementFamilies as $unitMeasurementFamily) {
            if (array_key_exists($unitMeasurementCode, $unitMeasurementFamily['units'])) {
                return $unitMeasurementFamily;
            }
        }

        throw new \LogicException(
            sprintf(
                'Unable to retrieve unit measurement family for the "%s" unit measurement code',
                $unitMeasurementCode
            )
        );
    }

    /**
     * @param array<array{operator: string, value: string}> $operationsToDefaultUnitMeasurement
     */
    private function doOperations($operationsToDefaultUnitMeasurement, float $value): float
    {
        foreach ($operationsToDefaultUnitMeasurement as $operation) {
            switch ($operation['operator']) {
                case 'add':
                    $value += (float) $operation['value'];

                    break;
                case 'sub':
                    $value -= (float) $operation['value'];

                    break;
                case 'mul':
                    $value *= (float) $operation['value'];

                    break;
                case 'div':
                    $value /= (float) $operation['value'];

                    break;
                default:
                    throw new \LogicException(sprintf(
                        'Unable to convert value, unrecognized operator. Found "%s", expected: "%s"',
                        $operation['operator'],
                        implode(', ', self::RECOGNIZED_OPERATORS)
                    ));
            }
        }

        return $value;
    }

    /**
     * @param array<array{operator: string, value: string}> $operationsToDefaultUnitMeasurement
     */
    private function doReverseOperations($operationsToDefaultUnitMeasurement, float $value): float
    {
        foreach ($operationsToDefaultUnitMeasurement as $operation) {
            switch ($operation['operator']) {
                case 'add':
                    $value -= (float) $operation['value'];

                    break;
                case 'sub':
                    $value += (float) $operation['value'];

                    break;
                case 'mul':
                    $value /= (float) $operation['value'];

                    break;
                case 'div':
                    $value *= (float) $operation['value'];

                    break;
                default:
                    throw new \LogicException(sprintf(
                        'Unable to convert value, unrecognized operator. Found "%s", expected: "%s"',
                        $operation['operator'],
                        implode(', ', self::RECOGNIZED_OPERATORS)
                    ));
            }
        }

        return $value;
    }

    /**
     * @param array{unitCode: array{code: string, labels: array<string, string>, convert_from_standard: array{operator: string, value: string}, symbol: string}} $units
     *
     * @return array|array{array{operator: string, value: string}}
     */
    private function getOperationsForDefaultFromUnitMeasurement(array $units, string $unitMeasurementCode): array
    {
        $operationsToDefaultUnitMeasurement = [];
        foreach ($units as $unitMeasurement) {
            if ($unitMeasurement['code'] === $unitMeasurementCode) {
                $operationsToDefaultUnitMeasurement = $unitMeasurement['convert_from_standard'];
            }
        }

        return $operationsToDefaultUnitMeasurement;
    }
}
