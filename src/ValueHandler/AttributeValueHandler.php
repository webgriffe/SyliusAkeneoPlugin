<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Sylius\Component\Attribute\AttributeType\CheckboxAttributeType;
use Sylius\Component\Attribute\AttributeType\IntegerAttributeType;
use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Sylius\Component\Attribute\AttributeType\TextareaAttributeType;
use Sylius\Component\Attribute\AttributeType\TextAttributeType;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverter;
use Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverterInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;
use Webmozart\Assert\Assert;

final class AttributeValueHandler implements ValueHandlerInterface
{
    /** @var RepositoryInterface */
    private $attributeRepository;

    /** @var FactoryInterface */
    private $factory;

    /** @var TranslationLocaleProviderInterface */
    private $localeProvider;

    /** @var ValueConverterInterface */
    private $valueConverter;

    public function __construct(
        RepositoryInterface $attributeRepository,
        FactoryInterface $factory,
        TranslationLocaleProviderInterface $localeProvider,
        ValueConverterInterface $valueConverter = null
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->factory = $factory;
        $this->localeProvider = $localeProvider;
        if ($valueConverter === null) {
            trigger_deprecation(
                'webgriffe/sylius-akeneo-plugin',
                '1.8',
                'Not passing a value converter to "%s" is deprecated and will be removed in %s.',
                __CLASS__,
                '2.0'
            );
            $valueConverter = new ValueConverter();
        }
        $this->valueConverter = $valueConverter;
    }

    /**
     * @inheritdoc
     */
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

    /**
     * @inheritdoc
     */
    public function handle($subject, string $attributeCode, array $value): void
    {
        if (!$subject instanceof ProductVariantInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'This attribute value handler only supports instances of %s, %s given.',
                    ProductVariantInterface::class,
                    is_object($subject) ? get_class($subject) : gettype($subject)
                )
            );
        }

        /** @var AttributeInterface|null $attribute */
        $attribute = $this->attributeRepository->findOneBy(['code' => $attributeCode]);

        if ($attribute === null) {
            throw new \InvalidArgumentException(
                sprintf(
                    'This attribute value handler only supports existing attributes. ' .
                    'Attribute with the given %s code does not exist.',
                    $attributeCode
                )
            );
        }

        $availableLocalesCodes = $this->localeProvider->getDefinedLocalesCodes();

        /** @var ProductInterface|null $product */
        $product = $subject->getProduct();
        Assert::isInstanceOf($product, ProductInterface::class);
        $productChannelCodes = array_map(static function (ChannelInterface $channel): ?string {
            return $channel->getCode();
        }, $product->getChannels()->toArray());
        $productChannelCodes = array_filter($productChannelCodes);
        foreach ($value as $valueData) {
            if (array_key_exists('scope', $valueData) && $valueData['scope'] !== null && !in_array($valueData['scope'], $productChannelCodes, true)) {
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
        ProductInterface $product
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

        $productOptions = $options->filter(function (ProductOptionInterface $option) use ($attributeCode): bool {
            return $option->getCode() === $attributeCode;
        });

        return !$productOptions->isEmpty();
    }
}
