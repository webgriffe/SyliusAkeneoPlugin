<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;

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

    public function supports($subject, string $attribute, array $value): bool
    {
        return $attribute === $this->akeneoAttributeCode;
    }

    public function handle($subject, string $attribute, array $value)
    {
        if (!$this->supports($subject, $attribute, $value)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Cannot handle Akeneo attribute "%s". %s only supports Akeneo attribute "%s".',
                    'not_supported_property',
                    self::class,
                    $this->akeneoAttributeCode
                )
            );
        }
        $this->propertyAccessor->setValue($subject, $this->propertyPath, $value[0]['data']);
    }
}
