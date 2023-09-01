<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Product;

use Sylius\Component\Core\Model\ProductVariantInterface;
use Webgriffe\SyliusAkeneoPlugin\Product\Exception\ValidationException;

interface ValidatorInterface
{
    /**
     * @throws ValidationException
     */
    public function validate(ProductVariantInterface $productVariant): void;
}
