<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use InvalidArgumentException;
use RuntimeException;
use Sylius\Component\Core\Model\ChannelInterface;
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
        // todo: this value handler shares the same exact code of the metric property value handler
        if (!$this->supports($subject, $attribute, $value)) {
            throw new InvalidArgumentException(
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
        $product = $productVariant->getProduct();
        Assert::isInstanceOf($product, ProductInterface::class);

        $productChannelCodes = array_map(static function (ChannelInterface $channel): ?string {
            return $channel->getCode();
        }, $product->getChannels()->toArray());
        $productChannelCodes = array_filter($productChannelCodes);

        foreach ($value as $valueData) {
            if (array_key_exists('scope', $valueData) && $valueData['scope'] !== null && !in_array($valueData['scope'], $productChannelCodes, true)) {
                continue;
            }

            $valueToSet = $valueData['data'];
            if ($this->propertyAccessor->isWritable($productVariant, $this->propertyPath)) {
                $this->propertyAccessor->setValue($productVariant, $this->propertyPath, $valueToSet);
                Assert::isInstanceOf($productVariant, ProductVariantInterface::class);
                $hasBeenSet = true;
            }

            if ($this->propertyAccessor->isWritable($product, $this->propertyPath)) {
                $this->propertyAccessor->setValue($product, $this->propertyPath, $valueToSet);
                Assert::isInstanceOf($product, ProductInterface::class);
                $hasBeenSet = true;
            }

            if ($hasBeenSet) {
                break;
            }
        }

        if (!$hasBeenSet) {
            throw new RuntimeException(
                sprintf(
                    'Property path "%s" is not writable on both %s and %s but it should be for at least once.',
                    $this->propertyPath,
                    get_class($productVariant),
                    get_class($product)
                )
            );
        }
    }
}
