<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Converter;

use InvalidArgumentException;
use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Sylius\Component\Attribute\AttributeType\TextAttributeType;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

final class ValueConverter implements ValueConverterInterface
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    #[\Override]
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
            /** @var string[] $value */
            $value = (array) $value;
            if (!$this->isAttributeValueBetweenProductAttributeChoices($attribute, $value)) {
                $attributeCode = $attribute->getCode();
                Assert::string($attributeCode);

                throw new InvalidArgumentException(
                    sprintf(
                        'This select attribute can only save existing attribute options. ' .
                        'Attribute option codes [%s] for attribute "%s" does not exist.',
                        implode(', ', $value),
                        $attributeCode,
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
        /** @var array<string, array> $choices */
        $choices = $attributeConfiguration['choices'];
        $possibleAttributeOptionCodes = array_map('strval', array_keys($choices));
        /** @var string[] $invalid */
        $invalid = array_diff($value, $possibleAttributeOptionCodes);

        return count($invalid) === 0;
    }
}
