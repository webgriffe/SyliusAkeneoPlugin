<?php


namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;


use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Webgriffe\SyliusAkeneoPlugin\Converter\UnitMeasurementValueConverterInterface;
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
    private $akeneoUnitMeasurementCode;
    /**
     * @var UnitMeasurementValueConverterInterface
     */
    private $unitMeasurementValueConverter;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        UnitMeasurementValueConverterInterface $unitMeasurementValueConverter,
        string $akeneoAttributeCode,
        string $propertyPath,
        string $akeneoUnitMeasurementCode = null
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->unitMeasurementValueConverter = $unitMeasurementValueConverter;
        $this->akeneoAttributeCode = $akeneoAttributeCode;
        $this->propertyPath = $propertyPath;
        $this->akeneoUnitMeasurementCode = $akeneoUnitMeasurementCode;
    }

    /**
     * @param mixed $subject
     */
    public function supports($subject, string $attribute, array $value): bool
    {
        return $subject instanceof ProductVariantInterface && $attribute === $this->akeneoAttributeCode;
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
        return $this->unitMeasurementValueConverter->convert($data['amount'], $data['unit'], $this->akeneoUnitMeasurementCode);
    }
}
