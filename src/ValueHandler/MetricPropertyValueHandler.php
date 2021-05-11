<?php


namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;


use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Webgriffe\SyliusAkeneoPlugin\Converter\DefaultUnitMeasurementValueConverterInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;
use Webmozart\Assert\Assert;

final class MetricPropertyValueHandler implements ValueHandlerInterface
{
    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var string */
    private $akeneoAttributeCode;

    /** @var string */
    private $propertyPath;

    /** @var string|null */
    private $defaultAkeneoUnitMeasurementCode;
    /**
     * @var DefaultUnitMeasurementValueConverterInterface
     */
    private $defaultUnitMeasurementValueConverter;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        DefaultUnitMeasurementValueConverterInterface $defaultUnitMeasurementValueConverter,
        string $akeneoAttributeCode,
        string $propertyPath,
        string $defaultAkeneoUnitMeasurementCode = null
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->defaultUnitMeasurementValueConverter = $defaultUnitMeasurementValueConverter;
        $this->akeneoAttributeCode = $akeneoAttributeCode;
        $this->propertyPath = $propertyPath;
        $this->defaultAkeneoUnitMeasurementCode = $defaultAkeneoUnitMeasurementCode;
    }

    /**
     * @param mixed $subject
     */
    public function supports($subject, string $attribute, array $value): bool
    {
        return $subject instanceof ProductVariantInterface &&
            $attribute === $this->akeneoAttributeCode &&
            array_key_exists('0', $value) &&
            is_array($value[0]) &&
            array_key_exists('data', $value[0]) &&
            is_array($value[0]['data']) &&
            array_key_exists('amount', $value[0]['data']) &&
            array_key_exists('unit', $value[0]['data'])
        ;
    }

    /**
     * @param mixed $subject
     */
    public function handle($subject, string $attribute, array $value): void
    {
        if (!$this->supports($subject, $attribute, $value)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Cannot handle Akeneo attribute "%s". %s only supports Akeneo attribute "%s".',
                    $attribute,
                    self::class,
                    $this->akeneoAttributeCode
                )
            );
        }
        $hasBeenSet = false;

        /** @var array<array-key, array{scope: string, locale: string, data: array{amount: string, unit: string}, linked_data: array}> $value */

        $productVariant = $subject;
        Assert::isInstanceOf($productVariant, ProductVariantInterface::class);
        if ($this->propertyAccessor->isWritable($productVariant, $this->propertyPath)) {
            $this->propertyAccessor->setValue($productVariant, $this->propertyPath, $this->getValue($value[0]['data']));
            Assert::isInstanceOf($productVariant, ProductVariantInterface::class);
            $hasBeenSet = true;
        }

        $product = $productVariant->getProduct();
        Assert::isInstanceOf($product, ProductInterface::class);
        if ($this->propertyAccessor->isWritable($product, $this->propertyPath)) {
            $this->propertyAccessor->setValue($product, $this->propertyPath, $this->getValue($value[0]['data']));
            Assert::isInstanceOf($product, ProductInterface::class);
            $hasBeenSet = true;
        }

        if (!$hasBeenSet) {
            throw new \RuntimeException(
                sprintf(
                    'Property path "%s" is not writable on both %s and %s but it should be for at least once.',
                    $this->propertyPath,
                    get_class($productVariant),
                    get_class($product)
                )
            );
        }
    }

    /**
     * @param array{amount: string, unit: string} $data
     */
    private function getValue(array $data): float
    {
        return $this->defaultUnitMeasurementValueConverter->convert($data['amount'], $data['unit'], $this->defaultAkeneoUnitMeasurementCode);
    }
}
