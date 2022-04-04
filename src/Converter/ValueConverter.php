<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Converter;

use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Sylius\Component\Attribute\AttributeType\TextAttributeType;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ValueConverter implements ValueConverterInterface
{
    private ?TranslatorInterface $translator;

    /**
     * ValueConverter constructor.
     */
    public function __construct(
        TranslatorInterface $translator = null
    ) {
        if ($translator === null) {
            trigger_deprecation(
                'webgriffe/sylius-akeneo-plugin',
                '1.8',
                'Not passing a translator to "%s" is deprecated and will be removed in %s.',
                self::class,
                '2.0'
            );
        }
        $this->translator = $translator;
    }

    /**
     * @inheritdoc
     */
    public function convert(AttributeInterface $attribute, $value, string $localeCode)
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
                if ($this->translator === null) {
                    return $floatAmount . ' ' . $unit;
                }

                return $this->translator->trans('webgriffe_sylius_akeneo.ui.metric_amount_unit', ['unit' => $unit, 'amount' => $floatAmount], null, $localeCode);
            }

            return $value;
        }
        if ($attribute->getType() === SelectAttributeType::TYPE && !is_bool($value)) {
            $value = (array) $value;
            $attributeConfiguration = $attribute->getConfiguration();
            /** @var array $choices */
            $choices = $attributeConfiguration['choices'];
            $possibleOptionsCodes = array_map('strval', array_keys($choices));
            $invalid = array_diff($value, $possibleOptionsCodes);

            if (count($invalid) > 0) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'This select attribute can only save existing attribute options. ' .
                        'Attribute option codes [%s] do not exist.',
                        implode(', ', $invalid)
                    )
                );
            }
        }

        return $value;
    }
}
