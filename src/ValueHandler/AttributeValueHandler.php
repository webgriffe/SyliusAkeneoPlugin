<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

if (!interface_exists(\Sylius\Resource\Factory\FactoryInterface::class)) {
    class_alias(\Sylius\Resource\Factory\FactoryInterface::class, \Sylius\Component\Resource\Factory\FactoryInterface::class);
}
if (!interface_exists(\Sylius\Resource\Doctrine\Persistence\RepositoryInterface::class)) {
    class_alias(\Sylius\Resource\Doctrine\Persistence\RepositoryInterface::class, \Sylius\Component\Resource\Repository\RepositoryInterface::class);
}
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use InvalidArgumentException;
use Sylius\Component\Attribute\AttributeType\CheckboxAttributeType;
use Sylius\Component\Attribute\AttributeType\IntegerAttributeType;
use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Sylius\Component\Attribute\AttributeType\TextareaAttributeType;
use Sylius\Component\Attribute\AttributeType\TextAttributeType;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverterInterface;
use Webgriffe\SyliusAkeneoPlugin\ProductAttributeHelperTrait;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;
use Webmozart\Assert\Assert;

final class AttributeValueHandler implements ValueHandlerInterface
{
    use ProductAttributeHelperTrait;

    /**
     * @param RepositoryInterface<ProductAttributeInterface> $attributeRepository
     * @param FactoryInterface<ProductAttributeValueInterface> $factory
     */
    public function __construct(
        private RepositoryInterface $attributeRepository,
        private FactoryInterface $factory,
        private TranslationLocaleProviderInterface $localeProvider,
        private ValueConverterInterface $valueConverter,
        private ?AkeneoPimClientInterface $akeneoPimClient = null,
    ) {
        if ($this->akeneoPimClient === null) {
            trigger_deprecation(
                'webgriffe/sylius-akeneo-plugin',
                'v2.6.0',
                'Not passing a "%s" instance to "%s" constructor is deprecated and will not be possible anymore in the next major version.',
                AkeneoPimClientInterface::class,
                self::class,
            );
        }
    }

    public function supports($subject, string $attributeCode, array $value): bool
    {
        if (!$subject instanceof ProductVariantInterface) {
            return false;
        }

        if ($this->isProductOption($subject, $attributeCode)) {
            return false;
        }
        $attribute = $this->attributeRepository->findOneBy(['code' => $attributeCode]);

        return $attribute !== null && $this->hasSupportedType($attribute);
    }

    public function handle($subject, string $attributeCode, array $value): void
    {
        if (!$subject instanceof ProductVariantInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'This attribute value handler only supports instances of %s, %s given.',
                    ProductVariantInterface::class,
                    get_debug_type($subject),
                ),
            );
        }

        $attribute = $this->attributeRepository->findOneBy(['code' => $attributeCode]);
        if ($attribute === null) {
            throw new InvalidArgumentException(
                sprintf(
                    'This attribute value handler only supports existing attributes. ' .
                    'Attribute with the given %s code does not exist.',
                    $attributeCode,
                ),
            );
        }
        // TODO: Find a way to update attribute options only when they change or when needed, not every time
        if ($this->akeneoPimClient !== null &&
            $attribute->getType() === SelectAttributeType::TYPE
        ) {
            $this->importAttributeConfiguration($attributeCode, $attribute);
        }

        $availableLocalesCodes = $this->localeProvider->getDefinedLocalesCodes();

        /** @var ProductInterface|null $product */
        $product = $subject->getProduct();
        Assert::isInstanceOf($product, ProductInterface::class);
        $productChannelCodes = array_map(static fn (ChannelInterface $channel): ?string => $channel->getCode(), $product->getChannels()->toArray());
        $productChannelCodes = array_filter($productChannelCodes);

        $updatedLocales = [];
        foreach ($value as $valueData) {
            if (!is_array($valueData)) {
                throw new InvalidArgumentException(sprintf('Invalid Akeneo value data: expected an array, "%s" given.', gettype($valueData)));
            }
            if (!array_key_exists('scope', $valueData)) {
                throw new InvalidArgumentException('Invalid Akeneo value data: required "scope" information was not found.');
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
            $updatedLocales = array_merge($updatedLocales, $localeCodesToSet);

            foreach ($localeCodesToSet as $localeCode) {
                $this->handleAttributeValue($attribute, $valueData['data'], $localeCode, $product);
            }
        }
        // Remove attribute values for locales that are no more present in the Akeneo data
        foreach ($availableLocalesCodes as $availableLocaleCode) {
            if (!in_array($availableLocaleCode, $updatedLocales, true)) {
                $this->handleAttributeValue($attribute, null, $availableLocaleCode, $product);
            }
        }
    }

    /**
     * @param array|int|string|bool|null $value
     */
    private function handleAttributeValue(
        ProductAttributeInterface $attribute,
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
            $attributeValue = $this->factory->createNew();
        }

        $attributeValue->setAttribute($attribute);
        $attributeValue->setValue($this->valueConverter->convert($attribute, $value, $localeCode));
        $attributeValue->setLocaleCode($localeCode);

        $product->addAttribute($attributeValue);
    }

    private function hasSupportedType(ProductAttributeInterface $attribute): bool
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

    private function getAkeneoPimClient(): AkeneoPimClientInterface
    {
        $akeneoPimClient = $this->akeneoPimClient;
        Assert::notNull($akeneoPimClient);

        return $akeneoPimClient;
    }
}
