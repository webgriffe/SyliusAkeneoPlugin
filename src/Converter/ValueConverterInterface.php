<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Converter;

use Sylius\Component\Attribute\Model\AttributeInterface;

interface ValueConverterInterface
{
    public function convert(AttributeInterface $attribute, array|bool|int|string $value, string $localeCode): array|bool|int|string;
}
