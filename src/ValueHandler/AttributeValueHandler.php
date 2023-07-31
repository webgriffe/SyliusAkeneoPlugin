<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Sylius\Component\Attribute\AttributeType\CheckboxAttributeType;
use Sylius\Component\Attribute\AttributeType\IntegerAttributeType;
use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Sylius\Component\Attribute\AttributeType\TextareaAttributeType;
use Sylius\Component\Attribute\AttributeType\TextAttributeType;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverterInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;
use Webmozart\Assert\Assert;

final class AttributeValueHandler implements ValueHandlerInterface
{
    /**
     * @param RepositoryInterface<AttributeInterface> $attributeRepository
     * @param FactoryInterface<ProductAttributeValueInterface> $factory
     */
    public function __construct(
        private RepositoryInterface $attributeRepository,
        private FactoryInterface $factory,
        private TranslationLocaleProviderInterface $localeProvider,
        private ValueConverterInterface $valueConverter,
    ) {
    }

    public function supports($subject, string $attributeCode, array $value): bool
    {
        if (!$subject instanceof ProductVariantInterface) {
            return false;
        }

        if ($this->isProductOption($subject, $attributeCode)) {
            return false;
        }

        /** @var AttributeInterface|null $attribute */
        $attribute = $this->attributeRepository->findOneBy(['code' => $attributeCode]);

        return $attribute !== null && $this->hasSupportedType($attribute);
    }

    public function handle($subject, string $attributeCode, array $value): void
    {
        if (!$subject instanceof ProductVariantInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'This attribute value handler only supports instances of %s, %s given.',
                    ProductVariantInterface::class,
                    get_debug_type($subject),
                ),
            );
        }

        /** @var AttributeInterface|null $attribute */
        $attribute = $this->attributeRepository->findOneBy(['code' => $attributeCode]);

        if ($attribute === null) {
            throw new \InvalidArgumentException(
                sprintf(
                    'This attribute value handler only supports existing attributes. ' .
                    'Attribute with the given %s code does not exist.',
                    $attributeCode,
                ),
            );
        }

        $availableLocalesCodes = $this->localeProvider->getDefinedLocalesCodes();

        /** @var ProductInterface|null $product */
        $product = $subject->getProduct();
        Assert::isInstanceOf($product, ProductInterface::class);
        $productChannelCodes = array_map(static fn (ChannelInterface $channel): ?string => $channel->getCode(), $product->getChannels()->toArray());
        $productChannelCodes = array_filter($productChannelCodes);

        foreach ($value as $valueData) {
            if (!is_array($valueData)) {
                throw new \InvalidArgumentException(sprintf('Invalid Akeneo value data: expected an array, "%s" given.', gettype($valueData)));
            }
            if (!array_key_exists('scope', $valueData)) {
                throw new \InvalidArgumentException('Invalid Akeneo value data: required "scope" information was not found.');
            }
            if ($valueData['scope'] !== null && !in_array($valueData['scope'], $productChannelCodes, true)) {
                continue;
            }

            $localeCodesToSet = $availableLocalesCodes;
            /** @var string|null $valueLocaleCode */
            $valueLocaleCode = $valueData['locale'];
            if ($valueLocaleCode !== null) {
                $localeCodesToSet = in_array($valueLocaleCode, $availableLocalesCodes, true) ? [$valueLocaleCode] : [];
            }

            foreach ($localeCodesToSet as $localeCode) {
                $this->handleAttributeValue($attribute, $valueData['data'], $localeCode, $product);
            }
        }
    }

    /**
     * @param array|int|string|bool|null $value
     */
    private function handleAttributeValue(
        AttributeInterface $attribute,
        $value,
        string $localeCode,
        ProductInterface $product,
    ): void {
        $attributeCode = $attribute->getCode();
        Assert::notNull($attributeCode);
        $attributeValue = $product->getAttributeByCodeAndLocale($attributeCode, $localeCode);

        if ($value === null) {
            if ($attributeValue !== null) {
                $product->removeAttribute($attributeValue);
            }

            return;
        }

        if ($attributeValue === null) {
            /** @var ProductAttributeValueInterface $attributeValue */
            $attributeValue = $this->factory->createNew();
        }

        $attributeValue->setAttribute($attribute);
        $attributeValue->setValue($this->valueConverter->convert($attribute, $value, $localeCode));
        $attributeValue->setLocaleCode($localeCode);

        $product->addAttribute($attributeValue);
    }

    private function hasSupportedType(AttributeInterface $attribute): bool
    {
        return $attribute->getType() === TextareaAttributeType::TYPE ||
            $attribute->getType() === TextAttributeType::TYPE ||
            $attribute->getType() === CheckboxAttributeType::TYPE ||
            $attribute->getType() === SelectAttributeType::TYPE ||
            $attribute->getType() === IntegerAttributeType::TYPE;
    }

    private function isProductOption(ProductVariantInterface $subject, string $attributeCode): bool
    {
        $product = $subject->getProduct();
        Assert::isInstanceOf($product, ProductInterface::class);
        $options = $product->getOptions();

        $productOptions = $options->filter(fn (ProductOptionInterface $option): bool => $option->getCode() === $attributeCode);

        return !$productOptions->isEmpty();
    }
}
