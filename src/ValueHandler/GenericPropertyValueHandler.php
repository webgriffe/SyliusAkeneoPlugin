<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;
use Webmozart\Assert\Assert;

final class GenericPropertyValueHandler implements ValueHandlerInterface
{
    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var string */
    private $akeneoAttributeCode;

    /** @var string */
    private $propertyPath;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        string $akeneoAttributeCode,
        string $propertyPath
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->akeneoAttributeCode = $akeneoAttributeCode;
        $this->propertyPath = $propertyPath;
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

        $productVariant = $subject;
        Assert::isInstanceOf($productVariant, ProductVariantInterface::class);
        if ($this->propertyAccessor->isWritable($productVariant, $this->propertyPath)) {
            /** @psalm-suppress MixedArgument */
            $this->propertyAccessor->setValue($productVariant, $this->propertyPath, $this->getValue($value));
            Assert::isInstanceOf($productVariant, ProductVariantInterface::class);
            $hasBeenSet = true;
        }

        $product = $productVariant->getProduct();
        Assert::isInstanceOf($product, ProductInterface::class);
        if ($this->propertyAccessor->isWritable($product, $this->propertyPath)) {
            /** @psalm-suppress MixedArgument */
            $this->propertyAccessor->setValue($product, $this->propertyPath, $this->getValue($value));
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
     * @return mixed
     */
    private function getValue(array $value)
    {
        Assert::keyExists($value, 0);
        /** @psalm-suppress MixedAssignment */
        $value = $value[0];
        Assert::isArray($value);
        Assert::keyExists($value, 'data');
        /** @psalm-suppress MixedAssignment */
        $value = $value['data'];

        return $value;
    }
}
