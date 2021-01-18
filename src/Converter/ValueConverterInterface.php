<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Converter;

use Sylius\Component\Attribute\Model\AttributeInterface;

interface ValueConverterInterface
{
    /**
     * @param int|string|bool|array $value
     *
     * @return array|int|string|bool
     */
    public function convert(AttributeInterface $attribute, $value, string $localeCode);
}
