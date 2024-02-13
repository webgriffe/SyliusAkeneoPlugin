<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Converter;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use InvalidArgumentException;
use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Sylius\Component\Attribute\AttributeType\TextAttributeType;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webgriffe\SyliusAkeneoPlugin\ProductAttributeHelperTrait;
use Webmozart\Assert\Assert;

final class ValueConverter implements ValueConverterInterface
{
    use ProductAttributeHelperTrait;

    /**
     * @param RepositoryInterface<ProductAttributeInterface>|null $attributeRepository
     */
    public function __construct(
        private TranslatorInterface $translator,
        private ?AkeneoPimClientInterface $akeneoPimClient = null,
        private ?RepositoryInterface $attributeRepository = null
    ) {
        if ($this->akeneoPimClient === null) {
            trigger_deprecation(
                'webgriffe/sylius-akeneo-plugin',
                '1.0',
                'The $akeneoPimClient argument is required.'
            );
        }
    }

    public function convert(AttributeInterface $attribute, array|bool|int|string $value, string $localeCode): array|bool|int|string
    {
        if (is_array($value) && $attribute->getType() !== SelectAttributeType::TYPE) {
            // Akeneo metrical attribute
            if ($attribute->getType() === TextAttributeType::TYPE) {
                if (!array_key_exists('amount', $value)) {
                    throw new \LogicException('Amount key not found');
                }
                $floatAmount = (float) ($value['amount']);
                if (!array_key_exists('unit', $value)) {
                    throw new \LogicException('Unit key not found');
                }
                $unit = (string) $value['unit'];

                return $this->translator->trans('webgriffe_sylius_akeneo.ui.metric_amount_unit', ['unit' => $unit, 'amount' => $floatAmount], null, $localeCode);
            }

            return $value;
        }
        if (!is_bool($value) && $attribute->getType() === SelectAttributeType::TYPE) {
            $value = (array) $value;
            if (!$this->isAttributeValueBetweenProductAttributeChoices($attribute, $value)) {
                $attributeCode = $attribute->getCode();
                Assert::string($attributeCode);
                if ($attribute instanceof ProductAttributeInterface &&
                    $this->akeneoPimClient !== null &&
                    $this->attributeRepository !== null
                ) {
                    // Try to re-import attribute configuration
                    $this->importAttributeConfiguration($attributeCode, $attribute);

                    if ($this->isAttributeValueBetweenProductAttributeChoices($attribute, $value)) {
                        return $value;
                    }
                }

                throw new InvalidArgumentException(
                    sprintf(
                        'This select attribute can only save existing attribute options. ' .
                        'Attribute option codes [%s] for attribute "%s" does not exist.',
                        implode(', ', $value),
                        $attributeCode
                    ),
                );
            }
        }

        return $value;
    }

    private function isAttributeValueBetweenProductAttributeChoices(AttributeInterface $attribute, array $value): bool
    {
        $attributeConfiguration = $attribute->getConfiguration();
        if (!array_key_exists('choices', $attributeConfiguration)) {
            return false;
        }
        /** @var array $choices */
        $choices = $attributeConfiguration['choices'];
        $possibleAttributeOptionCodes = array_map('strval', array_keys($choices));
        /** @var string[] $invalid */
        $invalid = array_diff($value, $possibleAttributeOptionCodes);

        return count($invalid) === 0;
    }

    private function getAkeneoPimClient(): AkeneoPimClientInterface
    {
        $akeneoPimClient = $this->akeneoPimClient;
        Assert::notNull($akeneoPimClient);

        return $akeneoPimClient;
    }

    /**
     * @return RepositoryInterface<ProductAttributeInterface>
     */
    private function getAttributeRepository(): RepositoryInterface
    {
        $attributeRepository = $this->attributeRepository;
        Assert::notNull($attributeRepository);

        return $attributeRepository;
    }
}
