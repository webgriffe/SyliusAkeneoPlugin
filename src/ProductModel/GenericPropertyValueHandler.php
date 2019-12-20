<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ProductModel;

use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

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

    public function supports(ProductInterface $product, string $attribute, array $value): bool
    {
        return $attribute === $this->akeneoAttributeCode;
    }

    public function handle(ProductInterface $product, string $attribute, array $value)
    {
        if (!$this->supports($product, $attribute, $value)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Cannot handle Akeneo attribute "%s". %s only supports Akeneo attribute "%s".',
                    'not_supported_property',
                    self::class,
                    $this->akeneoAttributeCode
                )
            );
        }
        $this->propertyAccessor->setValue($product, $this->propertyPath, $value[0]['data']);
    }
}
