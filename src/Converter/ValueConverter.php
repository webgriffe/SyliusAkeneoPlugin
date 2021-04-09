<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Converter;

use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Sylius\Component\Attribute\AttributeType\TextAttributeType;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ValueConverter implements ValueConverterInterface
{
    /** @var TranslatorInterface|null */
    private $translator;

    /**
     * ValueConverter constructor.
     */
    public function __construct(
        TranslatorInterface $translator = null
    ) {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function convert(AttributeInterface $attribute, $value, string $localeCode)
    {
        if (is_array($value)) {
            if ($attribute->getType() === TextAttributeType::TYPE) {
                if (!array_key_exists('amount', $value)) {
                    throw new \LogicException('Amount key not found');
                }
                $amount = (string) $value['amount'];
                if (!array_key_exists('unit', $value)) {
                    throw new \LogicException('Unit key not found');
                }
                $unit = (string) $value['unit'];
                if ($this->translator === null) {
                    return $amount . ' ' . $unit;
                }

                return $this->translator->trans('webgriffe_sylius_akeneo.ui.value_converter', ['unit' => $unit, 'amount' => $amount], null, $localeCode);
            }

            return $value;
        }
        if ($attribute->getType() === SelectAttributeType::TYPE && !is_bool($value)) {
            $value = (array) $value;
            $attributeConfiguration = $attribute->getConfiguration();
            $possibleOptionsCodes = array_map('strval', array_keys($attributeConfiguration['choices']));
            $invalid = array_diff($value, $possibleOptionsCodes);

            if (!empty($invalid)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'This select attribute can only save existing attribute options. ' .
                        'Attribute option codes [%s] do not exist.',
                        implode(', ', $invalid)
                    )
                );
            }

            return [$value];
        }

        return $value;
    }
}
