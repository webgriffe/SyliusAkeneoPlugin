<?php


namespace Webgriffe\SyliusAkeneoPlugin\Converter;


use Webgriffe\SyliusAkeneoPlugin\MeasurementFamiliesApiClientInterface;

final class DefaultUnitMeasurementValueConverter implements DefaultUnitMeasurementValueConverterInterface
{
    /**
     * @var MeasurementFamiliesApiClientInterface
     */
    private $apiClient;

    /**
     * DefaultUnitMeasurementValueConverter constructor.
     */
    public function __construct(MeasurementFamiliesApiClientInterface $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * @throws \HttpException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function convert(string $amount, string $unitMeasurementCode, ?string $defaultAkeneoUnitMeasurementCode): float
    {
        $unitMeasurementFamily = $this->getUnitMeasurementFamilyByUnitMeasurementCode($unitMeasurementCode);
        $operationsToDefaultUnitMeasurement = $this->getOperationsForDefaultFromUnitMeasurement($unitMeasurementFamily['units'], $unitMeasurementCode);

        $value = (float)$amount;
        $value = $this->doOperations($operationsToDefaultUnitMeasurement, $value);

        if ($defaultAkeneoUnitMeasurementCode !== null && $unitMeasurementFamily['standard_unit_code'] !== $defaultAkeneoUnitMeasurementCode) {
            $operationsToReverse = $this->getOperationsForDefaultFromUnitMeasurement($unitMeasurementFamily['units'], $defaultAkeneoUnitMeasurementCode);
            $value = $this->doReverseOperations($operationsToReverse, $value);
        }

        return $value;
    }

    /**
     * @return array{code: string, labels: array{localeCode: string}, standard_unit_code: string, units: array{unitCode: array{code: string, labels: array<string, string>, convert_from_standard: array{operator: string, value: string}, symbol: string}}}
     * @throws \HttpException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getUnitMeasurementFamilyByUnitMeasurementCode(string $unitMeasurementCode): array
    {
        $unitMeasurementFamilies = $this->apiClient->getMeasurementFamilies();
        /** @var array{code: string, labels: array{localeCode: string}, standard_unit_code: string, units: array{unitCode: array{code: string, labels: array<string, string>, convert_from_standard: array{operator: string, value: string}, symbol: string}}} $unitMeasurementFamily */
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
     * @param float $value
     * @return float
     */
    protected function doOperations($operationsToDefaultUnitMeasurement, float $value): float
    {
        foreach ($operationsToDefaultUnitMeasurement as $operation) {
            switch ($operation['operator']) {
                case 'add':
                    $value += (float)$operation['value'];
                    break;
                case 'sub':
                    $value -= (float)$operation['value'];
                    break;
                case 'mul':
                    $value *= (float)$operation['value'];
                    break;
                case 'div':
                    $value /= (float)$operation['value'];
                    break;
                default:
                    break;
            }
        }
        return $value;
    }

    /**
     * @param array<array{operator: string, value: string}> $operationsToDefaultUnitMeasurement
     * @param float $value
     * @return float
     */
    protected function doReverseOperations($operationsToDefaultUnitMeasurement, float $value): float
    {
        foreach ($operationsToDefaultUnitMeasurement as $operation) {
            switch ($operation['operator']) {
                case 'add':
                    $value -= (float)$operation['value'];
                    break;
                case 'sub':
                    $value += (float)$operation['value'];
                    break;
                case 'mul':
                    $value /= (float)$operation['value'];
                    break;
                case 'div':
                    $value *= (float)$operation['value'];
                    break;
                default:
                    break;
            }
        }
        return $value;
    }

    /**
     * @param array{unitCode: array{code: string, labels: array<string, string>, convert_from_standard: array{operator: string, value: string}, symbol: string}} $units
     * @param string $unitMeasurementCode
     * @return array|array{array{operator: string, value: string}}
     */
    protected function getOperationsForDefaultFromUnitMeasurement(array $units, string $unitMeasurementCode): array
    {
        $operationsToDefaultUnitMeasurement = [];
        /** @var array{code: string, labels: array<string, string>, convert_from_standard: array{operator: string, value: string}, symbol: string} $unitMeasurement */
        foreach ($units as $unitMeasurement) {
            if ($unitMeasurement['code'] === $unitMeasurementCode) {
                $operationsToDefaultUnitMeasurement = $unitMeasurement['convert_from_standard'];
            }
        }
        return $operationsToDefaultUnitMeasurement;
    }
}
